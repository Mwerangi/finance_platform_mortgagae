<template>
  <div class="modal fade" :id="id" tabindex="-1" ref="modalElement">
    <div class="modal-dialog" :class="sizeClass">
      <div class="modal-content">
        <div class="modal-header" v-if="$slots.header || title">
          <slot name="header">
            <h5 class="modal-title">{{ title }}</h5>
          </slot>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <slot />
        </div>
        <div class="modal-footer" v-if="$slots.footer">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  id: {
    type: String,
    required: true
  },
  title: String,
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
  }
});

const modalElement = ref(null);
let modalInstance = null;

const sizeClass = computed(() => {
  const sizes = {
    sm: 'modal-sm',
    md: '',
    lg: 'modal-lg',
    xl: 'modal-xl'
  };
  return sizes[props.size];
});

const show = () => {
  if (modalInstance) {
    modalInstance.show();
  }
};

const hide = () => {
  if (modalInstance) {
    modalInstance.hide();
  }
};

onMounted(() => {
  if (modalElement.value) {
    modalInstance = new window.bootstrap.Modal(modalElement.value);
  }
});

onUnmounted(() => {
  if (modalInstance) {
    modalInstance.dispose();
  }
});

defineExpose({ show, hide });
</script>
