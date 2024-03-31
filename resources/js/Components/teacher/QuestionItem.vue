<template lang="pug">

.flex.flex-col.p-4.gap-2.border.border-black.rounded-md
    SimpleTopic(:topic="topics" v-model="topic")
    .flex.flex-col
        div 題目：
        textarea.rounded-md(style="width: 700px; height: 80px;" v-model="question.name")
        img(v-show="question.media_type == 'image'" :src="url" style="max-width:700px;")
        //- img added by C.T.Lin //  
    .flex 
        PrimaryButton(@click="onClickAddOption") add option
    .flex.flex-col.gap-2(v-for="(item, index) in options")
        | 選項 {{ index + 1 }}
        .flex.items-center.gap-2
            textarea.rounded-md(style="width: 600px; height: 60px;" v-model="item.name" )
            label.flex.gap-2
                div 正確答案
                TextInput(type="checkbox" :value="1" name="isCorrect" v-model="item.is_correct" :checked="item.is_correct")
            PrimaryButton(@click="onClickDelOption(item.id, index)" class="!bg-red-500")
                | 刪除

    .flex.gap-4
        PrimaryButton(@click="onSave") save
        PrimaryButton(v-if="showDelBtn" @click="onDelete" class="!bg-red-500") delete

</template>

<script setup lang="ts">
import { toRefs, computed, defineProps, defineEmits } from 'vue';
import SimpleTopic from "@/Components/teacher/SimpleTopic.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Topic, Question as QuesType, QOption } from './UtilsType';


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

/* computed */

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

const onSave = () => emit('save');
const onDelete = () => emit('delete', question.value.id);

</script>

<style scoped>

</style>