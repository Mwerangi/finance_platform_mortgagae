<template>
  <div class="mb-3">
    <label :for="id" class="form-label" v-if="label">
      {{ label }}
      <span class="text-danger" v-if="required">*</span>
    </label>
    
    <textarea
      :id="id"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :rows="rows"
      class="form-control"
      :class="{ 'is-invalid': error }"
    ></textarea>
    
    <div class="invalid-feedback" v-if="error">{{ error }}</div>
    <small class="form-text text-muted" v-if="help">{{ help }}</small>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  error: String,
  help: String,
  required: Boolean,
  disabled: Boolean,
  readonly: Boolean,
  rows: {
    type: Number,
    default: 3
  }
});

defineEmits(['update:modelValue']);

const id = computed(() => `textarea-${Math.random().toString(36).substr(2, 9)}`);
</script>
