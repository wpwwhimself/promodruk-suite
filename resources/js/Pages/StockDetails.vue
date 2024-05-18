<script setup lang="ts">
const props = defineProps<{
    product_code: string
}>()
</script>

<template>
    <h2>Produkt</h2>
    <p>{{ product_code }}</p>
</template>
