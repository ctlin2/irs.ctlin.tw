<template lang="pug">

Head(title="主題管理")

.w-screen.h-screen.flex.flex-col.items-center.p-4.gap-4
    .flex.justify-center.p-4.text-2xl.bg-cyan-400(class="w-2/3") 主題管理
    .flex.flex-col.p-4.gap-4(class="w-2/3 border")
        .flex.gap-4
            PrimaryButton(@click="changeTopic") 修改當前主題
            PrimaryButton(@click="delTopic" class="!bg-red-500") 刪除當前主題
        .flex.gap-4
            | 當前主題:
            div(v-for="(item, index) in select_topic_ids")
                | {{ getTopicName(item) }} /
        .flex.gap-4
            TopicItem(:level="0" :topic-items="top_level_topics"
                :selectedId="select_topic_ids[0]"
                @onClickTopicItem="onSelectTopic" @onClickAddTopic="addTopic")
            TopicItem(v-for="(item, index) in nextChildLevel"
                :selected-id="select_topic_ids[item]"
                :level="item" :topic-items="filterLevelTopic(item, select_topic_ids[index])"
                @onClickTopicItem="onSelectTopic" @onClickAddTopic="addTopic")

</template>

<script setup>
import { ref, reactive, computed, defineProps, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import _ from 'lodash';
import Swal from 'sweetalert2';
import TopicItem from "@/Components/teacher/TopicItem.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";


const props = defineProps({
    topics: {
        type: Array,
        required: true,
    },
});

/* data */

const select_topic_ids = reactive([]);


/* computed */
const topics = computed(() => props.topics);
const top_level_topics = computed(() => _.filter(topics.value, {level: 0}));
const childTopics = computed(() => _.filter(topics.value, item => item.level !== 0 && item.mark_id === _.get(select_topic_ids, 0)));
const nextChildLevel = computed(() => _.size(select_topic_ids));


/* methods */

const getTopicName = (t_id) => _.get(_.find(topics.value, {id: t_id}), 'name');
const filterLevelTopic = (level, parent_id) => _.filter(childTopics.value, {level: level, parent_id: parent_id});
const onSelectTopic = (level, t_id) => {
    _.pullAt(select_topic_ids, _.range(level, _.size(select_topic_ids)));
    select_topic_ids.push(t_id);
}

const addTopic = (level) => {
    let parent_id = _.get(select_topic_ids, level - 1, null);

    Swal.fire({
        title: '請輸入主題名稱',
        input: 'text',
        inputValidator(inputValue, validationMessage) {
            if (inputValue === '')
                return '主題名稱不可為空';
        },
        showCancelButton: true,
    }).then((res) => {
        if (res.isConfirmed) {
            submitAddTopic(parent_id, res.value);
        }
    });
}

const changeTopic = () => {
    Swal.fire({
        title: '請輸入要修改的主題名稱',
        input: 'text',
        inputValidator(inputValue, validationMessage) {
            if (inputValue === '')
                return '主題名稱不可為空';
        },
        showCancelButton: true,
    }).then((res) => {
        if (res.isConfirmed) {
            submitUpdateTopic(_.last(select_topic_ids), res.value);
        }
    })
}

const delTopic = () => {
    Swal.fire({
        icon: "question",
        title: '是否刪除主題',
        showCancelButton: true,
    }).then(res => {
        if (res.isConfirmed) {
            let del_t_id = _.last(select_topic_ids);
            submitDelTopic(del_t_id);
        }
    })
}


const submitAddTopic = (parent_id, t_name) => {
    useForm({
        _action: 'add_topic',
        parent_id: parent_id,
        t_name: t_name,
    }).post('/teacher/topic');
}

const submitUpdateTopic = (t_id, t_name) => {
    useForm({
        _action: 'change_topic',
        my_data: {
            id: t_id,
            name: t_name,
        }
    }).post('/teacher/topic');
}

const submitDelTopic = (t_id) => {
    let level = _.size(select_topic_ids) - 2;
    let parent_id = _.get(select_topic_ids, level);
    useForm({
        _action: 'del_topic',
        id: t_id,
    }).post('/teacher/topic', {
        onFinish: () => {
            if (level !== 0)
                onSelectTopic(level, parent_id);
                console.log(level, parent_id);
        }
    });
}


/* created */

console.log(props.topics);
console.log(top_level_topics.value);

</script>

<style scoped>
</style>