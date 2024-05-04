<script setup>

</script>

<template lang="pug">
.h-screen.w-full.flex.flex-row.flex-wrap
    .h-screen.flex-initial.flex-col.gap-2.bg-black.p-2(style="width: 340px;")
        slot(name="left")
    .h-screen.flex-1.flex-col.overflow-y-auto.max-h-screen
        slot
</template>

<style scoped>

</style>