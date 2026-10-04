<template lang="pug">

Head(title="題庫管理")

BaseLayout
    template(#left)
        .flex
            select(v-model="currentQuestionInfo.question_type_id" @change="changeQuestinoType").appearance-none.rounded-md.px-1.py-1.gap-1.flex-1
                option(v-for="(item, index) in question_types" :value="item.id" :selected="item.id === currentQuestionInfo.question_type_id")
                    | {{ item.name }}
            PrimaryButton(@click="onClickAddQuestion") 新增題目
        .border.border-white.border-collapse.overflow-y-auto.h-full
            .bg-white.px-4.py-2.border-y.border-gray-400.select-none.flex.justify-between.truncate(v-for="(item, index) in questions"
                :class="['hover:bg-gray-200', {'from-cyan-400 to-10% to-transparent bg-gradient-to-r': currentQuestionId === item.id}, ]"
                @click="onClickSelectedQuestion(item.id)")
                .flex
                    | {{ item.name }}

    .flex-1.flex.flex-col.overflow-y-auto.max-h-screen
        .bg-cyan-400.flex(class="h-[52px]")
        .flex-1.flex.flex-col.p-4.gap-4
            SimpleTopic(:topic="topics" v-model="topicId")
            div
                PrimaryButton(@click="onClickSearch") search
            div
                QuestionItem(:topics="topics" :question="currentQuestionInfo" :options="currentQOptions"
                    @addOption="onAddQOption" @delOption="onDelQOption" @save="saveData" @delete="submitDelData" @changeTopic="changeCurrentQTopic")
</template>

<script setup lang="ts">
import {ref, reactive, computed, defineProps, watch, onMounted, toRefs} from 'vue';
import {Head, router, useForm} from '@inertiajs/vue3';
import * as _ from 'lodash';
import BaseLayout from "@/Layouts/BaseLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SimpleTopic from "@/Components/teacher/SimpleTopic.vue";
import QuestionItem from "@/Components/teacher/QuestionItem.vue";
import { Topic, Question as QuesType, QOption } from '@/Components/teacher/UtilsType';
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
});

/* data */

const topicId = ref<number>(null);
// const selected_question_type = ref(1);
// const isCreated = ref(false);
const currentQuestionId = ref<number>();
const fixed_question_type_id = ref<number>(1);
const currentQuestionInfo = ref<QuesType>({
    id: null,
    name: '',
    topic_id: null,
    question_type_id: 1,
    answer: null,
});
const currentQOptions = ref<Array<QOption>>([]);
const delQOptions = reactive<Array<number>>([]);
// const questions = toRefs(props.questions);
// const q_options = toRefs(props.q_options);
const question_types = ref<Array<Object>>([
    { id: 1, name: '選擇題' },
    { id: 3, name: '填充題' },
]);

/* computed */

const topics = computed<Array<Topic>>(() => props.topic);
const questions = computed<Array<QuesType>>(() => props.questions);
const q_options = computed<Array<QOption>>(() => props.q_options);

/* methods */

const onClickSearch = () => {
    if (topicId.value === null) {
        router.get('/teacher/question');
    } else {
        router.get('/teacher/question', {
            t_id: topicId.value,
        });
    }
}

const filterQuestionOptions = (q_id: number) => _.filter(q_options.value, {question_id: q_id});

const onClickSelectedQuestion = (val: number): void => {
    currentQuestionId.value = val;
    currentQuestionInfo.value = _.cloneDeep(_.find(questions.value, {id: val}));
    fixed_question_type_id.value = currentQuestionInfo.value.question_type_id;
    currentQOptions.value = _.cloneDeep(filterQuestionOptions(val));
    delQOptions.splice(0);
}

const changeCurrentQTopic = (topic_id: number): void => {
    currentQuestionInfo.value.topic_id = topic_id;
}

// C.T.Lin
const changeQuestinoType = () => {
    // in editing mode, can't change question_type_id, so recover it.
    if (currentQuestionInfo.value.question_type_id !== fixed_question_type_id.value &&
        currentQuestionInfo.value.id !== null){
        currentQuestionInfo.value.question_type_id = fixed_question_type_id.value;
    }
    console.log('current question type', currentQuestionInfo.value.question_type_id);
}

const onClickAddQuestion = (): void => {
    currentQuestionInfo.value = {
        id: null,
        name: '',
        topic_id: topicId.value,
        question_type_id: 1,
        answer: null,
    }
    currentQuestionId.value = null;
    currentQOptions.value = [];
    delQOptions.splice(0);
    // isCreated.value = true;
}

const onAddQOption = (val: QOption): void => {
    currentQOptions.value.push(val);
}

const onDelQOption = (q_option_id: number, index: number): void => {
    if (q_option_id !== null) {
        delQOptions.push(q_option_id);
    }
    currentQOptions.value.splice(index, 1);
}

const postData = (data: object): void => {
    console.log(data);
    useForm(data).post('/teacher/question', {  // modified by C.T.Lin
        onError: (p) => {
            console.log('onError /teacher/question:')
            console.log(p)
            alert(p.errors)
        },
        onSuccess: () => {
            Swal.fire({
                text: '儲存完成',
                icon: 'success',
                toast: true,
                showConfirmButton: false,
                position: 'center',
                timer: 3500
            });
            if (currentQuestionInfo.value.id === null) { //creating, Added by C.T.Lin
                onClickAddQuestion();
            }
             
        },
    });
}

const submitCreateData = (): void => {
    let data = {
        _action: 'add_question',
        question: currentQuestionInfo.value,
        q_options: currentQOptions.value,
    }
    postData(data);
}

const submitChangeData = (): void => {
    let data = {
        _action: 'change_question',
        question: currentQuestionInfo.value,
        q_options: currentQOptions.value,
        del_options: delQOptions,
    }
    postData(data);
}

const submitDelData = (q_id: number): void => {
    let data = {
        _action: 'del_question',
        q_id: q_id,
    }
    postData(data);
    onClickAddQuestion(); // Added by C.T.Lin
}

const saveData = () => { // update or create, depending on currentQuestionId
    if (currentQuestionInfo.value.id === null) { // isCreated.value
        submitCreateData();
    } else {
        submitChangeData();
    }
}


watch(topicId, () => {
    console.log('current topic id', topicId.value);
});


/* created */


let params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams: URLSearchParams, prop: string) => searchParams.get(prop),
});


onMounted(() => {
    let tmp_topic_id = params?.t_id;
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

</style>