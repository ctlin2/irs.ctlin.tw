<template lang="pug">

.flex.flex-col.p-4.gap-2.border.border-black.rounded-md
    SimpleTopic(:topic="topics" v-model="topic")
    .flex.flex-col
        .flex.items-center.justify-between
            div 題目：
            PrimaryButton(type="button" @click="isSourceEditing = !isSourceEditing")
                | {{ isSourceEditing ? '視覺編輯' : 'HTML 原始碼' }}
        textarea(v-if="isSourceEditing" v-model="question.name" spellcheck="false" aria-label="HTML 原始碼").w-full.min-h-64.resize-y.rounded-md.font-mono.text-sm
        CKEditor(v-else).rounded-md(style="width: 100%;" :editor="ClassicEditor" :config="editorConfig" v-model="question.name")
        img(v-show="question.media_type == 'image'" :src="url" style="max-width:700px;").w-128
        //- img added by C.T.Lin //  
    .flex 
        PrimaryButton(v-show="question.question_type_id === 1" @click="onClickAddOption") add option
    .flex.flex-col.gap-2(v-for="(item, index) in options")
        | 選項 {{ index + 1 }}
        CKEditor.rounded-md(style="width: 100%;" :editor="ClassicEditor" :config="optionEditorConfig" :model-value="toOptionEditorHtml(item.name)" @update:model-value="item.name = $event")
        .flex.items-center.justify-end.gap-2
            label.flex.gap-2.shrink-0
                div 正確答案
                TextInput(type="checkbox" :value="item.id" name="isCorrect" v-model="option_indices" :checked="item.is_correct" @click="toggleChecked(item)")
            PrimaryButton(@click="onClickDelOption(item.id, index)" class="!bg-red-500 shrink-0")
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
import { Ckeditor as CKEditor } from '@ckeditor/ckeditor5-vue';
import {
    Autoformat,
    BlockQuote,
    Bold,
    CKBox,
    CKFinder,
    CKFinderUploadAdapter,
    ClassicEditor,
    CloudServices,
    EasyImage,
    Essentials,
    Font,
    GeneralHtmlSupport,
    Heading,
    Image,
    ImageCaption,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    Indent,
    Italic,
    Link,
    List,
    MediaEmbed,
    Paragraph,
    PasteFromOffice,
    PictureEditing,
    SourceEditing,
    Table,
    TableToolbar,
    TextTransformation,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
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
const editorConfig = {
    licenseKey: 'GPL',
    plugins: [
        Essentials, CKFinderUploadAdapter, Paragraph, Heading, Autoformat, Bold, Italic,
        BlockQuote, Image, ImageCaption, ImageStyle, ImageToolbar, ImageUpload, CloudServices,
        CKBox, CKFinder, EasyImage, List, Indent, Link, MediaEmbed, PasteFromOffice,
        Table, TableToolbar, PictureEditing, TextTransformation, Font, GeneralHtmlSupport,
    ],
    toolbar: {
        items: [
            'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', '|', 'fontColor',
            'link', 'uploadImage', 'insertTable', 'blockQuote', 'mediaEmbed', '|',
            'bulletedList', 'numberedList', 'outdent', 'indent',
        ],
    },
    fontColor: {
        colorPicker: { format: 'hex' },
    },
    htmlSupport: {
        allow: [{ name: /.*/, attributes: true, classes: true, styles: true }],
    },
    image: {
        toolbar: [
            'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
            'toggleImageCaption', 'imageTextAlternative',
        ],
    },
    table: {
        contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
    },
    language: 'en',
};
const optionEditorConfig = {
    licenseKey: 'GPL',
    plugins: [Essentials, Paragraph, Bold, Italic, Font, SourceEditing],
    toolbar: ['sourceEditing', '|', 'bold', 'italic', 'fontColor'],
    fontColor: {
        colorPicker: { format: 'hex' },
    },
};
const isSourceEditing = ref(false);

const toOptionEditorHtml = (name: string): string => {
    const value = name ?? '';
    if (/^\s*<(?:p|h[1-6]|span|strong|b|em|i|u|s|code|a|ul|ol|li|blockquote|figure|table|pre|div|br)\b/i.test(value)) {
        return value;
    }

    const escapedValue = value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    return `<p>${escapedValue}</p>`;
};

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