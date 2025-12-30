import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAppSettingsStore = defineStore('appSettings', () => {
    const settings = ref({
        app_name: 'Inventory Management System',
        app_short_name: 'IMS',
        app_description: '',
        logo_url: null,
        logo_dark_url: null,
        favicon_url: null,
        login_background_url: null,
        login_side_image_url: null,
        primary_color: '#1976D2',
        secondary_color: '#424242',
        accent_color: '#82B1FF',
        error_color: '#FF5252',
        info_color: '#2196F3',
        success_color: '#4CAF50',
        warning_color: '#FB8C00',
        login_layout: 'centered',
        login_title: 'Welcome Back',
        login_subtitle: 'Sign in to continue',
        show_logo_on_login: true,
        currency_symbol: '₦',
        currency_code: 'NGN',
        timezone: 'Africa/Lagos',
        date_format: 'Y-m-d',
        time_format: 'H:i:s',
        company_email: '',
        company_phone: '',
        company_address: '',
        company_website: '',
    });

    const loading = ref(false);
    const loaded = ref(false);

    // Computed properties for easy access
    const appName = computed(() => settings.value.app_name);
    const appShortName = computed(() => settings.value.app_short_name);
    const logoUrl = computed(() => settings.value.logo_url);
    const logoDarkUrl = computed(() => settings.value.logo_dark_url);
    const faviconUrl = computed(() => settings.value.favicon_url);
    const primaryColor = computed(() => settings.value.primary_color);
    const colorScheme = computed(() => ({
        primary: settings.value.primary_color,
        secondary: settings.value.secondary_color,
        accent: settings.value.accent_color,
        error: settings.value.error_color,
        info: settings.value.info_color,
        success: settings.value.success_color,
        warning: settings.value.warning_color,
    }));

    // Load settings from API
    async function loadSettings() {
        if (loaded.value) return settings.value;
        
        loading.value = true;
        try {
            const response = await axios.get('/api/app-settings');
            settings.value = { ...settings.value, ...response.data };
            loaded.value = true;
            
            // Update document title
            updateDocumentTitle();
            
            // Update favicon
            updateFavicon();
            
            return settings.value;
        } catch (error) {
            console.error('Failed to load app settings:', error);
            return settings.value;
        } finally {
            loading.value = false;
        }
    }

    // Refresh settings (force reload)
    async function refreshSettings() {
        loaded.value = false;
        return await loadSettings();
    }

    // Update document title
    function updateDocumentTitle(pageTitle = null) {
        if (pageTitle) {
            document.title = `${pageTitle} - ${settings.value.app_name}`;
        } else {
            document.title = settings.value.app_name;
        }
    }

    // Update favicon
    function updateFavicon() {
        if (settings.value.favicon_url) {
            let link = document.querySelector("link[rel*='icon']");
            if (!link) {
                link = document.createElement('link');
                link.rel = 'shortcut icon';
                document.getElementsByTagName('head')[0].appendChild(link);
            }
            link.type = 'image/x-icon';
            link.href = settings.value.favicon_url;
        }
    }

    // Get logo based on theme (light/dark)
    function getLogo(isDark = false) {
        if (isDark && settings.value.logo_dark_url) {
            return settings.value.logo_dark_url;
        }
        return settings.value.logo_url || null;
    }

    return {
        settings,
        loading,
        loaded,
        appName,
        appShortName,
        logoUrl,
        logoDarkUrl,
        faviconUrl,
        primaryColor,
        colorScheme,
        loadSettings,
        refreshSettings,
        updateDocumentTitle,
        updateFavicon,
        getLogo,
    };
});

