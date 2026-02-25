<template>
  <div class="mb-3">
    <label :for="id" class="form-label" v-if="label">
      {{ label }}
      <span class="text-danger" v-if="required">*</span>
    </label>
    
    <select
      :id="id"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :required="required"
      :disabled="disabled"
      class="form-select"
      :class="{ 'is-invalid': error }"
    >
      <option value="" v-if="placeholder">{{ placeholder }}</option>
      <option 
        v-for="option in options" 
        :key="option.value" 
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    
    <div class="invalid-feedback" v-if="error">{{ error }}</div>
    <small class="form-text text-muted" v-if="help">{{ help }}</small>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  options: {
    type: Array,
    required: true
  },
  placeholder: String,
  error: String,
  help: String,
  required: Boolean,
  disabled: Boolean
});

defineEmits(['update:modelValue']);

const id = computed(() => `select-${Math.random().toString(36).substr(2, 9)}`);
</script>
