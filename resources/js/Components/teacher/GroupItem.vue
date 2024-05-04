<template lang="pug">
.flex.flex-col.border.border-gray-400.rounded-md(:class="[{'border-2 border-red-500': group_id === select_group}]")
    // - header
    .group.relative.rounded-t-md.px-4.py-2.text-xl.border-b.text-center.cursor-pointer.select-none(class="hover:bg-gray-300" @click="click_group")
        | 第 {{ group_no }} 組
        span.absolute.invisible.flex.gap-4(class="top-[10px] right-[10px] group-hover:visible")
            // - 刪除組員
            span(@click.stop="del_member")
                svg(xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6")
                    path(d="M10.375 2.25a4.125 4.125 0 100 8.25 4.125 4.125 0 000-8.25zM10.375 12a7.125 7.125 0 00-7.124 7.247.75.75 0 00.363.63 13.067 13.067 0 006.761 1.873c2.472 0 4.786-.684 6.76-1.873a.75.75 0 00.364-.63l.001-.12v-.002A7.125 7.125 0 0010.375 12zM16 9.75a.75.75 0 000 1.5h6a.75.75 0 000-1.5h-6z")
            // - 新增組員
            span(@click.stop="add_member")
                svg(xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6")
                    path(d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z")
    // - body
    .p-4.grid.grid-cols-2.gap-4(class="min-w-[15.6em]")
        .border.text-center.bg-gray-100.cursor-pointer.select-none(v-for="(item, index) in group_std" @click="click_std(item.id)"
            :class="['hover:bg-gray-300', {'border-2 border-cyan-400': has_select_std(item.id)}, {'text-red-500': is_absent(item.status)}]")
            | {{ item.std_no }} #[br] {{ item.std_name }}
    // - footer
    .flex.justify-center.px-4.py-2.rounded-b-md.border-t
        .flex.items-center.gap-4
            .flex-1.border.px-2.py-1.text-center.cursor-pointer.rounded-md(class="hover:bg-gray-100" @click="sub_point")
                svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 stroke-red-500")
                    path(stroke-linecap="round" stroke-linejoin="round" d="M18 12H6")
            .flex-1.text-center {{ point }}
            .flex-1.border.px-2.py-1.text-center.cursor-pointer.rounded-md(class="hover:bg-gray-100" @click="add_point")
                svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 stroke-blue-500")
                    path(stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15")

</template>

<script setup>
import { ref, computed, defineProps, defineEmits } from 'vue';
import _ from 'lodash';


const emit = defineEmits(['onClickStd', 'onClickGroup', 'addMember', 'delMember', 'addPoint', 'subPoint']);

const props = defineProps({
    group_info: {
        type: Object,
    },
    group_std: {
        type: Array,
    },
    select_group: {
        type: Number,
    },
    select_std: {
        type: Array,
    },
});

/* data */


/* computed */

const group_id = computed(() => props.group_info.id);
const group_no = computed(() => props.group_info.no);
const point = computed(() => props.group_info.g_point);

/* methods */

const add_point = () => emit('addPoint', props.group_info);
const sub_point = () => emit('subPoint', props.group_info);

const has_select_std = (std_id) => _.includes(props.select_std, std_id);
const is_absent = (std_status) => std_status === 4; // TODO
const click_std = (std_id) => emit('onClickStd', std_id);

const click_group = () => emit('onClickGroup', group_id.value);

const add_member = () => emit('addMember', group_id.value);
const del_member = () => emit('delMember', group_id.value);

/* created */


</script>

<style scoped>

</style>