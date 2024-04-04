<template lang="pug">

Head(title="答題分析")

.w-screen.h-screen.flex.flex-col
    .flex.justify-center.p-4
        PrimaryButton(@click="toggleShowCorrect")
            | {{ showCorrect ? '隱藏' : '顯示' }}答案
    .flex.gap-4.p-4.justify-center
        .flex.flex-col.truncate(v-for="(item, index) in q_options" style="width: 250px;")
            .flex.p-4.justify-center
                | 選擇人數：{{ getCount(getStudents(item.id)) }}
            .flex.px-4.py-2.mb-3.font-medium.shadow-md(:class="[showCorrect ? getCorrectStyle(item.is_correct) : '', 'outline-2 outline-offset-1 outline-black']")
                textarea
                    | ({{ getAlpha(index) }}) {{ item.name }}
            //.flex.justify-center.items-center.px-4.py-2.mb-3.text-2xl.font-bold(v-show="showCorrect"
            //    :class="[item.is_correct ? 'bg-cyan-500' : 'bg-red-500']")
            //    | {{ item.is_correct ? '正確' : '錯誤' }}
            div(v-for="(std, s_index) in getStudents(item.id)" class="odd:bg-gray-200 even:bg-violet-200 py-2 px-4")
                | {{ std.std_no }} {{ std.std_name }}

</template>

<script setup lang="ts">
import { ref, computed, defineProps } from 'vue';
import { Head } from "@inertiajs/vue3";
import * as _ from 'lodash';
import { Question, QOption, Student, Answers } from '@/Components/teacher/UtilsType';
import PrimaryButton from "@/Components/PrimaryButton.vue";


interface StdAnswers extends Answers{
    std_no: string,
    std_name: string,
}

const props = defineProps<{
    std_answers: Array<StdAnswers>,
    q_options: Array<QOption>,
}>();

/* data */

const showCorrect = ref(false);

/* computed */

const std_answers = computed<Array<StdAnswers>>(() => props.std_answers);
const q_options = computed<Array<QOption>>(() => props.q_options);

const groupStudents = computed(() => {
    return _.groupBy(std_answers.value, 'question_option_id');
});

/* methods */

const getAlpha = (shift: number): string => {
    return String.fromCharCode(65 + shift);
}

const getStudents = (question_option_id: number): Array<StdAnswers> => {
    return groupStudents.value[question_option_id];
}

const getCorrectStyle = (is_correct: boolean) => is_correct ? 'bg-cyan-500 ring ring-cyan-700' : 'bg-red-500 ring ring-red-700';

const getCount = (item: StdAnswers[]) => _.size(item);

const toggleShowCorrect = () => showCorrect.value = !showCorrect.value;

</script>

<style scoped>

</style>
