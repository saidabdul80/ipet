<template>
    <v-app>
        <router-view />
    </v-app>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(() => {
    authStore.checkAuth();
});
</script>

<style>
@import 'tailwindcss';
</style>

