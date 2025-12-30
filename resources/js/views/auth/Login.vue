<template>
  <div class="login-container" :style="containerStyle">
    <!-- Split Layout -->
    <v-row v-if="settings.login_layout === 'split'" no-gutters class="fill-height">
      <!-- Left Side - Image/Branding -->
      <v-col cols="12" md="6" class="login-side-panel d-none d-md-flex">
        <div class="side-content">
          <div v-if="settings.show_logo_on_login && settings.logo_url" class="logo-container mb-8">
            <img :src="settings.logo_url" :alt="settings.app_name" class="logo-large" />
          </div>
          <h1 class="text-h3 font-weight-bold text-white mb-4">
            {{ settings.login_title || settings.app_name }}
          </h1>
          <p class="text-h6 text-white-70">
            {{ settings.login_subtitle || 'Manage your inventory with ease' }}
          </p>
        </div>
      </v-col>

      <!-- Right Side - Login Form -->
      <v-col cols="12" md="6" class="login-form-panel">
        <div class="form-container">
          <div class="text-center mb-8">
            <div v-if="settings.show_logo_on_login && settings.logo_url" class="d-md-none mb-4">
              <img :src="settings.logo_url" :alt="settings.app_name" class="logo-small" />
            </div>
            <h2 class="text-h4 font-weight-bold mb-2">Welcome Back</h2>
            <p class="text-subtitle-1 text-grey">Sign in to continue</p>
          </div>

          <v-form @submit.prevent="handleLogin">
            <v-text-field
              v-model="form.email"
              label="Email Address"
              type="email"
              variant="outlined"
              prepend-inner-icon="mdi-email"
              :error-messages="errors.email"
              :color="settings.color_scheme?.primary"
              required
              class="mb-4"
            ></v-text-field>

            <v-text-field
              v-model="form.password"
              label="Password"
              type="password"
              variant="outlined"
              prepend-inner-icon="mdi-lock"
              :error-messages="errors.password"
              :color="settings.color_scheme?.primary"
              required
              class="mb-6"
            ></v-text-field>

            <v-btn
              type="submit"
              :color="settings.color_scheme?.primary"
              size="x-large"
              block
              :loading="loading"
              :disabled="loading"
              class="text-none font-weight-bold"
              elevation="2"
            >
              Sign In
            </v-btn>
          </v-form>

          <div class="text-center mt-8">
            <p class="text-caption text-grey">
              © {{ new Date().getFullYear() }} {{ settings.app_name }}. All rights reserved.
            </p>
          </div>
        </div>
      </v-col>
    </v-row>

    <!-- Centered Layout -->
    <v-container v-else-if="settings.login_layout === 'centered'" class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card class="elevation-12 rounded-xl pa-4">
            <v-card-text>
              <div class="text-center mb-6">
                <div v-if="settings.show_logo_on_login && settings.logo_url" class="mb-4">
                  <img :src="settings.logo_url" :alt="settings.app_name" class="logo-medium" />
                </div>
                <h2 class="text-h4 font-weight-bold mb-2">
                  {{ settings.login_title || 'Welcome' }}
                </h2>
                <p class="text-subtitle-1 text-grey">
                  {{ settings.login_subtitle || 'Sign in to your account' }}
                </p>
              </div>

              <v-form @submit.prevent="handleLogin">
                <v-text-field
                  v-model="form.email"
                  label="Email Address"
                  type="email"
                  variant="outlined"
                  prepend-inner-icon="mdi-email"
                  :error-messages="errors.email"
                  :color="settings.color_scheme?.primary"
                  required
                  class="mb-4"
                ></v-text-field>

                <v-text-field
                  v-model="form.password"
                  label="Password"
                  type="password"
                  variant="outlined"
                  prepend-inner-icon="mdi-lock"
                  :error-messages="errors.password"
                  :color="settings.color_scheme?.primary"
                  required
                  class="mb-6"
                ></v-text-field>

                <v-btn
                  type="submit"
                  :color="settings.color_scheme?.primary"
                  size="x-large"
                  block
                  :loading="loading"
                  :disabled="loading"
                  class="text-none font-weight-bold"
                  elevation="2"
                >
                  Sign In
                </v-btn>
              </v-form>

              <div class="text-center mt-6">
                <p class="text-caption text-grey">
                  © {{ new Date().getFullYear() }} {{ settings.app_name }}
                </p>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>

    <!-- Background Layout -->
    <v-container v-else class="fill-height" fluid>
      <v-row align="center" justify="center">
        <v-col cols="12" sm="8" md="5" lg="4">
          <v-card class="elevation-24 rounded-xl pa-4 glass-card">
            <v-card-text>
              <div class="text-center mb-6">
                <div v-if="settings.show_logo_on_login && settings.logo_url" class="mb-4">
                  <img :src="settings.logo_url" :alt="settings.app_name" class="logo-medium" />
                </div>
                <h2 class="text-h4 font-weight-bold mb-2 text-white">
                  {{ settings.login_title || settings.app_name }}
                </h2>
                <p class="text-subtitle-1 text-white-70">
                  {{ settings.login_subtitle || 'Sign in to continue' }}
                </p>
              </div>

              <v-form @submit.prevent="handleLogin">
                <v-text-field
                  v-model="form.email"
                  label="Email Address"
                  type="email"
                  variant="outlined"
                  prepend-inner-icon="mdi-email"
                  :error-messages="errors.email"
                  :color="settings.color_scheme?.primary"
                  required
                  class="mb-4"
                  bg-color="rgba(255,255,255,0.9)"
                ></v-text-field>

                <v-text-field
                  v-model="form.password"
                  label="Password"
                  type="password"
                  variant="outlined"
                  prepend-inner-icon="mdi-lock"
                  :error-messages="errors.password"
                  :color="settings.color_scheme?.primary"
                  required
                  class="mb-6"
                  bg-color="rgba(255,255,255,0.9)"
                ></v-text-field>

                <v-btn
                  type="submit"
                  :color="settings.color_scheme?.primary"
                  size="x-large"
                  block
                  :loading="loading"
                  :disabled="loading"
                  class="text-none font-weight-bold"
                  elevation="2"
                >
                  Sign In
                </v-btn>
              </v-form>

              <div class="text-center mt-6">
                <p class="text-caption text-white-70">
                  © {{ new Date().getFullYear() }} {{ settings.app_name }}
                </p>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useAppSettingsStore } from '@/stores/appSettings';

const router = useRouter();
const authStore = useAuthStore();
const appSettingsStore = useAppSettingsStore();

const loading = ref(false);

// Get settings from store
const settings = computed(() => appSettingsStore.settings);

const form = reactive({
  email: '',
  password: '',
});

const errors = reactive({
  email: [],
  password: [],
});

// Computed style for container background
const containerStyle = computed(() => {
  const styles = {
    minHeight: '100vh',
    width: '100%',
  };

  if (settings.value.login_layout === 'background' && settings.value.login_background_url) {
    styles.backgroundImage = `url(${settings.value.login_background_url})`;
    styles.backgroundSize = 'cover';
    styles.backgroundPosition = 'center';
    styles.backgroundRepeat = 'no-repeat';
  } else if (settings.value.login_layout === 'split' && settings.value.login_side_image_url) {
    // Side image will be handled in CSS
  }

  return styles;
});

// Load app settings from store
const loadSettings = async () => {
  await appSettingsStore.loadSettings();
  appSettingsStore.updateDocumentTitle('Login');
};

const handleLogin = async () => {
  loading.value = true;
  errors.email = [];
  errors.password = [];

  const result = await authStore.login(form);

  if (result.success) {
    router.push({ name: 'Dashboard' });
  } else {
    // Handle login error - assuming general error for now
    errors.email = [result.message];
  }

  loading.value = false;
};

onMounted(() => {
  loadSettings();
});
</script>

<style scoped>
.login-container {
  position: relative;
  overflow: hidden;
}

/* Split Layout Styles */
.login-side-panel {
  background: linear-gradient(135deg, v-bind('settings.color_scheme?.primary || "#1976D2"') 0%, v-bind('settings.color_scheme?.secondary || "#424242"') 100%);
  background-image: v-bind('settings.login_side_image_url ? `url(${settings.login_side_image_url})` : "none"');
  background-size: cover;
  background-position: center;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}

.login-side-panel::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(25, 118, 210, 0.9) 0%, rgba(66, 66, 66, 0.9) 100%);
  z-index: 1;
}

.side-content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 500px;
}

.login-form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: #ffffff;
}

.form-container {
  width: 100%;
  max-width: 450px;
}

/* Logo Styles */
.logo-large {
  max-width: 200px;
  max-height: 120px;
  object-fit: contain;
  filter: brightness(0) invert(1);
}

.logo-medium {
  max-width: 150px;
  max-height: 80px;
  object-fit: contain;
}

.logo-small {
  max-width: 120px;
  max-height: 60px;
  object-fit: contain;
}

/* Glass Card for Background Layout */
.glass-card {
  background: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

/* Text Colors */
.text-white-70 {
  color: rgba(255, 255, 255, 0.7);
}

/* Responsive */
@media (max-width: 960px) {
  .login-form-panel {
    min-height: 100vh;
  }

  .form-container {
    padding: 1rem;
  }
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.form-container {
  animation: fadeInUp 0.6s ease-out;
}

.side-content {
  animation: fadeInUp 0.8s ease-out;
}
</style>