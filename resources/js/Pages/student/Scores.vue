<template lang="pug">

Head(title="測驗成績與排行榜")

.min-h-screen.w-full.bg-slate-50.text-slate-900
    header.bg-cyan-700.text-white
        .mx-auto.flex.max-w-6xl.flex-wrap.items-center.justify-between.gap-4.px-4.py-5
            div
                .text-sm.text-cyan-100 學習紀錄
                h1.mt-1.text-2xl.font-semibold 測驗成績與排行榜
                .mt-1.text-sm.text-cyan-50 {{ student.std_name }} · {{ student.std_no }}
            .flex.flex-wrap.gap-2
                button(type="button" class="rounded-md border border-white/60 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800" @click="router.get('/student/quiz')") 返回測驗
                button(type="button" class="rounded-md border border-white/60 px-4 py-2 text-sm font-medium text-white hover:bg-cyan-800" @click="router.get('/student/history')") 作答紀錄
    main.mx-auto.flex.w-full.max-w-6xl.flex-col.gap-6.px-4.py-6
        section.flex.flex-wrap.items-end.gap-3(aria-label="選擇課程與日期")
            label.flex.min-w-52.flex-1.flex-col.gap-1.text-sm.font-medium.text-slate-700
                | 課程
                select.rounded-md.border-slate-300.bg-white.px-3.py-2.text-slate-900(v-if="course" :value="String(course.id)" @change="selectCourse")
                    option(v-for="item in courses" :key="item.id" :value="String(item.id)")
                        | {{ item.course_name }} · {{ item.class_name }}
            label.flex.min-w-44.flex-col.gap-1.text-sm.font-medium.text-slate-700(v-if="course")
                | 測驗日期
                select.rounded-md.border-slate-300.bg-white.px-3.py-2.text-slate-900(:value="selected_date" @change="selectDate")
                    option(v-for="date in dates" :key="date" :value="date") {{ date }}
                    option(v-if="!dates.length" :value="selected_date") {{ selected_date }}
        section(v-if="course" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4")
            .border-l-4.border-cyan-500.bg-white.p-4
                .text-sm.text-slate-500 {{ selected_date }} 測驗數
                .mt-1.text-2xl.font-semibold {{ quizzes.length }}
            .border-l-4.border-emerald-500.bg-white.p-4
                .text-sm.text-slate-500 當日得分
                .mt-1.text-2xl.font-semibold {{ formatScore(myDailyScore) }}
            .border-l-4.border-amber-500.bg-white.p-4
                .text-sm.text-slate-500 當日累計排名
                .mt-1.text-2xl.font-semibold {{ myDailyRank ? `第 ${myDailyRank} 名` : '—' }}
            .border-l-4.border-violet-500.bg-white.p-4
                .text-sm.text-slate-500 課程累計得分
                .mt-1.text-2xl.font-semibold {{ formatScore(myCumulativeScore) }}
        section(v-if="course")
            .mb-3.flex.flex-wrap.items-end.justify-between.gap-2
                div
                    h2.text-lg.font-semibold {{ course.course_name }} · 單次測驗
                    p.text-sm.text-slate-500 {{ course.class_name }} · {{ selected_date }}
                span.text-sm.text-slate-500 每次測驗依該題配分計分
            .overflow-x-auto.bg-white
                table.w-full.border-collapse.text-left
                    thead
                        tr.border-b.border-slate-200.text-xs.uppercase.text-slate-500
                            th.px-4.py-3 時間
                            th.px-4.py-3 測驗題目
                            th.px-4.py-3 得分
                            th.px-4.py-3 排名
                            th.px-4.py-3 作答狀態
                    tbody(v-if="quizzes.length")
                        tr.border-b.border-slate-100(v-for="quiz in quizzes" :key="quiz.id")
                            td.whitespace-nowrap.px-4.py-3.text-sm.text-slate-500 {{ quiz.time }}
                            td.min-w-64.px-4.py-3
                                div(v-html="sanitize(quiz.question_name)")
                            td.whitespace-nowrap.px-4.py-3.font-medium {{ formatScore(quiz.score) }} / {{ formatScore(quiz.marks) }}
                            td.whitespace-nowrap.px-4.py-3 第 {{ quiz.rank }} 名
                            td.px-4.py-3
                                span(v-if="quiz.attempted" class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800") 已作答
                                span(v-else class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600") 未作答
                    tbody(v-else)
                        tr
                            td.px-4.py-8.text-center.text-slate-500(colspan="5") {{ selected_date }} 尚無測驗
        section(v-if="course" class="grid grid-cols-1 gap-6 lg:grid-cols-2")
            div
                h2.mb-3.text-lg.font-semibold 當日累計排行榜
                .overflow-x-auto.bg-white
                    table.w-full.border-collapse.text-left
                        thead
                            tr.border-b.border-slate-200.text-xs.uppercase.text-slate-500
                                th.px-3.py-3 排名
                                th.px-3.py-3 學號
                                th.px-3.py-3 姓名
                                th.px-3.py-3 得分
                        tbody
                            tr.border-b.border-slate-100(v-for="row in daily_leaderboard" :key="row.student_id" :class="{'bg-cyan-50 font-semibold': row.is_me}")
                                td.px-3.py-3 第 {{ row.rank }} 名
                                td.px-3.py-3 {{ row.std_no }}
                                td.px-3.py-3 {{ row.std_name }} {{ row.is_me ? '（我）' : '' }}
                                td.px-3.py-3 {{ formatScore(row.score) }}
                        tbody(v-if="!daily_leaderboard.length")
                            tr
                                td.px-3.py-8.text-center.text-slate-500(colspan="4") 尚無課程學生資料
            div
                h2.mb-3.text-lg.font-semibold 課程累計排行榜
                .overflow-x-auto.bg-white
                    table.w-full.border-collapse.text-left
                        thead
                            tr.border-b.border-slate-200.text-xs.uppercase.text-slate-500
                                th.px-3.py-3 排名
                                th.px-3.py-3 學號
                                th.px-3.py-3 姓名
                                th.px-3.py-3 累計得分
                        tbody
                            tr.border-b.border-slate-100(v-for="row in cumulative_leaderboard" :key="row.student_id" :class="{'bg-cyan-50 font-semibold': row.is_me}")
                                td.px-3.py-3 第 {{ row.rank }} 名
                                td.px-3.py-3 {{ row.std_no }}
                                td.px-3.py-3 {{ row.std_name }} {{ row.is_me ? '（我）' : '' }}
                                td.px-3.py-3 {{ formatScore(row.score) }}
                        tbody(v-if="!cumulative_leaderboard.length")
                            tr
                                td.px-3.py-8.text-center.text-slate-500(colspan="4") 尚無課程學生資料
        .border.border-dashed.border-slate-300.bg-white.p-10.text-center(v-else)
            h2.text-lg.font-semibold 尚無修課資料
            p.mt-1.text-sm.text-slate-500 請確認學生帳號已加入課程。
</template>

<script setup lang="ts">
import { computed, defineProps } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';

interface CourseOption {
    id: number;
    course_name: string;
    class_name: string;
}

interface LeaderboardRow {
    student_id: number;
    std_no: string;
    std_name: string;
    score: number;
    rank: number;
    is_me: boolean;
}

interface QuizScore {
    id: number;
    question_name: string;
    time: string;
    marks: number;
    score: number;
    rank: number;
    attempted: boolean;
}

const props = defineProps<{
    courses: CourseOption[];
    course: CourseOption | null;
    dates: string[];
    selected_date: string;
    quizzes: QuizScore[];
    daily_leaderboard: LeaderboardRow[];
    cumulative_leaderboard: LeaderboardRow[];
    student: { std_no: string; std_name: string };
}>();

const myDailyScore = computed(() => props.daily_leaderboard.find((row) => row.is_me)?.score ?? 0);
const myDailyRank = computed(() => props.daily_leaderboard.find((row) => row.is_me)?.rank ?? null);
const myCumulativeScore = computed(() => props.cumulative_leaderboard.find((row) => row.is_me)?.score ?? 0);

const formatScore = (score: number) => new Intl.NumberFormat('zh-TW', { maximumFractionDigits: 2 }).format(score);
const sanitize = (html: string) => DOMPurify.sanitize(html ?? '');

const selectCourse = (event: Event) => {
    const courseId = (event.target as HTMLSelectElement).value;
    router.get('/student/scores', { course_id: courseId });
};

const selectDate = (event: Event) => {
    const date = (event.target as HTMLSelectElement).value;
    if (props.course) {
        router.get('/student/scores', { course_id: props.course.id, date });
    }
};
</script>
