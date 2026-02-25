<template>
  <div class="mb-3">
    <label :for="id" class="form-label" v-if="label">
      {{ label }}
      <span class="text-danger" v-if="required">*</span>
    </label>
    
    <div v-if="prefix || suffix" class="input-group">
      <span class="input-group-text" v-if="prefix">{{ prefix }}</span>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        class="form-control"
        :class="{ 'is-invalid': error }"
      />
      <span class="input-group-text" v-if="suffix">{{ suffix }}</span>
      <div class="invalid-feedback" v-if="error">{{ error }}</div>
    </div>
    
    <template v-else>
      <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :readonly="readonly"
        class="form-control"
        :class="{ 'is-invalid': error }"
      />
      <div class="invalid-feedback" v-if="error">{{ error }}</div>
    </template>
    
    <small class="form-text text-muted" v-if="help">{{ help }}</small>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: [String, Number],
  label: String,
  type: {
    type: String,
    default: 'text'
  },
  placeholder: String,
  error: String,
  help: String,
  required: Boolean,
  disabled: Boolean,
  readonly: Boolean,
  prefix: String,
  suffix: String
});

defineEmits(['update:modelValue']);

const id = computed(() => `input-${Math.random().toString(36).substr(2, 9)}`);
</script>
