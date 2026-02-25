<template>
  <div>
    <!-- Search & Actions -->
    <div class="row mb-3" v-if="searchable || $slots.actions">
      <div class="col-md-6" v-if="searchable">
        <div class="input-group">
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>
          <input
            type="text"
            class="form-control"
            :placeholder="searchPlaceholder"
            v-model="searchQuery"
            @input="handleSearch"
          />
        </div>
      </div>
      <div class="col-md-6 text-end" v-if="$slots.actions">
        <slot name="actions" />
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-hover align-middle" :class="tableClass">
        <thead class="table-light">
          <tr>
            <th v-for="column in columns" :key="column.key" :class="column.headerClass">
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td :colspan="columns.length" class="text-center py-4">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <span class="ms-2">Loading...</span>
            </td>
          </tr>
          <tr v-else-if="!data || data.length === 0">
            <td :colspan="columns.length" class="text-center text-muted py-4">
              <i class="bi bi-inbox fs-3 d-block mb-2"></i>
              {{ emptyMessage }}
            </td>
          </tr>
          <tr v-else v-for="(row, index) in data" :key="row.id || index">
            <td v-for="column in columns" :key="column.key" :class="column.cellClass">
              <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                {{ row[column.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-between align-items-center" v-if="pagination && !loading">
      <div class="text-muted small">
        Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
      </div>
      <nav v-if="pagination.last_page > 1">
        <ul class="pagination mb-0">
          <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page - 1)">
              Previous
            </a>
          </li>
          <li 
            class="page-item" 
            v-for="page in visiblePages" 
            :key="page"
            :class="{ active: page === pagination.current_page }"
          >
            <a class="page-link" href="#" @click.prevent="goToPage(page)">
              {{ page }}
            </a>
          </li>
          <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
            <a class="page-link" href="#" @click.prevent="goToPage(pagination.current_page + 1)">
              Next
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true
  },
  data: Array,
  pagination: Object,
  loading: Boolean,
  searchable: Boolean,
  searchPlaceholder: {
    type: String,
    default: 'Search...'
  },
  emptyMessage: {
    type: String,
    default: 'No data available'
  },
  tableClass: {
    type: String,
    default: 'table-striped'
  }
});

const emit = defineEmits(['page-change', 'search']);

const searchQuery = ref('');

const visiblePages = computed(() => {
  if (!props.pagination) return [];
  
  const current = props.pagination.current_page;
  const last = props.pagination.last_page;
  const delta = 2;
  const range = [];
  
  for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
    range.push(i);
  }
  
  if (current - delta > 2) {
    range.unshift('...');
  }
  if (current + delta < last - 1) {
    range.push('...');
  }
  
  range.unshift(1);
  if (last > 1) {
    range.push(last);
  }
  
  return range.filter(p => p !== '...' || range.indexOf(p) === range.lastIndexOf(p));
});

const goToPage = (page) => {
  if (page >= 1 && page <= props.pagination.last_page) {
    emit('page-change', page);
  }
};

const handleSearch = () => {
  emit('search', searchQuery.value);
};
</script>
