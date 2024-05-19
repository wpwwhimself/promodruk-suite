<script setup lang="ts">
import { format } from 'date-fns';

const date = new Date()
</script>

<template>
    <header>
        <p>
            STAN MAGAZYNOWY
            na dzień {{ format(date, "dd.MM.yyyy") }}
            /
            godz. {{ format(date, "HH:mm:ss") }}
        </p>
    </header>

    <slot />

    <footer>
        <p>For Promodruk by Wojciech Przybyła</p>
    </footer>
</template>
