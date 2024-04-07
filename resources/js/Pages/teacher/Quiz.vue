<template lang="pug">

Head(title="出題測驗")

BaseLayout
    template(#left)
        .flex.justify-center.p-4.text-white.text-2xl
            | 日期：{{ currentCourseDate ?? '未知' }}

        .border.border-white.border-collapse.overflow-y-auto(style="max-height: 700px;")
            .bg-white.px-4.py-2.border-y.border-gray-400.select-none.flex.justify-between.truncate(v-for="(item, index) in questions"
                :class="['hover:bg-gray-200', {'from-cyan-400 to-10% to-transparent bg-gradient-to-r': currentQuestionId === item.id}, ]"
                @click="onClickSelectedQuestion(item.id)")
                .flex
                    | {{ item.name }}

    .flex-1.flex.flex-col.overflow-y-auto.max-h-screen
        .bg-cyan-400.flex(class="h-[52px]")
        .flex-1.flex.flex-col.p-4.gap-4
            SimpleTopic(:topic="topics" v-model="topicId")
            .flex.gap-4
                PrimaryButton(@click="onClickSearch") search
            .flex.flex-col.p-4.gap-2.border.border-black.rounded-md
                .flex.flex-col
                    div {{ currentQuestionInfo.name }}
                .py-4.px-8
                    ol.flex.flex-col.gap-4(style="list-style-type: upper-alpha;")
                        li(v-for="(item, index) in currentQOptions")
                            | {{ item.name }}
                img(v-show="currentQuestionInfo.media_type == 'image'" :src="url" style="max-width:700px;").mx-auto
                //- img added by C.T.Lin //
                .flex.items-center.gap-4
                    | 測驗時間
                    input.rounded-md.text-center(style="width: 100px;" type="number" list="expire_time" min="0" v-model="expireTime")
                    datalist#expire_time
                        option(value="5")
                        option(value="10")
                        option(value="15")
                    | 分鐘
                    PrimaryButton(@click="submitAddQuiz") 發佈
            .flex
                table
                    thead
                        tr
                            th
                                span.cursor-pointer(@click="reloadPage")
                                    svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6")
                                        path(stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99")
                            th 題目
                            //th 答題人數
                            th 答對人數/答題人數
                            th 截止時間
                            th.cursor-pointer(@click="onClickShowQRCode")
                                span.cursor-pointer.inline-flex.justify-center.items-center
                                    svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6")
                                        path(stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z")
                                        path(stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75zM6.75 16.5h.75v.75h-.75v-.75zM16.5 6.75h.75v.75h-.75v-.75zM13.5 13.5h.75v.75h-.75v-.75zM13.5 19.5h.75v.75h-.75v-.75zM19.5 13.5h.75v.75h-.75v-.75zM19.5 19.5h.75v.75h-.75v-.75zM16.5 16.5h.75v.75h-.75v-.75z")

                    tbody
                        tr(v-for="(item, index) in quiz_list")
                            td.text-center {{ index + 1 }}
                            td {{ item.name }}
                            //td.text-center {{ item.std_answer_num }}
                            td.text-center.cursor-pointer(@click="onClickOpenAnswersAnalysis(item.id)") {{ item.std_answer_correct_num }}/{{ item.std_answer_num }}
                            td.text-center {{ format_time(item.expired_at) }}
                            td
                                PrimaryButton(class="!bg-red-500" @click="submitDelQuiz(item.id)") 刪除
    Modal(:show="showQRCode" :closeable="true" @close="showQRCode = false")
        .flex.flex-col.justify-center.items.center.p-4.gap-4
            div.text-3xl 課堂測驗 QR Code
            .flex.justify-center
                QrcodeVue(:value="qrcodeUrl" :size="300")
</template>

<script setup lang="ts">
import {ref, reactive, computed, defineProps, watch, onMounted, toRefs} from 'vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import * as _ from 'lodash';
import moment from "moment";
import QrcodeVue from "qrcode.vue";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SimpleTopic from "@/Components/teacher/SimpleTopic.vue";
import Modal from "@/Components/Modal.vue";
import { Topic, Question as QuesType, QOption, QuizList} from '@/Components/teacher/UtilsType';
import Course from './Course.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    topic: {
        type: Array<Topic>,
        required: true,
    },
    questions: {
        type: Array<QuesType>,
        required: true,
    },
    q_options: {
        type: Array<QOption>,
    },
    course_quiz_rds: {
        type: Array<QuizList>,
    },
    report: {
        type: Array,
    },
    class_name: {
        type: String,
    },
    course_id: {
        type: Number,
    },
    course_name: {
        type: String,
    },
    course_date: {
        type: String,
    },
});

/* data */

const showQRCode = ref<boolean>(false);
// current search topic id
const topicId = ref<number>(null);
const expireTime = ref<number>(5);
const currentQuestionId = ref<number>();
const currentQuestionInfo = ref<QuesType>({
    id: null,
    name: '',
    topic_id: null,
    media_type: '', // added  by C.T.Lin
    media_url: 'noimg-200-a.png', // added  by C.T.Lin, default: no image
    question_type_id: 1,
});
const currentQOptions = ref<Array<QOption>>([]);

/* computed */

// const currentCourseId = computed(() => props.course_id);
const currentCourseId = computed(() => props.course_id);
const currentClassName = computed(() => props.class_name);
const currentCourseName = computed(() => props.course_name);
const currentCourseDate = computed(() => props.course_date);
const topics = computed<Array<Topic>>(() => props.topic);
const questions = computed<Array<QuesType>>(() => props.questions);
const q_options = computed<Array<QOption>>(() => props.q_options);
const quiz_list = computed<Array<QuizList>>(() => {
    return _.transform(props.course_quiz_rds, (result, item, index) => {
        result.push(_.merge(item, _.get(props.report, item.id)))
    }, []);
});

const qrcodeUrl = computed(() => {
    let baseUrl = window.location.host;
    return `https://${baseUrl}/student/quiz?course_id=${currentCourseId.value}&course_date=${currentCourseDate.value}`
});

const url = computed(() => '/storage/images/' + currentQuestionInfo.value.media_url); // added by C.T.Lin

/* methods */

const format_time = (time: string) => moment(time).format('HH:mm');

const onClickShowQRCode = () => {
    showQRCode.value = true;
}

const onClickSearch = () => {
    let data = {};
    if (topicId.value !== null) {
        _.set(data, 't_id', topicId.value);
    }
    router.get('/teacher/quiz', data);
}

const filterQuestionOptions = (q_id: number) => _.filter(q_options.value, {question_id: q_id});

const onClickSelectedQuestion = (val: number): void => {
    currentQuestionId.value = val;
    currentQuestionInfo.value = _.cloneDeep(_.find(questions.value, {id: val}));
    currentQOptions.value = _.cloneDeep(filterQuestionOptions(val));
}

const onClickOpenAnswersAnalysis = (quiz_id: number) => {
    let url = `https://irs.ctlin.tw/teacher/quiz_answer_detail?course_quiz_id=${quiz_id}`;
    window.open(url, 'new', 'popup');
}

const postData = (data: object): void => {
    console.log(data);
    useForm(data).post('/teacher/quiz',
        { onError: (p) => {
            console.log('onError p::')
            console.log(p)
            // alert(p.errors)
            Swal.fire({
                text: '失敗：需先建立課程分組名單',
                icon: 'error',
                toast: true,
                showConfirmButton: false,
                position: 'middle',
                timer: 3500
            })
        },
        onSuccess: () => {
            // alert('作答完成')
            Swal.fire({
                text: '完成',
                icon: 'success',
                toast: true,
                showConfirmButton: false,
                position: 'middle',
                timer: 3500
            })
        }
    });
}

const submitAddQuiz = () => {  
    let data = {
        _action: 'add_quiz',
        question_id: currentQuestionId.value,
        expired_time: expireTime.value,
        // course_date: moment().format('YYYY-MM-DD'),
    };
    postData(data);
}

const submitDelQuiz = (quiz_id: number) => {
    let data = {
        _action: 'del_quiz',
        quiz_id: quiz_id,
    };
    postData(data);
}

const reloadPage = () => {
    router.reload();
};


/* created */


let params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams: URLSearchParams, prop: string) => searchParams.get(prop),
});


onMounted(() => {
    let tmp_topic_id = params?.t_id as string;
    if (tmp_topic_id === undefined) {
        topicId.value = null;
    } else {
        topicId.value = _.toNumber(tmp_topic_id);
    }
});

console.log(props.topic);
console.log(props.questions);
console.log(props.q_options);

</script>

<style scoped>
td, th {
    border: 1px solid black;
    padding: 0.5rem 0.5rem;
}
</style>
