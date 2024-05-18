<script setup lang="ts">
import { onMounted } from 'vue';

const props = defineProps<{
    product_code: string,
    data: object,
}>()
</script>

<template>
    <h2>Produkt</h2>
    <p>{{ product_code }}</p>
</template>
