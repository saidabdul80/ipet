<template>
  <div>
    <v-row>
      <v-col cols="12">
        <h1 class="text-h4 mb-4">Units of Measurement</h1>
      </v-col>
    </v-row>

    <!-- Filters and Actions -->
    <v-row>
      <v-col cols="12" md="8">
        <v-text-field
          v-model="search"
          label="Search units..."
          prepend-inner-icon="mdi-magnify"
          variant="outlined"
          density="compact"
          clearable
          @input="loadUnits"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="primary" @click="openCreateDialog" v-if="authStore.hasPermission('create_units')">
          <v-icon left>mdi-plus</v-icon>
          Add Unit
        </v-btn>
      </v-col>
    </v-row>

    <!-- Units Table -->
    <v-card>
      <v-data-table
        :headers="headers"
        :items="units"
        :loading="loading"
      >
        <template v-slot:item.name="{ item }">
          <div>
            <div class="font-weight-bold">{{ item.name }}</div>
            <div class="text-caption text-grey">{{ item.abbreviation }}</div>
          </div>
        </template>

        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>

        <template v-slot:item.actions="{ item }">
          <v-btn
            icon
            size="small"
            @click="editUnit(item)"
            title="Edit"
            v-if="authStore.hasPermission('update_units')"
          >
            <v-icon>mdi-pencil</v-icon>
          </v-btn>
          <v-btn
            icon
            size="small"
            @click="deleteUnit(item)"
            title="Delete"
            color="error"
            v-if="authStore.hasPermission('delete_units')"
          >
            <v-icon>mdi-delete</v-icon>
          </v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="500px" persistent>
      <v-card>
        <v-card-title>{{ editMode ? 'Edit Unit' : 'New Unit' }}</v-card-title>
        <v-card-text>
          <v-form ref="form">
            <v-text-field
              v-model="formData.name"
              label="Unit Name *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-text-field>
            <v-text-field
              v-model="formData.abbreviation"
              label="Abbreviation *"
              variant="outlined"
              :rules="[v => !!v || 'Required']"
            ></v-text-field>
            <v-textarea
              v-model="formData.description"
              label="Description"
              variant="outlined"
              rows="2"
            ></v-textarea>
            <v-switch
              v-model="formData.is_active"
              label="Active"
              color="primary"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn @click="closeDialog">Cancel</v-btn>
          <v-btn color="primary" @click="saveUnit" :loading="saving">Save</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const dialog = ref(false);
const editMode = ref(false);
const search = ref('');
const units = ref([]);

const headers = [
  { title: 'Unit', key: 'name' },
  { title: 'Description', key: 'description' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false },
];

const formData = ref({
  name: '',
  abbreviation: '',
  description: '',
  is_active: true,
});

const loadUnits = async () => {
  loading.value = true;
  try {
    const params = { search: search.value };
    const response = await axios.get('/api/units', { params });
    units.value = response.data.data || response.data;
  } catch (error) {
    console.error('Failed to load units:', error);
  } finally {
    loading.value = false;
  }
};

const openCreateDialog = () => {
  editMode.value = false;
  formData.value = {
    name: '',
    abbreviation: '',
    description: '',
    is_active: true,
  };
  dialog.value = true;
};

const editUnit = (unit) => {
  editMode.value = true;
  formData.value = { ...unit };
  dialog.value = true;
};

const closeDialog = () => {
  dialog.value = false;
};

const saveUnit = async () => {
  saving.value = true;
  try {
    if (editMode.value) {
      await axios.put(`/api/units/${formData.value.id}`, formData.value);
    } else {
      await axios.post('/api/units', formData.value);
    }
    closeDialog();
    loadUnits();
  } catch (error) {
    console.error('Failed to save unit:', error);
    alert('Failed to save unit');
  } finally {
    saving.value = false;
  }
};

const deleteUnit = async (unit) => {
  if (confirm(`Are you sure you want to delete ${unit.name}?`)) {
    try {
      await axios.delete(`/api/units/${unit.id}`);
      loadUnits();
    } catch (error) {
      console.error('Failed to delete unit:', error);
      alert('Failed to delete unit');
    }
  }
};

onMounted(() => {
  loadUnits();
});
</script>

