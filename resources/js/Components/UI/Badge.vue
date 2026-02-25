<template>
  <span class="badge" :class="badgeClass">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'].includes(value)
  },
  pill: Boolean
});

const badgeClass = computed(() => {
  return [
    `bg-${props.variant}`,
    { 'rounded-pill': props.pill }
  ];
});
</script>
