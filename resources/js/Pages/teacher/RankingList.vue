<template lang="pug">

Head(title="排行榜")

BaseLayout
    template(#left)
        .flex.justify-center.border-2.border-white.p-1.text-white.text-3xl
            .flex-1.flex.justify-center.border-2.border-white.p-4.text-white.text-3xl
                | {{ class_info.course_name }}
        .flex.justify-center.p-4.text-white.text-2xl
            | 班級:{{ class_info.class_name }}
        .border.border-white.border-collapse.overflow-y-auto(style="max-height: 700px;")
            .bg-white.px-4.py-2.border-y.border-gray-400.select-none.flex.justify-between(v-for="(item, index) in students")
                //- :class="['hover:bg-gray-200', {'from-cyan-400 to-10% to-transparent bg-gradient-to-r': has_in_select_std(item.id)}, ]"
                //- @click="set_current_std(item.id)")
                .flex
                    | {{ item.std_no }} {{ item.std_name }}

    .bg-cyan-400.flex(class="h-[52px]")
    .flex.gap-4.p-4
        select(class="appearance-none rounded-md" v-model="current_date")
            option(v-for="(item, index) in course_date_list" :value="item")
                | {{ item }}
        PrimaryButton(@click="onClickSearch") Search
    .flex.gap-4.p-4
        PrimaryButton(@click="is_show_group = true") 組別排行榜
        PrimaryButton(@click="is_show_group = false") 個人排行榜
    .p-4(v-if="is_show_group")
        table
            thead
                tr
                    th(v-for="(item, index) in group_table_title") {{ item }}
            tbody
                tr(v-for="(item, index) in groups")
                    td.text-center {{ index + 1 }}
                    td.text-center(v-for="(t_item, t_index) in group_table_val")
                        | {{ item[t_item] }}
    .p-4(v-else)
        table
            thead
                tr
                    th(v-for="(item, index) in std_table_title") {{ item }}
            tbody
                tr(v-for="(item, index) in students")
                    td.text-center {{ index + 1 }}
                    td.text-center(v-for="(t_item, t_index) in std_table_val")
                        | {{ item[t_item] }}

</template>

<script setup lang="ts">
import { ref, computed, defineProps } from "vue";
import { Head, router } from "@inertiajs/vue3";
import * as _ from "lodash";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Course, Student, Group } from "@/Components/teacher/UtilsType";


const props = defineProps<{
    course: Course,
    students: Array<Student>,
    groups: Array<Group>,
    course_date_list: Array<string>,
}>();

/* data */

const is_show_group = ref(false);
const current_date = ref();

const group_table_title = ref(['排名', '組別', '分數']);
const group_table_val = ref(['no', 'g_point']);
const std_table_title = ref(['排名', '姓名', '分數']);
const std_table_val = ref(['std_name', 's_point']);

/* computed */

const course_id = computed<number>(() => props.course.id);
// const course_date_list = computed<Array<string>>(() => {
//     return ['2023-09-19', '2023-09-20'];
// });
const course_date_list = computed<Array<string>>(() => _.sortBy(props.course_date_list));
const class_info = computed<Course>(() => props.course);

// const class_info = computed(() => {
//     return {course_name: '物聯網與應用實務', class_name: '資工一AC'}
// });

const students = computed<Array<Student>>(() => props.students);
const groups = computed<Array<Group>>(() => props.groups);

const table_data = computed(() => {

});

/* methods */

const onClickSearch = () => {
    router.get('/teacher/ranking_list', {
        course_id: course_id.value,
        course_date: current_date.value,
    });
}

/* created */


let params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams: URLSearchParams, prop: string) => searchParams.get(prop),
});

current_date.value = params.course_date;

</script>

<style scoped>
th, td {
    border: 1px solid black;
    padding: 0.5rem 1rem;
}
</style>