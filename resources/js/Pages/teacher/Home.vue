<template lang="pug">

Head(title="上課")

BaseLayout
    template(#left)
        .flex.justify-center.border-2.border-white.p-1.text-white.text-3xl
            .flex-1.flex.justify-center.border-2.border-white.p-4.text-white.text-2xl
                | {{ class_info.course_name }}
        .flex.justify-center.p-4.text-white.text-2xl
            | 班級:{{ class_info.class_name }}
        .flex.justify-end.items-center.px-2
            div
                .text-red-300.cursor-pointer.px-2(v-if="is_multiple" @click="disable_multiple").
                    單選
                .text-blue-300.cursor-pointer.px-2(v-else @click="enable_multiple").
                    多選
        .border.border-white.border-collapse.overflow-y-auto(style="max-height: 700px;")
            .bg-white.px-4.py-2.border-y.border-gray-400.select-none.flex.justify-between(v-for="(item, index) in students"
                :class="['hover:bg-gray-200', {'from-cyan-400 to-10% to-transparent bg-gradient-to-r': has_in_select_std(item.id)}, { 'text-slate-500': has_group(item)}, ]"
                @click="set_current_std(item.id)")
                .flex
                    | {{ item.std_no }} {{ item.std_name }}
                .flex
                    span.cursor-pointer(@click.stop="change_s_status(item)") {{ std_resultMap[item.status]}}
                .flex.gap-2
                    // - 減分
                    span.cursor-pointer(@click.stop="sub_s_point(item)")
                        svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 stroke-red-500")
                            path(stroke-linecap="round" stroke-linejoin="round" d="M18 12H6")
                    span
                        | {{ item.s_point }}
                    // - 加分
                    span.cursor-pointer(@click.stop="add_s_point(item)")
                        svg(xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 stroke-blue-500")
                            path(stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15")

    .flex-1.flex.flex-col.overflow-y-auto.max-h-screen
        .bg-cyan-400.flex(class="h-[52px]")
        .flex-1.flex.flex-col.p-4.gap-4
            .flex.flex-wrap.gap-4
                PrimaryButton(@click="add_group") 新增組別
                PrimaryButton(@click="del_group" class="!bg-red-400") 刪除組別
                PrimaryButton(@click="sample_group") 抽組別
                PrimaryButton(@click="sample_std") 抽學生
                PrimaryButton(@click="sample_group_std") 抽組別學生
                .flex.gap-2
                    .flex.justify-center.items-center
                        | 加扣分：
                    TextInput.text-center(v-model="point_step" @change="change_point_step" style="width: 100px;")
                PrimaryButton(@click="toQuizPage") 出題測驗
                PrimaryButton(@click="toRankingPage") 排行榜
            //-.grid.grid-cols-5.gap-4
            .flex.flex-row.flex-wrap.gap-4
                GroupItem(v-for="(item, index) in groups" :group_info="item" :group_std="filter_students(item.group_id)"
                    :select_group="select_group_id" :select_std="select_std_id"
                    @onClickStd="set_current_std" @onClickGroup="set_current_group"
                    @addMember="add_member" @delMember="del_member"
                    @addPoint="add_g_point" @subPoint="sub_g_point")
</template>

<script setup>
import {Head, useForm, router} from '@inertiajs/vue3';
import { ref, reactive, computed, defineProps } from 'vue';
import _ from 'lodash';
import BaseLayout from "@/Layouts/BaseLayout.vue";
import GroupItem from "@/Components/teacher/GroupItem.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";


const props = defineProps({
    course: {
        type: Object,
    },
    students: {
        type: Array,
    },
    groups: {
        type: Array,
    },
});

/* data */

const is_multiple = ref(true);
const select_std_id = reactive([]);
const select_group_id = ref();

/* computed */

const class_info = computed(() => props.course);
const point_step = computed(() => class_info.value.def_point_step);
const students = computed(() => props.students);
// const students = computed(() => {
//     return _.transform(_.range(1,10), (res, item, index) => {
//         res.push({
//             id: item, // This id is s_points table id.
//             std_no: `4120E0${_.padStart(_.toString(item), 2, '0')}`,
//             std_name: '王小明',
//             group_id: 1,
//             course_id: 1,
//         });
//     }, []);
// });
const groups = computed(() => props.groups);
// const groups = computed(() => {
//     return _.transform(_.range(1, 3), (res, item) => {
//         res.push({
//             id: item,
//             group_id: item,
//             course_id: 1,
//             no: item,
//         });
//     }, []);
// });

const all_std_id = computed(() => _.map(students.value, 'id'));
const all_group_id = computed(() => _.map(groups.value, 'id'));

const std_status="出席,遲到,早退,遲到&早退,缺席";
const std_status_values=_.split(std_status,',');
const std_resultMap = _.zipObject(_.range(std_status_values.length), std_status_values);
// const std_status_map=computed(()=>_.)

/* methods */

const has_in_select_std = (std_id) => _.includes(select_std_id, std_id);
const has_group = (item) => item.group_id !== null;
const enable_multiple = () => {
    select_std_id.splice(0);
    is_multiple.value = true;
}
const disable_multiple = () => {
    select_std_id.splice(0);
    is_multiple.value = false;
}
const filter_students = (group_id) => _.filter(students.value, {group_id});

const set_current_std = (std_id) => {
    if (is_multiple.value) {
        if (_.includes(select_std_id, std_id)) {
            _.pull(select_std_id, std_id);
        } else {
            select_std_id.push(std_id);
        }
    } else {
        if (_.includes(select_std_id, std_id)) {
            _.pull(select_std_id, std_id);
        } else {
            select_std_id.splice(0);
            select_std_id.push(std_id);
        }
    }
}
const set_current_group = (group_id) => select_group_id.value = group_id;
/**
 * 抽組別
 */
const sample_group = () => {
    let delay_time = 300;
    _.forEach(all_group_id.value, (item, index) => {
        _.delay(() => {
            set_current_group(item);
        }, index * delay_time);
    });
    _.delay(() => {
        set_current_group(_.sample(all_group_id.value));
    }, _.size(all_group_id.value) * delay_time);
}

/**
 * 抽學生
 */
const sample_std = () => {
    let delay_time = 300;
    select_std_id.splice(0);
    _.forEach(all_std_id.value, (item, index) => {
        _.delay(() => {
            select_std_id.splice(0);
            select_std_id.push(item);
        }, index * delay_time);
    });
    _.delay(() => {
        select_std_id.splice(0);
        select_std_id.push(_.sample(all_std_id.value));
    }, _.size(all_std_id.value) * delay_time);
}
/**
 * 抽當前組別中的學生
 */
const sample_group_std = () => {
    let delay_time = 300;
    let group_std_ids = _.map(filter_students(_.find(groups.value, {id: select_group_id.value}).group_id), 'id');
    _.forEach(group_std_ids, (item, index) => {
        _.delay(() => {
            select_std_id.splice(0);
            select_std_id.push(item);
        }, index * delay_time);
    });
    _.delay(() => {
        select_std_id.splice(0);
        select_std_id.push(_.sample(group_std_ids));
    }, _.size(group_std_ids) * delay_time);
}

const add_s_point = (item) => change_std_point(item.id, item.s_point + point_step.value);
const sub_s_point = (item) => change_std_point(item.id, item.s_point - point_step.value);
const add_g_point = (item) => change_group_point(item.id, item.g_point + point_step.value);
const sub_g_point = (item) => change_group_point(item.id, item.g_point - point_step.value);

const change_s_status = (item) => change_std_status(item.id, item.status+1);

/**
 * 新增組別 - 會發送 API
 */
const add_group = () => {
    let tmp_group = _.map(groups.value, 'no');
    let max = _.max(tmp_group)??0; // modified by C.T.Lin
    let no = _.get(_.sortBy(_.pullAll(_.range(1, max), tmp_group)), '0', max + 1);
    useForm({ 
        _action: 'add_group',
        no: no,
    }).post('/teacher/home');
}

/**
 * 刪除組別 - 會發送 API
 */
const del_group = () => {
    if (_.isNumber(select_group_id.value)) {
        useForm({
            _action: 'del_group',
            g_point_id: select_group_id.value,
        }).post('/teacher/home');
    }
}

/**
 * 新增組員 - 會發送 API
 * @param group_id
 */
const add_member = (group_id) => {
    console.log('add member', select_std_id, 'to group', group_id);
    if (_.size(select_std_id) !== 0) {
        useForm({
            _action: 'add_member',
            s_point_ids: select_std_id,
            g_point_id: group_id,
        }).post('/teacher/home', {
            onFinish: () => disable_multiple(),
        });
    }
}

/**
 * 刪除組員 - 會發送 API
 * @param group_id
 */
const del_member = (group_id) => {
    console.log('del member', select_std_id, 'to group', group_id);
    if (_.size(select_std_id) !== 0) {
        useForm({
            _action: 'del_member',
            s_point_ids: select_std_id,
            g_point_id: group_id,
        }).post('/teacher/home', {
            onFinish: () => disable_multiple(),
        });
    }
}

const change_point_step = (event) => {
    useForm({
        _action: 'update',
        my_data: {
            id: class_info.value.id,
            def_point_step: event.target.value,
        }
    }).post('/teacher/course');
}

const change_group_point = (g_id, point) => {
    useForm({
        _action: 'change_group_point',
        g_point_id: g_id,
        g_point_value: point,
    }).post('/teacher/home');
}

const change_std_point = (s_id, point) => {
    useForm({
        _action: 'change_member_point',
        s_point_id: s_id,
        s_point_value: point,
    }).post('/teacher/home');
}

const change_std_status = (s_id,status) => {
    // ...
  let n_status = (status>4)?0:status;
  useForm({
    _action: 'change_member_status',
    s_point_id: s_id,
    status: n_status
  }).post('/teacher/home');
}

const toQuizPage = () => {
    let course_id = students.value[0].course_id;
    let course_date = students.value[0].course_date;
    // console.log(`course_id = ${course_id}, course_date = ${course_date}`)
    router.get('/teacher/quiz', {
        // course_id: course_id,
        // course_date: course_date,
    });
}

const toRankingPage = () => {
    // console.log(`course_id = ${course_id}, course_date = ${course_date}`)
    router.get('/teacher/ranking_list', {
        // course_id: course_id,
        // course_date: course_date,
    });
}

/* created */

console.log(students.value);
console.log(groups.value);
console.log(props.students);
console.log(props.groups);

</script>

<style scoped>

</style>