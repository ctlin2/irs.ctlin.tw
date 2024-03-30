<script setup>

</script>

<template lang="pug">
.h-screen.flex.flex-col
    .w-full.h-full.flex.overflow-y-hidden
        .flex.flex-col.gap-4.bg-black.p-4(style="width: 350px;")
            slot(name="left")
        .flex-1.flex.flex-col.overflow-y-auto.max-h-screen
            slot
</template>

<style scoped>

</style>