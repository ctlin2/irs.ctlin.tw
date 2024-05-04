<template lang="pug">

.flex.flex-col.p-4.gap-2.border.border-black.rounded-md
    SimpleTopic(:topic="topics" v-model="topic")
    .flex.flex-col
        div 題目：
        textarea.rounded-md.resize-x(style="width: 100%; height: 80px;" v-model="question.name")
        img(v-show="question.media_type == 'image'" :src="url" style="max-width:700px;").w-128
        //- img added by C.T.Lin //  
    .flex 
        PrimaryButton(v-show="question.question_type_id === 1" @click="onClickAddOption") add option
    .flex.flex-col.gap-2(v-for="(item, index) in options")
        | 選項 {{ index + 1 }}
        .flex.items-center.gap-2
            textarea.rounded-md.resize-x(style="width: calc(100% - 170px); height: 60px;" v-model="item.name" )
            label.flex.gap-2
                div 正確答案
                TextInput(type="checkbox" :value="item.id" name="isCorrect" v-model="option_indices" :checked="item.is_correct" @click="toggleChecked(item)")
            PrimaryButton(@click="onClickDelOption(item.id, index)" class="!bg-red-500")
                | 刪除
    InputLabel(v-if="question.question_type_id === 3" value="填充題答案:(使用正規表示法，如^pattern1$|^pattern2$)").text-lg
    TextInput(v-if="question.question_type_id === 3" v-model="question.answer")
    .flex.gap-4
        PrimaryButton(@click="onSave") 
            | {{ upateOrCreate }}
        PrimaryButton(v-if="showDelBtn" @click="onDelete" class="!bg-red-500") delete

</template>

<script setup lang="ts">
import { ref, toRefs, computed, defineProps, defineEmits } from 'vue';
import SimpleTopic from "@/Components/teacher/SimpleTopic.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Topic, Question as QuesType, QOption } from './UtilsType';
import Question from '@/Pages/teacher/Question.vue';


const emit = defineEmits(['addOption', 'delOption', 'save', 'delete', 'changeTopic']);


const props = defineProps({
    topics: {
        type: Array<Topic>,
    },
    question: {
        type: Object,
    },
    options: {
        type: Array<QOption>,
    },
});

/* data */

const { options } = toRefs(props);
// const topic = toRef(props.question.topic_id);
// const topic_id = toRef(props.question, 'topic_id');
const option_indices = ref([]); // C.T.Lin

/* computed */

const upateOrCreate = computed(() => question.value.id !== null ? 'update' : 'create');
const showDelBtn = computed(() => question.value.id !== null);
const question = computed<QuesType>(() => props.question as QuesType);
// const topic = computed<number>(() => question.value?.topic_id);
const topic = computed<number>({
    get: () => question.value?.topic_id,
    set: (val) => emit('changeTopic', val),
});

const url = computed(() => '/storage/images/' + question.value.media_url ); // added by C.T.Lin

/* methods */

const onClickAddOption = (): void => {
    emit('addOption', {
        id: null,
        name: '',
        is_correct: false,
    });
}

const onClickDelOption = (option_id: number, index: number): void => {
    emit('delOption', option_id, index);
}

const toggleChecked = (item:QOption) => { item.is_correct = !item.is_correct; }

const onSave = () => emit('save');
const onDelete = () => emit('delete', question.value.id);

</script>

<style scoped>

</style>