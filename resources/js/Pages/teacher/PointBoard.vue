<template>
    <Head title="積點系統" />

    <BaseLayout>
        <div class="w-full h-full flex">
            <div class="flex flex-col gap-4 bg-black p-4" style="width: 350px;">
                <div class="flex justify-center border-double border-2 border-white p-1 text-white text-3xl">
                    <div class="flex-1 flex justify-center border-double border-2 border-white p-4 text-white text-2xl">
                        {{ class_info.course_title }}
                    </div>
                </div>
                <div class="flex justify-center p-4 text-white text-2xl">
                    班級：{{ class_info.class_name }}
                </div>
                <div class="border border-white border-collapse">
                    <div v-for="(item, index) in students" class="bg-white px-4 py-2 border-y border-gray-400">
                        {{ item.id }} {{ item.username }}
                    </div>
                </div>
            </div>
            <div class="flex-1 flex flex-col">
                <div class="h-[52px] bg-cyan-400 flex">
                </div>
                <div class="flex-1 flex flex-col p-4">
                    <div>

                    </div>
                </div>
            </div>
        </div>
    </BaseLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { reactive, computed } from 'vue';
import _ from 'lodash';
import BaseLayout from "@/Layouts/BaseLayout.vue";


const my_class = reactive([
    {id: 1, class_name: '資工一A', course_title: '離散數學'}
]);
const my_groups = reactive([
    {id: 1, name: '組1', score: 0}
]);

/* computed */

const class_info = computed(() => _.find(my_class, {id: 1}));
const students = computed(() => {
    return _.transform(_.range(1,10), (res, item, index) => {
        res.push({
            id: `4120E0${_.padStart(item + 1, 2, 0)}`,
            username: '王小明',
            groups_id: null,
            class_id: 1,
        });
    }, []);
});
const groups = computed(() => my_groups);

/* created */

console.log(students.value);

</script>

<style scoped>

</style>