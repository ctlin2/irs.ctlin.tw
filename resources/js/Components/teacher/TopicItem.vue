<template lang="pug">
.border.border-black.rounded-md.flex.flex-col(style="width: 150px;")
    //- header
    .text-center.px-4.py-2
        | Topic Level {{ level }}
    //- body
    .flex-1.border-y.p-4
        .topic-item(v-for="(item, index) in topicItems" @click="onClickItem(item)"
            :class="[{'bg-blue-400 text-white': item.id === selectedId}]")
            | {{ item.name }}
    //- footer
    .flex.justify-center.px-4.py-2(@click="onClickAddItem")
        | +
</template>

<script setup lang="ts">
import { computed, defineProps, defineEmits } from 'vue';


const emit = defineEmits<{
    onClickTopicItem: [level: number, t_id: number],
    onClickAddTopic: [level: number],
}>();

const props = defineProps<{
    level: number,
    topicItems: object[],
    selectedId: number,
}>();

/* computed */

const level = computed<number>(() => props.level);

/* methods */

const onClickItem = (item: object) => emit('onClickTopicItem', level.value, item.id);

const onClickAddItem = () => emit('onClickAddTopic', level.value);

</script>

<style scoped>
.topic-item {
    @apply border border-black px-4 py-2 w-full first:rounded-t-md last:rounded-b-md;
    margin-top: -1px;
}
</style>