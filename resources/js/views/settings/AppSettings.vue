<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span>App Settings</span>
            <div>
              <v-btn color="warning" @click="resetDialog = true" class="mr-2">
                <v-icon left>mdi-restore</v-icon>
                Reset to Defaults
              </v-btn>
              <v-btn color="primary" @click="saveSettings" :loading="saving">
                <v-icon left>mdi-content-save</v-icon>
                Save Changes
              </v-btn>
            </div>
          </v-card-title>

          <v-card-text>
            <v-tabs v-model="tab" color="primary">
              <v-tab value="general">General</v-tab>
              <v-tab value="branding">Branding</v-tab>
              <v-tab value="colors">Colors</v-tab>
              <v-tab value="login">Login Page</v-tab>
              <v-tab value="company">Company Info</v-tab>
            </v-tabs>

            <v-window v-model="tab" class="mt-6">
              <!-- General Tab -->
              <v-window-item value="general">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.app_name"
                      label="Application Name"
                      variant="outlined"
                      prepend-inner-icon="mdi-application"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.app_short_name"
                      label="Short Name / Acronym"
                      variant="outlined"
                      prepend-inner-icon="mdi-format-text"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="form.app_description"
                      label="Application Description"
                      variant="outlined"
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="form.currency_symbol"
                      label="Currency Symbol"
                      variant="outlined"
                      prepend-inner-icon="mdi-currency-ngn"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="form.currency_code"
                      label="Currency Code"
                      variant="outlined"
                      prepend-inner-icon="mdi-cash"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="form.timezone"
                      label="Timezone"
                      variant="outlined"
                      prepend-inner-icon="mdi-clock-outline"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-window-item>

              <!-- Branding Tab -->
              <v-window-item value="branding">
                <v-row>
                  <!-- Logo Upload -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="text-subtitle-1">Logo (Light Mode)</v-card-title>
                      <v-card-text>
                        <div v-if="logoPreview || settings.logo_url" class="mb-4 text-center">
                          <img :src="logoPreview || settings.logo_url" alt="Logo" class="preview-image" />
                          <v-btn
                            size="small"
                            color="error"
                            variant="text"
                            @click="deleteImage('logo')"
                            class="mt-2"
                          >
                            Remove
                          </v-btn>
                        </div>
                        <v-file-input
                          v-model="files.logo"
                          label="Upload Logo"
                          accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                          prepend-icon="mdi-image"
                          variant="outlined"
                          @change="handleFileChange('logo')"
                        ></v-file-input>
                        <p class="text-caption text-grey">Recommended: PNG or SVG, max 2MB</p>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <!-- Dark Logo Upload -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="text-subtitle-1">Logo (Dark Mode)</v-card-title>
                      <v-card-text>
                        <div v-if="logoDarkPreview || settings.logo_dark_url" class="mb-4 text-center">
                          <img :src="logoDarkPreview || settings.logo_dark_url" alt="Dark Logo" class="preview-image" />
                          <v-btn
                            size="small"
                            color="error"
                            variant="text"
                            @click="deleteImage('logo_dark')"
                            class="mt-2"
                          >
                            Remove
                          </v-btn>
                        </div>
                        <v-file-input
                          v-model="files.logo_dark"
                          label="Upload Dark Logo"
                          accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                          prepend-icon="mdi-image"
                          variant="outlined"
                          @change="handleFileChange('logo_dark')"
                        ></v-file-input>
                        <p class="text-caption text-grey">Recommended: PNG or SVG, max 2MB</p>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <!-- Favicon Upload -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="text-subtitle-1">Favicon</v-card-title>
                      <v-card-text>
                        <div v-if="faviconPreview || settings.favicon_url" class="mb-4 text-center">
                          <img :src="faviconPreview || settings.favicon_url" alt="Favicon" class="preview-image-small" />
                          <v-btn
                            size="small"
                            color="error"
                            variant="text"
                            @click="deleteImage('favicon')"
                            class="mt-2"
                          >
                            Remove
                          </v-btn>
                        </div>
                        <v-file-input
                          v-model="files.favicon"
                          label="Upload Favicon"
                          accept="image/x-icon,image/png"
                          prepend-icon="mdi-web"
                          variant="outlined"
                          @change="handleFileChange('favicon')"
                        ></v-file-input>
                        <p class="text-caption text-grey">Recommended: ICO or PNG, 32x32px, max 512KB</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-window-item>

              <!-- Colors Tab -->
              <v-window-item value="colors">
                <v-row>
                  <v-col cols="12">
                    <p class="text-subtitle-1 mb-4">Customize your application's color scheme</p>
                  </v-col>
                  <v-col cols="12" md="6" lg="4" v-for="color in colorFields" :key="color.key">
                    <v-card variant="outlined">
                      <v-card-text>
                        <div class="d-flex align-center justify-space-between mb-2">
                          <span class="font-weight-medium">{{ color.label }}</span>
                          <div
                            class="color-preview"
                            :style="{ backgroundColor: form[color.key] }"
                          ></div>
                        </div>
                        <v-text-field
                          v-model="form[color.key]"
                          type="color"
                          variant="outlined"
                          density="compact"
                          hide-details
                        ></v-text-field>
                        <p class="text-caption text-grey mt-1">{{ form[color.key] }}</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-window-item>

              <!-- Login Page Tab -->
              <v-window-item value="login">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="form.login_layout"
                      :items="loginLayouts"
                      label="Login Page Layout"
                      variant="outlined"
                      prepend-inner-icon="mdi-page-layout-header"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="form.show_logo_on_login"
                      label="Show Logo on Login Page"
                      color="primary"
                      hide-details
                    ></v-switch>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.login_title"
                      label="Login Page Title"
                      variant="outlined"
                      prepend-inner-icon="mdi-format-title"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.login_subtitle"
                      label="Login Page Subtitle"
                      variant="outlined"
                      prepend-inner-icon="mdi-format-text"
                    ></v-text-field>
                  </v-col>

                  <!-- Login Background Image -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="text-subtitle-1">Background Image</v-card-title>
                      <v-card-text>
                        <div v-if="loginBackgroundPreview || settings.login_background_url" class="mb-4 text-center">
                          <img :src="loginBackgroundPreview || settings.login_background_url" alt="Background" class="preview-image-wide" />
                          <v-btn
                            size="small"
                            color="error"
                            variant="text"
                            @click="deleteImage('login_background')"
                            class="mt-2"
                          >
                            Remove
                          </v-btn>
                        </div>
                        <v-file-input
                          v-model="files.login_background"
                          label="Upload Background Image"
                          accept="image/png,image/jpeg,image/jpg"
                          prepend-icon="mdi-image"
                          variant="outlined"
                          @change="handleFileChange('login_background')"
                        ></v-file-input>
                        <p class="text-caption text-grey">For 'background' layout. Recommended: 1920x1080px, max 5MB</p>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <!-- Login Side Image -->
                  <v-col cols="12" md="6">
                    <v-card variant="outlined">
                      <v-card-title class="text-subtitle-1">Side Image</v-card-title>
                      <v-card-text>
                        <div v-if="loginSideImagePreview || settings.login_side_image_url" class="mb-4 text-center">
                          <img :src="loginSideImagePreview || settings.login_side_image_url" alt="Side Image" class="preview-image-wide" />
                          <v-btn
                            size="small"
                            color="error"
                            variant="text"
                            @click="deleteImage('login_side_image')"
                            class="mt-2"
                          >
                            Remove
                          </v-btn>
                        </div>
                        <v-file-input
                          v-model="files.login_side_image"
                          label="Upload Side Image"
                          accept="image/png,image/jpeg,image/jpg"
                          prepend-icon="mdi-image"
                          variant="outlined"
                          @change="handleFileChange('login_side_image')"
                        ></v-file-input>
                        <p class="text-caption text-grey">For 'split' layout. Recommended: 1080x1920px, max 5MB</p>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-window-item>

              <!-- Company Info Tab -->
              <v-window-item value="company">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.company_email"
                      label="Company Email"
                      type="email"
                      variant="outlined"
                      prepend-inner-icon="mdi-email"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.company_phone"
                      label="Company Phone"
                      variant="outlined"
                      prepend-inner-icon="mdi-phone"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12">
                    <v-textarea
                      v-model="form.company_address"
                      label="Company Address"
                      variant="outlined"
                      rows="3"
                      prepend-inner-icon="mdi-map-marker"
                    ></v-textarea>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="form.company_website"
                      label="Company Website"
                      type="url"
                      variant="outlined"
                      prepend-inner-icon="mdi-web"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-window-item>
            </v-window>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Reset Confirmation Dialog -->
    <v-dialog v-model="resetDialog" max-width="500px">
      <v-card>
        <v-card-title>Reset to Defaults?</v-card-title>
        <v-card-text>
          This will reset all settings to their default values and delete all uploaded images. This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="resetDialog = false">Cancel</v-btn>
          <v-btn color="warning" @click="resetToDefaults" :loading="resetting">Reset</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { useDialog } from '@/composables/useDialog';

const tab = ref('general');
const saving = ref(false);
const resetting = ref(false);
const resetDialog = ref(false);
const settings = ref({});
const { alert, confirm } = useDialog();

const form = reactive({
  app_name: '',
  app_short_name: '',
  app_description: '',
  primary_color: '#1976D2',
  secondary_color: '#424242',
  accent_color: '#82B1FF',
  error_color: '#FF5252',
  info_color: '#2196F3',
  success_color: '#4CAF50',
  warning_color: '#FB8C00',
  login_layout: 'split',
  login_title: '',
  login_subtitle: '',
  show_logo_on_login: true,
  currency_symbol: '₦',
  currency_code: 'NGN',
  timezone: 'Africa/Lagos',
  company_email: '',
  company_phone: '',
  company_address: '',
  company_website: '',
});

const files = reactive({
  logo: null,
  logo_dark: null,
  favicon: null,
  login_background: null,
  login_side_image: null,
});

const logoPreview = ref(null);
const logoDarkPreview = ref(null);
const faviconPreview = ref(null);
const loginBackgroundPreview = ref(null);
const loginSideImagePreview = ref(null);

const colorFields = [
  { key: 'primary_color', label: 'Primary Color' },
  { key: 'secondary_color', label: 'Secondary Color' },
  { key: 'accent_color', label: 'Accent Color' },
  { key: 'error_color', label: 'Error Color' },
  { key: 'info_color', label: 'Info Color' },
  { key: 'success_color', label: 'Success Color' },
  { key: 'warning_color', label: 'Warning Color' },
];

const loginLayouts = [
  { title: 'Split (Side Image)', value: 'split' },
  { title: 'Centered', value: 'centered' },
  { title: 'Background Image', value: 'background' },
];

const loadSettings = async () => {
  try {
    const response = await axios.get('/api/app-settings');
    settings.value = response.data;

    // Populate form
    Object.keys(form).forEach(key => {
      if (response.data[key] !== undefined) {
        form[key] = response.data[key];
      }
    });

    // Handle color scheme
    if (response.data.color_scheme) {
      form.primary_color = response.data.color_scheme.primary;
      form.secondary_color = response.data.color_scheme.secondary;
      form.accent_color = response.data.color_scheme.accent;
      form.error_color = response.data.color_scheme.error;
      form.info_color = response.data.color_scheme.info;
      form.success_color = response.data.color_scheme.success;
      form.warning_color = response.data.color_scheme.warning;
    }
  } catch (error) {
    console.error('Failed to load settings:', error);
  }
};

const handleFileChange = (type) => {
  const file = files[type];
  if (file) {
    // Get the actual file object
    let fileObj = null;
    if (Array.isArray(file) && file.length > 0) {
      fileObj = file[0];
    } else if (file instanceof File) {
      fileObj = file;
    }

    if (fileObj) {
      const reader = new FileReader();
      reader.onload = (e) => {
        switch (type) {
          case 'logo':
            logoPreview.value = e.target.result;
            break;
          case 'logo_dark':
            logoDarkPreview.value = e.target.result;
            break;
          case 'favicon':
            faviconPreview.value = e.target.result;
            break;
          case 'login_background':
            loginBackgroundPreview.value = e.target.result;
            break;
          case 'login_side_image':
            loginSideImagePreview.value = e.target.result;
            break;
        }
      };
      reader.readAsDataURL(fileObj);
    }
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const formData = new FormData();

    // Append form fields
    Object.keys(form).forEach(key => {
      if (form[key] !== null && form[key] !== undefined) {
        // Convert boolean to 0 or 1 for Laravel validation
        if (typeof form[key] === 'boolean') {
          formData.append(key, form[key] ? '1' : '0');
        } else {
          formData.append(key, form[key]);
        }
      }
    });

    // Append files
    Object.keys(files).forEach(key => {
      if (files[key]) {
        // v-file-input returns an array of files
        if (Array.isArray(files[key]) && files[key].length > 0) {
          formData.append(key, files[key][0]);
        } else if (files[key] instanceof File) {
          // Single file object
          formData.append(key, files[key]);
        }
      }
    });

    const response = await axios.post('/api/app-settings', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    settings.value = response.data.data;
    alert('Settings saved successfully!');

    // Clear file inputs
    Object.keys(files).forEach(key => {
      files[key] = null;
    });
    logoPreview.value = null;
    logoDarkPreview.value = null;
    faviconPreview.value = null;
    loginBackgroundPreview.value = null;
    loginSideImagePreview.value = null;

    await loadSettings();
  } catch (error) {
    console.error('Failed to save settings:', error);
    alert(error.response?.data?.message || 'Failed to save settings');
  } finally {
    saving.value = false;
  }
};

const deleteImage = async (type) => {
  const confirmed = await confirm('Are you sure you want to delete this image?');
  if (!confirmed) return;

  try {
    await axios.delete(`/api/app-settings/images/${type}`);
    await loadSettings();

    // Clear preview
    switch (type) {
      case 'logo':
        logoPreview.value = null;
        break;
      case 'logo_dark':
        logoDarkPreview.value = null;
        break;
      case 'favicon':
        faviconPreview.value = null;
        break;
      case 'login_background':
        loginBackgroundPreview.value = null;
        break;
      case 'login_side_image':
        loginSideImagePreview.value = null;
        break;
    }
  } catch (error) {
    console.error('Failed to delete image:', error);
    alert('Failed to delete image');
  }
};

const resetToDefaults = async () => {
  resetting.value = true;
  try {
    await axios.post('/api/app-settings/reset');
    resetDialog.value = false;
    await loadSettings();
    alert('Settings reset to defaults successfully!');
  } catch (error) {
    console.error('Failed to reset settings:', error);
    alert('Failed to reset settings');
  } finally {
    resetting.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>

<style scoped>
.preview-image {
  max-width: 200px;
  max-height: 100px;
  object-fit: contain;
}

.preview-image-small {
  max-width: 64px;
  max-height: 64px;
  object-fit: contain;
}

.preview-image-wide {
  max-width: 100%;
  max-height: 200px;
  object-fit: cover;
  border-radius: 8px;
}

.color-preview {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  border: 2px solid #e0e0e0;
}
</style>
