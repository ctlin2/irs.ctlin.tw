<template lang="pug">

Head(title="課堂測驗")

.w-screen.h-screen.flex.flex-col
    .bg-cyan-400.flex(class="h-[52px]")
    .flex.flex-col.gap-4.p-4.items-center
        .flex-flex-col.gap-4.p-4.text-3xl(v-if="hasNotQuiz")
            | 尚無測驗
        .flex-flex-col.gap-4.p-4(v-else)
            div
                | {{ question.name }}
            img(v-show="question.media_type == 'image'" :src="url" style="max-width:700px;").mx-auto.w-80
            //- img added by C.T.Lin //
            .py-4.px-8
                .flex.flex-col.gap-4
                    label.flex.gap-2.items-center(v-for="(item, index) in q_option")
                        TextInput(v-if="single_answer" type="radio" name="ans" v-model="chooseAns" :value="item.id")
                        TextInput(v-if="multiple_answer" type="checkbox" name="ans"
                        v-on:click="()=>{selectOpts(item.id)}") :value="item.id")
                        | {{ getAlpha(index) }}.
                        | {{ item.name }}
            PrimaryButton(@click="submit") 送出
            div.text-red-500(v-if="e_msg !== ''") {{ e_msg }}
            //- added by C.T.Lin 
</template>

<script setup lang="ts">
import { ref, reactive, computed, defineProps, watch } from 'vue';
import {Head, useForm} from '@inertiajs/vue3';
import * as _ from 'lodash';
import { Question, QOption, CourseQuiz} from '@/Components/teacher/UtilsType';
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Swal from 'sweetalert2';

const props = defineProps<{
    quiz: CourseQuiz,
    question: Question,
    q_option: Array<QOption>,
    e_msg?: string, // added by C.T.Lin
}>();

/* data */

const chooseAns = ref<number>(null);
const selected = ref([]);

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
const url = computed(() => '/storage/images/' + props.question.media_url??'noimg-200-a.png' ); // added by C.T.Lin

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
    useForm({
        course_quiz_id: props.quiz.id,
        q_option_id: chooseAns.value,
        selected: selected.value,
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

/* created */

console.log(props.quiz);
console.log(props.question);
console.log(props.q_option);

replaceUrl();

</script>

<style scoped>

</style>