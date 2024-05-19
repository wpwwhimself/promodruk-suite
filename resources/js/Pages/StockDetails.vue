<script setup lang="ts">
const props = defineProps<{
    product_code: string,
    data: object[],
}>()
</script>

<template>
    <p v-for="row in data">
        {{ row.index }}
        / [NAZWA]
        / {{ row.quantity }} szt.
        / Planowana dostawa: [DATA]
    </p>
</template>
