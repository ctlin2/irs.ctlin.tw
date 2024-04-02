<template lang="pug">

Head(title="登入")

.w-screen.h-screen.login-background
    .flex.justify-center.items-center.h-full
        .box
            div
            div.text-3xl.font-bold 學生登入
            div
            TextInput(type="text" placeholder="學號" v-model="form.std_no")
            div.text-red-500(v-if="e_msg !== ''") {{ e_msg }}
            PrimaryButton.justify-center(@click="login") 登入

</template>

<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { reactive, computed, defineProps } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";


const props = defineProps<{
    e_msg?: string,
    std_no?: string
}>();

/* data */

const form = useForm({
    std_no: props.std_no
});

/* methods */

const login = () => {
    form.post('/student/login');
}

</script>

<style scoped>
.box {
    @apply flex flex-col border rounded-md gap-4 p-4 bg-white;
    @apply h-[250px];
}
.login-background {
    @apply bg-gray-500;
}
</style>