<template>
  <AppLayout breadcrumb="System Settings">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">System Settings</h2>
        <p class="text-muted mb-0">Configure system-wide parameters and preferences</p>
      </div>
      <div class="btn-group">
        <button class="btn btn-outline-secondary" @click="clearCache">
          <i class="bi bi-arrow-clockwise me-1"></i>Clear Cache
        </button>
        <button class="btn btn-outline-primary" @click="exportSettings">
          <i class="bi bi-download me-1"></i>Export
        </button>
        <button class="btn btn-primary" @click="saveAllChanges" :disabled="!hasChanges">
          <i class="bi bi-save me-1"></i>Save Changes
        </button>
      </div>
    </div>

    <!-- Success/Error Messages -->
    <div v-if="$page.props.flash?.success" class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i>{{ $page.props.flash.success }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Settings Tabs -->
    <div class="card">
      <div class="card-header bg-white">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
          <li class="nav-item" v-for="(meta, category) in categories" :key="category">
            <button 
              class="nav-link" 
              :class="{ active: activeCategory === category }"
              @click="activeCategory = category"
              type="button"
            >
              <i :class="`bi bi-${meta.icon} me-2`"></i>
              {{ meta.name }}
            </button>
          </li>
        </ul>
      </div>

      <div class="card-body">
        <!-- Category Description -->
        <div class="alert alert-info mb-4">
          <i class="bi bi-info-circle me-2"></i>
          {{ categories[activeCategory]?.description }}
        </div>

        <!-- Settings List -->
        <div v-if="categorySettings.length > 0" class="settings-list">
          <div 
            v-for="setting in categorySettings" 
            :key="setting.id"
            class="setting-item border-bottom pb-3 mb-3"
          >
            <div class="row align-items-center">
              <!-- Setting Info -->
              <div class="col-md-4">
                <label class="form-label fw-bold mb-1">{{ setting.label }}</label>
                <p class="text-muted small mb-0">{{ setting.description }}</p>
              </div>

              <!-- Setting Value Input -->
              <div class="col-md-6">
                <!-- Boolean Toggle -->
                <div v-if="setting.data_type === 'boolean'" class="form-check form-switch">
                  <input 
                    class="form-check-input" 
                    type="checkbox"
                    :id="`setting-${setting.id}`"
                    v-model="localSettings[setting.id]"
                    :disabled="!setting.is_editable"
                    @change="markAsChanged(setting.id)"
                  >
                  <label class="form-check-label" :for="`setting-${setting.id}`">
                    {{ localSettings[setting.id] ? 'Enabled' : 'Disabled' }}
                  </label>
                </div>

                <!-- Number Input -->
                <div v-else-if="setting.data_type === 'number'" class="input-group">
                  <input 
                    type="number" 
                    class="form-control"
                    v-model="localSettings[setting.id]"
                    :disabled="!setting.is_editable"
                    @input="markAsChanged(setting.id)"
                    :min="setting.validation_rules?.min"
                    :max="setting.validation_rules?.max"
                  >
                  <span v-if="setting.unit" class="input-group-text">{{ setting.unit }}</span>
                </div>

                <!-- Color Picker -->
                <div v-else-if="setting.data_type === 'color'" class="input-group">
                  <input 
                    type="color" 
                    class="form-control form-control-color"
                    v-model="localSettings[setting.id]"
                    :disabled="!setting.is_editable"
                    @change="markAsChanged(setting.id)"
                  >
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="localSettings[setting.id]"
                    :disabled="!setting.is_editable"
                    @input="markAsChanged(setting.id)"
                  >
                </div>

                <!-- Textarea -->
                <textarea 
                  v-else-if="setting.data_type === 'text'"
                  class="form-control"
                  rows="3"
                  v-model="localSettings[setting.id]"
                  :disabled="!setting.is_editable"
                  @input="markAsChanged(setting.id)"
                ></textarea>

                <!-- Select/Dropdown -->
                <select 
                  v-else-if="setting.options && setting.options.length > 0"
                  class="form-select"
                  v-model="localSettings[setting.id]"
                  :disabled="!setting.is_editable"
                  @change="markAsChanged(setting.id)"
                >
                  <option v-for="option in setting.options" :key="option" :value="option">
                    {{ option }}
                  </option>
                </select>

                <!-- Default Text Input -->
                <input 
                  v-else
                  type="text" 
                  class="form-control"
                  v-model="localSettings[setting.id]"
                  :disabled="!setting.is_editable"
                  @input="markAsChanged(setting.id)"
                >
              </div>

              <!-- Actions -->
              <div class="col-md-2 text-end">
                <button 
                  v-if="setting.is_editable"
                  class="btn btn-sm btn-outline-secondary"
                  @click="resetSetting(setting.id)"
                  title="Reset to default"
                >
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
                <span v-else class="badge bg-secondary">Read Only</span>
                <span v-if="changedSettings.has(setting.id)" class="badge bg-warning ms-2">Modified</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center text-muted py-5">
          <i class="bi bi-inbox fs-1 d-block mb-3"></i>
          <p>No settings available in this category</p>
        </div>

        <!-- Category Actions -->
        <div class="d-flex justify-content-between mt-4">
          <button 
            class="btn btn-outline-danger"
            @click="resetCategory"
          >
            <i class="bi bi-arrow-counterclockwise me-1"></i>
            Reset Category to Defaults
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

const props = defineProps({
  settings: Object,
  categories: Object,
})

// State
const activeCategory = ref('policy_risk')
const localSettings = ref({})
const changedSettings = ref(new Set())

// Initialize local settings
onMounted(() => {
  initializeSettings()
})

const initializeSettings = () => {
  Object.values(props.settings).flat().forEach(setting => {
    localSettings.value[setting.id] = setting.data_type === 'boolean' 
      ? setting.value === 'true' || setting.value === true
      : setting.value
  })
}

// Computed
const categorySettings = computed(() => {
  return (props.settings[activeCategory.value] || []).sort((a, b) => a.display_order - b.display_order)
})

const hasChanges = computed(() => changedSettings.value.size > 0)

// Methods
const markAsChanged = (settingId) => {
  changedSettings.value.add(settingId)
}

const saveAllChanges = () => {
  const settingsToUpdate = Array.from(changedSettings.value).map(id => {
    const setting = Object.values(props.settings).flat().find(s => s.id === id)
    return {
      id: id,
      value: localSettings.value[id]
    }
  })

  router.post('/settings/bulk-update', {
    settings: settingsToUpdate
  }, {
    preserveScroll: true,
    onSuccess: () => {
      changedSettings.value.clear()
    }
  })
}

const resetSetting = (settingId) => {
  if (confirm('Reset this setting to its default value?')) {
    const setting = Object.values(props.settings).flat().find(s => s.id === settingId)
    router.post(`/settings/${settingId}/reset`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        localSettings.value[settingId] = setting.default_value
        changedSettings.value.delete(settingId)
      }
    })
  }
}

const resetCategory = () => {
  if (confirm(`Reset all settings in this category to default values?`)) {
    router.post('/settings/reset-category', {
      category: activeCategory.value
    }, {
      preserveScroll: true,
      onSuccess: () => {
        initializeSettings()
        changedSettings.value.clear()
      }
    })
  }
}

const clearCache = () => {
  router.post('/settings/clear-cache', {}, {
    preserveScroll: true
  })
}

const exportSettings = () => {
  window.location.href = '/settings/export'
}
</script>

<style scoped>
.setting-item:last-child {
  border-bottom: none !important;
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
}

.form-control-color {
  width: 60px;
  height: 38px;
  padding: 4px;
}

.nav-tabs .nav-link {
  color: #6c757d;
  border: none;
  border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link:hover {
  border-bottom-color: #dee2e6;
}

.nav-tabs .nav-link.active {
  color: #0d6efd;
  border-bottom-color: #0d6efd;
  background-color: transparent;
}
</style>
