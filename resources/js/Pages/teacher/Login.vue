<template lang="pug">

Head(title="登入")

.w-screen.h-screen.login-background
    .flex.justify-center.items-center.h-full
        .box
            div.text-3xl.font-bold 積點系統登入
            TextInput(type="text" placeholder="帳號" v-model="form.t_account" )
            TextInput(type="password" placeholder="密碼" v-model="form.t_password" )
            div.text-red-500(v-if="e_msg !== ''") {{ e_msg }}
            PrimaryButton.justify-center(@click="login") 登入

</template>

<script setup lang="ts">
import { Head, useForm } from "@inertiajs/vue3";
import { reactive, computed } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";


const props = defineProps<{
  e_msg: string,
}>();

/* data */

const form = useForm({
    t_account: '',
    t_password: '',
});


/* methods */

const login = () => {
  // log.print(t_account);
  // log.print(t_password);
    form.post('/teacher/login');
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
