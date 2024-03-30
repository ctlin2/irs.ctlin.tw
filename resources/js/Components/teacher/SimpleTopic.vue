<template lang="pug">

.flex.gap-2.items-center
    .px-4.py-2(@click="onClickClearTopic" class="hover:bg-blue-400 cursor-pointer") 主題:
    div(v-if="nextChildLevel === 0") 無主題
    template(v-for="(item, index) in selectedTopicIds")
        .px-4.py-2(@click="onClickCurrentTopic(index)" class="hover:bg-blue-400 cursor-pointer")
            | {{ getTopicName(item) }} /

    select(class="appearance-none rounded-md" :value="selectedTopicIds[nextChildLevel]" @change="onSelectTopic($event, nextChildLevel)")
        option(v-for="(item, index) in filterEqualLevelTopic(nextChildLevel, selectedTopicIds[nextChildLevel - 1])" :value="item.id")
            | {{ item.name }}

</template>

<script setup lang="ts">
import {reactive, computed, defineProps, defineEmits, watch, ref} from "vue";
import * as _ from "lodash";
import { Topic } from '@/Components/teacher/UtilsType';


const emit = defineEmits(['update:modelValue']);

/* props */

const props = defineProps({
    modelValue: {
        type: Number,
    },
    topic: {
        type: Array<Topic>,
        required: true,
    },
});

/* data */

const selectedTopicIds: number[] = reactive([]);

/* computed */

const topics = computed<Array<Topic>>(() => props.topic);
const topLevelTopics = computed<Array<Topic>>(() => _.filter(topics.value, {level: 0}));
const childTopics = computed<Topic[]>(() => _.filter(topics.value, item => item.level !== 0 && item.mark_id === _.get(selectedTopicIds, 0)));
const nextChildLevel = computed<number>(() => _.size(selectedTopicIds));
const currentSelectedLevel = computed(() => _.size(selectedTopicIds) - 1);

/* methods */

const getTopicName = (t_id: number): string => _.get(_.find(topics.value, {id: t_id}), 'name');
const filterEqualLevelTopic = (level: number, parent_id: number): Array<Topic> => {
    if (parent_id === undefined) {
        return topLevelTopics.value;
    } else {
        return _.filter(childTopics.value, {level, parent_id});
    }
}
const onSelectTopic = (event: Event, level: number): void => {
    _.pullAt(selectedTopicIds, _.range(level, _.size(selectedTopicIds)));
    let id = _.toNumber((event.target as HTMLSelectElement).value);
    selectedTopicIds.push(id);
    emit('update:modelValue', id);
}

const onClickCurrentTopic = (level: number): void => {
    _.pullAt(selectedTopicIds, _.range(level + 1, _.size(selectedTopicIds)));
    emit('update:modelValue', _.last(selectedTopicIds));
}

const onClickClearTopic = (): void => {
    selectedTopicIds.splice(0);
    emit('update:modelValue', null);
}

const getTopicParentsIds = (currentId: number): number[] => {
    let info: Topic = _.find(topics.value, {id: currentId});
    let selectedIds: Array<Topic> = [];
    if (info === undefined)
        return [];
    _.set(selectedIds, info.level, info);
    _.forEach(_.range(0, info.level), (index) => {
        let t_info: Topic = _.find(topics.value, {id: _.get(selectedIds, [info.level - index, 'parent_id'])});
        _.set(selectedIds, info.level - (index + 1), t_info);
    });
    return _.map(selectedIds, 'id');
}

const recreateSelectIds = () => {
    let tmp_ids = getTopicParentsIds(props.modelValue);
    selectedTopicIds.splice(0);
    selectedTopicIds.splice(0, 0, ...tmp_ids);
}

/* created */

recreateSelectIds();

/* watched */

watch(() => props.modelValue, () => {
    recreateSelectIds();
});

</script>

<style scoped>

</style>