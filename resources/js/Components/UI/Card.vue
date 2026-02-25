<template>
  <div class="card" :class="cardClass">
    <div class="card-header" v-if="$slots.header || header">
      <slot name="header">
        <h5 class="card-title mb-0">{{ header }}</h5>
      </slot>
    </div>
    <div class="card-body" :class="bodyClass">
      <slot />
    </div>
    <div class="card-footer" v-if="$slots.footer" :class="footerClass">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  header: String,
  cardClass: {
    type: String,
    default: ''
  },
  bodyClass: {
    type: String,
    default: ''
  },
  footerClass: {
    type: String,
    default: ''
  }
});
</script>
