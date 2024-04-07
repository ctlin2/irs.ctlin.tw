<template>
    <Head title="課程管理" />

    <BaseLayout>
        <template #left>
            <div class="flex justify-center border-double border-2 border-white p-1 text-white text-3xl">
                <div class="flex-1 flex justify-center border-double border-2 border-white p-4 text-white text-3xl">
                    {{ current_course?.course_name ?? '尚未選擇' }}
                </div>
            </div>
            <div class="flex justify-center p-4 text-white text-2xl">
                班級：{{ current_course?.class_name ?? '尚未選擇' }}
            </div>
            <div class="border border-white border-collapse overflow-y-auto" style="max-height: 700px;">
                <div v-if="students.length !== 0" v-for="(item, index) in students" class="bg-white px-4 py-2 border-y border-gray-400">
                    {{ item.std_no }} {{ item.std_name }}
                </div>
                <div v-else class="bg-white px-4 py-2 border-y border-gray-400">
                    尚未上傳學生資料
                </div>
            </div>
        </template>

        <div class="h-[52px] bg-cyan-400 flex">
        </div>
        <div class="flex-1 flex flex-col p-4 gap-4">
            <div class="w-full flex items-center gap-4">
                <div class="w-1/3 flex">
                    <select class="appearance-none rounded-md px-2 py-1 flex-1" v-model="current_course_id">
                        <option v-for="(item, index) in courses" :value="item.id">
                            {{ item.course_name }} - {{ item.class_name }}
                        </option>
                    </select>
                </div>
                <div class="">
                    <PrimaryButton @click="add">新增課程</PrimaryButton>
                </div>
                <div class="">
                    <PrimaryButton @click="toQuestionPage">題庫管理</PrimaryButton>
                </div>
                <div class="">
                    <PrimaryButton @click="toTopicPage">主題管理</PrimaryButton>
                </div>
            </div>
            <div class="flex gap-4">
                <input type="date" class="appearance-none rounded-md" v-model="today" />
                <PrimaryButton @click="to_course_home_page">進入課程</PrimaryButton>
            </div>
            <template v-if="current_course_id">
                <form class="flex gap-4 w-1/3" @submit.prevent="upload_students">
                    <input type="file" class="flex-1" required @input="form_data.std_xls = $event.target.files" />
                    <PrimaryButton class="!bg-blue-400" type="submit">上傳</PrimaryButton>
                </form>
                <div class="flex flex-col">
                    課程名稱：
                    <TextInput type="text" v-model="current_course_info.course_name" />
                </div>
                <div class="flex flex-col">
                    班級名稱：
                    <TextInput type="text" v-model="current_course_info.class_name" />
                </div>
                <div class="flex gap-4">
                    <div class="flex flex-col">
                        學生預設點數：
                        <TextInput type="text" v-model="current_course_info.def_s_point" />
                    </div>
                    <div class="flex flex-col">
                        組別預設點數：
                        <TextInput type="text" v-model="current_course_info.def_g_point" />
                    </div>
                    <div class="flex flex-col">
                        加扣分點數：
                        <TextInput type="text" v-model="current_course_info.def_point_step" />
                    </div>
                    <div class="flex flex-col">
                        準時出席：
                        <TextInput type="text" v-model="current_course_info.att_status_1" />
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="flex flex-col">
                        遲到：
                        <TextInput type="text" v-model="current_course_info.att_status_2" />
                    </div>
                    <div class="flex flex-col">
                        早退：
                        <TextInput type="text" v-model="current_course_info.att_status_3" />
                    </div>
                    <div class="flex flex-col">
                        遲到&早退：
                        <TextInput type="text" v-model="current_course_info.att_status_4" />
                    </div>
                    <div class="flex flex-col">
                        缺席：
                        <TextInput type="text" v-model="current_course_info.att_status_5" />
                    </div>
                </div>
                <div>
                    <PrimaryButton @click="save">SAVE</PrimaryButton>
                </div>
            </template>
        </div>
<!--        </div>-->
    </BaseLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, defineProps, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import _ from 'lodash';
import moment from "moment";
import BaseLayout from "@/Layouts/BaseLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Course, Student } from '@/Components/teacher/UtilsType';

interface GradeCourse extends Course{
    def_g_point: number,
    def_s_point: number,
    def_point_step: number,
    att_status_1: number,
    att_status_2: number,
    att_status_3: number,
    att_status_4: number,
    att_status_5: number,
}

const props = defineProps({
    courses: {
        type: Array<GradeCourse>,
        required: true,
    },
    students: {
        type: Array<Student>,
        required: true,
    },
})

/* data */

const today = ref('');
const current_course_id = ref();
const current_course_info = ref({});
const form_data = useForm({
    _action: 'import_student',
    std_xls: null,
    course_id: current_course_id.value,
});

/* computed */

const current_course = computed(() => _.find(props.courses, {id: current_course_id.value}));
const students = computed(() => _.filter(props.students, {course_id: current_course_id.value}));
// const students = computed(() => {
//     return _.transform(_.range(1,10), (res, item, index) => {
//         res.push({
//             std_no: `4120E0${_.padStart(item + 1, 2, 0)}`,
//             std_name: '王小明',
//             groups_id: null,
//             class_id: 1,
//         });
//     }, []);
// });

/* methods */

const add = () => {
    useForm({
        _action: 'add'
    }).post('/teacher/course');
}

const save = () => {
    useForm({
        _action: 'update',
        my_data: current_course_info.value,
    }).post('/teacher/course');
}

const upload_students = () => {
    form_data.post('/teacher/course', {
        onError: () => alert('錯誤'),
        onFinish: () => alert('完成'),
    });
}

const to_course_home_page = () => {
    if (_.isNumber(current_course_id.value)) {
        router.get('/teacher/home', {
            course_id: current_course_id.value,
            course_date: today.value,
        });
    }
}

const toQuestionPage = () => {
    router.get('/teacher/question');
}

const toTopicPage = () => {
    router.get('/teacher/topic');
}

/* watch */

watch(current_course_id, (newValue) => {
    current_course_info.value = _.clone(current_course.value);
    form_data.course_id=newValue;
    // console.log('new current_course_info', current_course_info.value);
})

/* created */

today.value = moment().format('YYYY-MM-DD');

console.log(students.value);
console.log(props.courses);
console.log(props.students);

</script>

<style scoped>

</style>
