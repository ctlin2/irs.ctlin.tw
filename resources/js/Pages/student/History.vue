<template lang="pug">

Head(title="過去作答紀錄")

.min-h-screen.w-full.bg-slate-50.text-slate-900
    header.bg-cyan-600.text-white
        .mx-auto.flex.max-w-6xl.flex-wrap.items-center.justify-between.gap-4.px-4.py-5
            div
                .text-sm.text-cyan-100 學習紀錄
                h1.mt-1.text-2xl.font-semibold 過去作答摘要
                .mt-1.text-sm.text-cyan-50 {{ student.std_name }} · {{ student.std_no }}
            button(type="button" class="rounded-md border border-white/60 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-700" @click="router.get('/student/quiz')") 返回測驗
    main.mx-auto.flex.w-full.max-w-6xl.flex-col.gap-6.px-4.py-6
        .grid.grid-cols-1.gap-3(class="sm:grid-cols-3")
            .border-l-4.border-cyan-500.bg-white.p-4
                .text-sm.text-slate-500 作答筆數
                .mt-1.text-2xl.font-semibold {{ filteredHistory.length }}
            .border-l-4.border-emerald-500.bg-white.p-4
                .text-sm.text-slate-500 課程數
                .mt-1.text-2xl.font-semibold {{ filteredCourseCount }}
            .border-l-4.border-amber-500.bg-white.p-4
                .text-sm.text-slate-500 課程日期
                .mt-1.text-2xl.font-semibold {{ historyGroups.length }}
        section.flex.flex-wrap.items-end.gap-3.border-b.border-slate-200.pb-5(aria-label="篩選作答紀錄")
            label.flex.min-w-52.flex-1.flex-col.gap-1.text-sm.font-medium.text-slate-700
                | 課程
                select.rounded-md.border-slate-300.bg-white.px-3.py-2.text-slate-900(v-model="courseFilter")
                    option(value="") 所有課程
                    option(v-for="course in courses" :key="course.id" :value="String(course.id)")
                        | {{ course.name }} · {{ course.className }}
            label.flex.min-w-40.flex-col.gap-1.text-sm.font-medium.text-slate-700
                | 日期
                input.rounded-md.border-slate-300.bg-white.px-3.py-2.text-slate-900(type="date" v-model="dateFilter")
            label.flex.min-w-56.flex-1.flex-col.gap-1.text-sm.font-medium.text-slate-700
                | 搜尋題目或答案
                input.rounded-md.border-slate-300.bg-white.px-3.py-2.text-slate-900(type="search" v-model="searchQuery" placeholder="輸入關鍵字")
            button(type="button" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100" @click="clearFilters") 清除
        .flex.flex-col.gap-6(v-if="historyGroups.length")
            section(v-for="group in historyGroups" :key="group.key")
                .flex.flex-wrap.items-end.justify-between.gap-2.border-b-2.border-slate-800.pb-3
                    div
                        h2.text-lg.font-semibold {{ group.courseName }}
                        .text-sm.text-slate-500 {{ group.className }} · {{ group.date }}
                    .text-sm.text-slate-500 {{ group.records.length }} 筆作答
                .overflow-x-auto.bg-white
                    table.w-full.border-collapse.text-left
                        thead
                            tr.border-b.border-slate-200.text-xs.uppercase.text-slate-500
                                th.px-3.py-3 題目
                                th.px-3.py-3 選項與答案
                                th.px-3.py-3 作答時間
                        tbody
                            tr.border-b.border-slate-100.align-top(v-for="record in group.records" :key="record.attempt_id")
                                td.min-w-64.px-3.py-4
                                    div(v-html="sanitize(record.question_name)")
                                td.min-w-72.px-3.py-4
                                    .flex.flex-col.gap-2(v-if="record.options.length")
                                        .flex.flex-col.gap-1(v-for="(option, index) in record.options" :key="option.id")
                                            .flex.min-w-0.items-start.gap-2
                                                span.w-6.shrink-0.text-sm.font-semibold.text-slate-500 {{ getAlpha(index) }}.
                                                div(class="min-w-0 flex-1 break-words [overflow-wrap:anywhere]" style="white-space: normal;" v-html="sanitize(option.name)")
                                            .flex.flex-wrap.gap-1.pl-8
                                                span(v-if="option.is_selected" class="rounded px-1.5 py-0.5 text-xs font-medium text-sky-800 bg-sky-100") 你的選擇
                                                span(v-if="option.is_correct" class="rounded px-1.5 py-0.5 text-xs font-medium text-emerald-800 bg-emerald-100") 正確答案
                                    .flex.flex-col.gap-2(v-else)
                                        .text-xs.font-medium.text-slate-500 我的答案
                                        ul.flex.flex-col.gap-1(v-if="record.answers.length")
                                            li(v-for="(answer, index) in record.answers" :key="index" v-html="sanitize(answer)")
                                        span.text-sm.text-slate-400(v-else) 無文字答案
                                        .text-xs.font-medium.text-slate-500(v-if="record.expected_answer") 正確答案規則
                                        code.text-sm.text-slate-700(v-if="record.expected_answer") {{ record.expected_answer }}
                                td.whitespace-nowrap.px-3.py-4.text-sm.text-slate-500 {{ formatDateTime(record.answered_at) }}
        .border.border-dashed.border-slate-300.bg-white.p-10.text-center(v-else)
            h2.text-lg.font-semibold {{ history.length ? '沒有符合條件的作答紀錄' : '目前沒有作答紀錄' }}
            p.mt-1.text-sm.text-slate-500 {{ history.length ? '試著清除或調整篩選條件。' : '完成課堂測驗後，紀錄會顯示在這裡。' }}
</template>

<script setup lang="ts">
import { computed, defineProps, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';

interface StudentHistoryRecord {
    attempt_id: number;
    quiz_id: number;
    course_id: number;
    course_name: string;
    class_name: string;
    quiz_date: string;
    question_name: string;
    answers: string[];
    options: HistoryOption[];
    expected_answer: string | null;
    answered_at: string;
}

interface HistoryOption {
    id: number;
    name: string;
    is_correct: boolean;
    is_selected: boolean;
}

interface StudentInfo {
    std_no: string;
    std_name: string;
}

interface HistoryGroup {
    key: string;
    courseName: string;
    className: string;
    date: string;
    records: StudentHistoryRecord[];
}

const props = defineProps<{
    history: StudentHistoryRecord[];
    student: StudentInfo;
}>();

const courseFilter = ref('');
const dateFilter = ref('');
const searchQuery = ref('');

const courses = computed(() => {
    const unique = new Map<number, { id: number; name: string; className: string }>();
    for (const record of props.history) {
        unique.set(record.course_id, {
            id: record.course_id,
            name: record.course_name,
            className: record.class_name,
        });
    }
    return [...unique.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const filteredHistory = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase();
    return props.history.filter((record) => {
        const matchesCourse = !courseFilter.value || String(record.course_id) === courseFilter.value;
        const matchesDate = !dateFilter.value || record.quiz_date === dateFilter.value;
        const optionNames = record.options.map((option) => option.name).join(' ');
        const searchableText = `${record.question_name} ${record.answers.join(' ')} ${optionNames}`.toLocaleLowerCase();
        return matchesCourse && matchesDate && (!query || searchableText.includes(query));
    });
});

const historyGroups = computed<HistoryGroup[]>(() => {
    const groups = new Map<string, HistoryGroup>();
    for (const record of filteredHistory.value) {
        const key = `${record.course_id}:${record.quiz_date}`;
        let group = groups.get(key);
        if (!group) {
            group = {
                key,
                courseName: record.course_name,
                className: record.class_name,
                date: record.quiz_date,
                records: [],
            };
            groups.set(key, group);
        }
        group.records.push(record);
    }
    return [...groups.values()].sort((a, b) => b.date.localeCompare(a.date));
});

const filteredCourseCount = computed(() => new Set(filteredHistory.value.map((record) => record.course_id)).size);
const getAlpha = (index: number) => String.fromCharCode(65 + index);
const sanitize = (html: string) => DOMPurify.sanitize(html ?? '');
const formatDateTime = (value: string) => value?.replace('T', ' ').slice(0, 16) ?? '';

const clearFilters = () => {
    courseFilter.value = '';
    dateFilter.value = '';
    searchQuery.value = '';
};
</script>
