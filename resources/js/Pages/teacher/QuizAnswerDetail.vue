<template lang="pug">

Head(title="答題分析")

.w-screen.h-screen.flex.flex-col
    .flex.justify-center.p-4.gap-4
        PrimaryButton(@click="toggleShowCorrect")
            | {{ showCorrect ? '隱藏' : '顯示' }}答案
        PrimaryButton(@click="addStudentPoints")
            | 答對個人加積點
        input(type="text" v-model="current_quiz_marks" size=2)
        PrimaryButton(@click="addAttendant")
            | 答題當做出席
        PrimaryButton(@click="addAbsent")
            | 未答題當做缺席
        PrimaryButton(@click="addLeave")
            | 未答題當做早退
    .flex.gap-4.p-4.justify-center(v-if="q_type_id !== 3")
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
    .flex.flex-wrap.gap-4.p-4.justify-center(v-if="q_type_id=== 3")
        .flex(v-for="(item, index) in std_answers")
            .flex.px-4.py-2.mb-2.min-w-fit.font-medium(:class="[showCorrect ? getCorrectStyle(is_correct(item.std_id)) : 'bg-gray-200', 'outline-1 outline-offset-1 outline-black']")
                | {{ item.std_no }} {{ item.std_name }}:「{{ item.answer }}」 {{ correct_wrong(item.std_id) }}
</template>

<script setup lang="ts">
import { ref, computed, defineProps, watch } from 'vue';
import { Head, router, useForm } from "@inertiajs/vue3";
import * as _ from 'lodash';
import { Question, QOption, Student, Answers, CourseQuiz } from '@/Components/teacher/UtilsType';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Swal from 'sweetalert2';


interface StdAnswers extends Answers{
    std_no: string,
    std_name: string,
    course_attempt_id: number,
}

const props = defineProps<{
    quiz_info: CourseQuiz,
    q_type_id: number,
    std_answers: Array<StdAnswers>,
    correct_std_answers: Array<StdAnswers>,
    q_options: Array<QOption>,
}>();

/* data */

const showCorrect = ref(false);
const current_quiz_marks = ref(props.quiz_info.marks);

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

// C.T.Lin
const is_correct = (std_id: number) => _.size(_.filter(props.correct_std_answers, { 'std_id': std_id })) > 0;
const correct_wrong = (std_id: number) => showCorrect.value ? (
    _.size(_.filter(props.correct_std_answers, { 'std_id': std_id })) > 0 ? '(答對)': '（答錯）') : '(待評)'; 

const postData = (data: object): void => {
    console.log(data);
    useForm(data).post('/teacher/quiz_answer_detail',
        { onError: (p) => {
                console.log('onError p::')
                console.log(p)
                alert(p.errors)
            },
            onSuccess: () => {
                // alert('作答完成')
                Swal.fire({
                    text: '更改完成',
                    icon: 'success',
                    toast: true,
                    showConfirmButton: false,
                    // position: 'middle',
                    timer: 3500
                })
            }
        }
    );
}

const addStudentPoints = () => {
    if (props.q_type_id === 3){ // fill-in
        let data = {
            _action: 'add_student_points',
            q_type_id: 3,
            student_answers:  props.correct_std_answers,
            marks: current_quiz_marks.value,
        };
        postData(data);
    }
    else {
        let data = {
            _action: 'add_student_points',
            q_type_id: 1, // or 2
            student_answers:  _.uniqBy(props.std_answers, 'std_no'),
            marks: current_quiz_marks.value,
        };
        postData(data);
    }
};

const addAttendant = () => {
    let data = {
        _action: 'add_attendant',
        course_attempt_id: props.std_answers.length == 0? -1 :props.std_answers[0]['course_attempt_id'],
        student_ids: props.std_answers.length == 0? [] :_.map(_.uniqBy(props.std_answers, 'std_no'), o =>o['std_id']),  // std_no, std_name, course_quiz_id
    };
    postData(data);
};

const addAbsent = () => {
    let data = {
        _action: 'add_absent',
        course_attempt_id: props.std_answers.length == 0? -1 :props.std_answers[0]['course_attempt_id'],
        student_ids: props.std_answers.length == 0? [] :_.map(_.uniqBy(props.std_answers, 'std_no'), o =>o['std_id']),  // std_no, std_name, 
    };
    postData(data);
};

const addLeave = () => {
    let data = {
        _action: 'add_leave',
        course_attempt_id: props.std_answers.length == 0? -1 :props.std_answers[0]['course_attempt_id'],
        student_ids: props.std_answers.length == 0? [] :_.map(_.uniqBy(props.std_answers, 'std_no'), o =>o['std_id']),  // std_no, std_name, 
        // student_numbers: _.map(_.uniqBy(props.std_answers, 'std_no'), o => _.pick(o, ['std_no'])),  // std_no, std_name, course_quiz_id
    };
    postData(data);
};


</script>

<style scoped>

</style>
