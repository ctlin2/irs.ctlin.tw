<template lang="pug">

Head(title="課堂測驗")

.w-screen.h-screen.flex.flex-col
    header.bg-cyan-600.flex.items-center.justify-end.px-4(class="h-[52px]")
        PrimaryButton(type="button" @click="router.get('/student/history')") 過去作答紀錄
    .flex.flex-col.gap-4.p-4.items-center
        .flex-flex-col.gap-2.p-4.text-3xl(v-if="hasNotQuiz")
            | 尚無測驗
        .flex-flex-col.w-full.gap-2.p-2(v-else :key="quiz.id + '-' + question.id")
            div(v-html="DOMPurify.sanitize(question.name)")
                //- | {{ question.name }}
            img(v-show="question.media_type == 'image'" :src="url").mx-auto.max-w-80
            //- img added by C.T.Lin style="max-width:700px;"//
            .py-4.px-8
                div(v-if="multichoice").flex.flex-col.gap-4
                    label.flex.gap-2.items-center(v-for="(item, index) in q_option")
                        TextInput(v-if="single_answer" type="radio" name="ans" v-model="chooseAns" :value="item.id")
                        TextInput(v-if="multiple_answer" type="checkbox" name="ans"
                        :checked="selected.includes(item.id)"
                        @change="selectOpts(item.id)" :value="item.id")
                        | {{ getAlpha(index) }}.
                        span(v-html="DOMPurify.sanitize(item.name)")
            .p-1
                div(v-if="question.question_type_id === 3").flex.flex-col.gap-4
                    InputLabel(value="填充題答案:").text-lg
                    TextInput(v-model="answer").w-full
            PrimaryButton(@click="submit") 送出
            | {{ timeLeft }}
            div.text-red-500(v-if="e_msg !== ''") {{ e_msg }}
            //- added by C.T.Lin 
</template>

<script setup lang="ts">
import { ref, reactive, computed, defineProps, onMounted, watch } from 'vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import * as _ from 'lodash';
import { Question, QOption, CourseQuiz} from '@/Components/teacher/UtilsType';
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Swal from 'sweetalert2';
import moment from "moment";
import DOMPurify from 'dompurify';

const props = defineProps<{
    quiz: CourseQuiz,
    question: Question,
    q_option: Array<QOption>,
    time_left: string, // added by C.T.Lin
    e_msg?: string, // added by C.T.Lin
}>();

/* data */
var interval = null;

const chooseAns = ref<number>(null);
const selected = ref<number[]>([]);
const answer = ref(null); // for fill-in question, C.T.Lin
const countdown = ref('00:00:00');

const my_class = reactive([
    {id: 1, class_name: '資工一A', course_title: '離散數學'}
]);
const my_groups = reactive([
    {id: 1, name: '組1', score: 0}
]);

/* computed */

const hasNotQuiz = computed(() => props.quiz === null);

const single_answer = computed(() => props.question.question_type_id === 1);
const multiple_answer = computed(() => props.question.question_type_id === 2);
const multichoice = computed(() => props.question.question_type_id === 1 ||
                                props.question.question_type_id === 2);
const url = computed(() => '/storage/images/' + props.question.media_url??'noimg-200-a.png' ); // added by C.T.Lin
const timeLeft = computed(()=> '時間剩:' + countdown.value);

watch(
    () => `${props.quiz?.id ?? ''}:${props.question?.id ?? ''}`,
    (questionKey, previousQuestionKey) => {
        if (questionKey === previousQuestionKey) return;

        chooseAns.value = null;
        selected.value = [];
        answer.value = null;
    }
);

/* methods */

const getAlpha = (shift: number) => {
    return String.fromCharCode(65 + shift);
}

const selectOpts = (id: number) => {
    //in here you can check what ever condition  before append to array.
    if(selected.value.indexOf(id) !== -1){  // already in array
        selected.value =_.without(selected.value, id)  // toggle out
    }else{
        selected.value.push(id)
    }
}

const submit = () => {
    clearInterval(interval);
    useForm({
        course_quiz_id: props.quiz.id,
        q_option_id: chooseAns.value, // for single answer
        selected: selected.value, // for multiple answers
        answer: answer.value, // for fill-in answer
    }).post('/student/quiz', {  // modified by C.T.Lin
        onError: (p) => {
            console.log('onError p::')
            console.log(p)
            // alert(p.errors)
            Swal.fire({
                text: p.status,
                icon: 'error',
                toast: true,
                showConfirmButton: false,
                position: 'center',
                timer: 3500
            });
        },
        onSuccess: () => {
            // alert('作答完成')
            Swal.fire({
                text: '作答完成',
                icon: 'success',
                toast: true,
                showConfirmButton: false,
                position: 'center',
                timer: 3500
            });
        },
    });
}

const replaceUrl = () => {
    if (!hasNotQuiz) {
        let changeUrl = `https://irs.ctlin.tw/student/quiz?course_id${props.quiz.course_id}&course_date=${props.quiz.course_date}`;
        if (window.location.href !== changeUrl)
            window.location.href = changeUrl;
    }
}

const updateTimer = () => {
    var duration = moment.duration(countdown.value);
    if (duration.as('milliseconds') > 0){
        duration.subtract(1, 'second');
        countdown.value = moment.utc(duration.as('milliseconds')).format('HH:mm:ss');
    }
    else {
        countdown.value = '00:00:00';
        clearInterval(interval);
        console.log('time off');
    }
}

onMounted(() => {
    var duration  = moment.duration(props.time_left);
    countdown.value = moment.utc(duration.as('milliseconds')).format('HH:mm:ss');
    interval =setInterval(function () {
        updateTimer();
    }.bind(this), 1000);  // set 1000 to any number you need
})

/* created */

console.log(props.quiz);
console.log(props.question);
console.log(props.q_option);

replaceUrl();

</script>

<style scoped>

</style>