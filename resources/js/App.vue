<template>
    <v-app>
        <router-view />
        <AlertDialog />
    </v-app>
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import AlertDialog from '@/components/feedback/AlertDialog.vue';

const authStore = useAuthStore();

onMounted(() => {
    authStore.checkAuth();
});
</script>

<style>
@import 'tailwindcss';
</style>
