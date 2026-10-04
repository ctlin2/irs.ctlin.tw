<template lang="pug">

Head(title="測驗總覽")

main.min-h-screen.w-full.bg-gray-50
    .bg-cyan-400(class="h-[52px]")
    .mx-auto.flex.w-full.max-w-7xl.flex-col.gap-6.p-6
        .flex.flex-wrap.items-end.justify-between.gap-4.border-b.border-gray-200.pb-5
            div
                .text-sm.text-gray-500 教學管理 / 測驗
                h1.mt-1.text-2xl.font-semibold.text-gray-900 測驗總覽
            PrimaryButton(type="button" @click="router.get('/teacher/course')") 回課程管理
        section.flex.flex-wrap.items-end.gap-3(aria-label="測驗篩選")
            label.flex.min-w-56.flex-1.flex-col.gap-1.text-sm.font-medium.text-gray-700(for="course-filter")
                | 課程
                select#course-filter.rounded-md.border-gray-300.px-3.py-2.text-gray-900(v-model="courseFilter")
                    option(value="") 所有課程
                    option(v-for="course in courses" :key="course.course_id" :value="String(course.course_id)")
                        | {{ course.course_name }} - {{ course.class_name }}
            label.flex.min-w-40.flex-col.gap-1.text-sm.font-medium.text-gray-700(for="date-filter")
                | 日期
                input#date-filter.rounded-md.border-gray-300.px-3.py-2.text-gray-900(type="date" v-model="dateFilter")
            label.flex.min-w-56.flex-1.flex-col.gap-1.text-sm.font-medium.text-gray-700(for="search-filter")
                | 題目搜尋
                input#search-filter.rounded-md.border-gray-300.px-3.py-2.text-gray-900(type="search" v-model="searchQuery" placeholder="輸入題目關鍵字")
            PrimaryButton(type="button" class="!bg-cyan-700 hover:!bg-cyan-600" @click="clearFilters") 清除篩選
        .text-sm.text-gray-500
            | 顯示 {{ filteredQuizzes.length }} 份測驗，分布於 {{ quizGroups.length }} 個課程日期
        .grid.grid-cols-1.gap-3(class="sm:grid-cols-3")
                .border-l-4.border-cyan-500.bg-cyan-50.p-4
                    .text-sm.text-gray-600 測驗總數
                    .mt-1.text-2xl.font-semibold.text-gray-900 {{ filteredQuizzes.length }}
                .border-l-4.border-emerald-500.bg-emerald-50.p-4
                    .text-sm.text-gray-600 作答次數
                    .mt-1.text-2xl.font-semibold.text-gray-900 {{ totalAttempts }}
                .border-l-4.border-amber-500.bg-amber-50.p-4
                    .text-sm.text-gray-600 課程日期總數
                    .mt-1.text-2xl.font-semibold.text-gray-900 {{ quizGroups.length }}
        .flex.flex-col.gap-6(v-if="quizGroups.length")
                section(v-for="group in quizGroups" :key="group.key")
                    .flex.flex-wrap.items-end.justify-between.gap-3.border-b-2.border-gray-800.pb-3
                        div
                            h3.text-lg.font-semibold.text-gray-900 {{ group.course_name }}
                            .text-sm.text-gray-600 {{ group.class_name }} · {{ group.quiz_date }}
                        .text-sm.text-gray-600 {{ group.quizzes.length }} 份測驗 · {{ group.attempts }} 次作答
                    .overflow-x-auto
                        table.w-full.border-collapse.text-left
                            thead
                                tr.border-b.border-gray-300.text-xs.uppercase.text-gray-500
                                    th.px-3.py-2 題目
                                    th.px-3.py-2 狀態
                                    th.px-3.py-2 截止時間
                                    th.px-3.py-2.text-right 作答
                                    th.px-3.py-2
                            tbody
                                tr.border-b.border-gray-100(v-for="quiz in group.quizzes" :key="quiz.quiz_id")
                                    td.px-3.py-3
                                        span.text-gray-900(v-html="DOMPurify.sanitize(quiz.question_name)")
                                    td.px-3.py-3
                                        span(:class="quizExpired(quiz.expired_at) ? 'text-gray-500' : 'font-medium text-emerald-700'")
                                            | {{ quizExpired(quiz.expired_at) ? '已結束' : '進行中' }}
                                    td.px-3.py-3.text-sm.text-gray-600 {{ formatDateTime(quiz.expired_at) }}
                                    td.px-3.py-3.text-right.tabular-nums {{ quiz.attempt_count }}
                                    td.px-3.py-3.text-right
                                        button(type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-100" @click="openAnalysis(quiz.quiz_id)") 查看分析
        .border.border-dashed.border-gray-300.p-8.text-center.text-gray-600(v-else)
            .text-lg.font-medium.text-gray-900 找不到測驗
            p.mt-1.text-sm 請調整課程、日期或搜尋條件。
</template>

<script setup lang="ts">
import { computed, defineProps, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import PrimaryButton from '@/Components/PrimaryButton.vue';

interface QuizSummaryRecord {
    quiz_id: number;
    course_id: number;
    course_name: string;
    class_name: string;
    quiz_date: string;
    question_name: string;
    created_at: string;
    expired_at: string;
    attempt_count: number;
}

interface QuizGroup {
    key: string;
    course_id: number;
    course_name: string;
    class_name: string;
    quiz_date: string;
    attempts: number;
    quizzes: QuizSummaryRecord[];
}

const props = defineProps<{ quizzes: QuizSummaryRecord[] }>();
const courseFilter = ref('');
const dateFilter = ref('');
const searchQuery = ref('');

const courses = computed(() => {
    const unique = new Map<number, { course_id: number; course_name: string; class_name: string }>();
    for (const quiz of props.quizzes) {
        unique.set(quiz.course_id, {
            course_id: quiz.course_id,
            course_name: quiz.course_name,
            class_name: quiz.class_name,
        });
    }
    return [...unique.values()].sort((a, b) => a.course_name.localeCompare(b.course_name));
});

const filteredQuizzes = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase();
    return props.quizzes.filter((quiz) => {
        const matchesCourse = !courseFilter.value || String(quiz.course_id) === courseFilter.value;
        const matchesDate = !dateFilter.value || quiz.quiz_date === dateFilter.value;
        const matchesQuery = !query || `${quiz.question_name} ${quiz.course_name} ${quiz.class_name}`.toLocaleLowerCase().includes(query);
        return matchesCourse && matchesDate && matchesQuery;
    });
});

const quizGroups = computed<QuizGroup[]>(() => {
    const groups = new Map<string, QuizGroup>();
    for (const quiz of filteredQuizzes.value) {
        const key = `${quiz.course_id}:${quiz.quiz_date}`;
        let group = groups.get(key);
        if (!group) {
            group = {
                key,
                course_id: quiz.course_id,
                course_name: quiz.course_name,
                class_name: quiz.class_name,
                quiz_date: quiz.quiz_date,
                attempts: 0,
                quizzes: [],
            };
            groups.set(key, group);
        }
        group.quizzes.push(quiz);
        group.attempts += Number(quiz.attempt_count);
    }
    return [...groups.values()];
});

const totalAttempts = computed(() => filteredQuizzes.value.reduce((total, quiz) => total + Number(quiz.attempt_count), 0));

const clearFilters = () => {
    courseFilter.value = '';
    dateFilter.value = '';
    searchQuery.value = '';
};

const formatDateTime = (value: string) => value?.slice(0, 16).replace('T', ' ') ?? '';
const quizExpired = (value: string) => new Date(value.replace(' ', 'T')).getTime() < Date.now();
const openAnalysis = (quizId: number) => router.get('/teacher/quiz_answer_detail', { course_quiz_id: quizId });
</script>
