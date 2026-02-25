<template>
  <AppLayout breadcrumb="User Management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h2 class="mb-1">User Management</h2>
        <p class="text-muted mb-0">Manage system users and their roles</p>
      </div>
      <Link href="/users/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>Add New User
      </Link>
    </div>

    <!-- Search and Filters -->
    <Card class="mb-4">
      <div class="row g-3">
        <div class="col-md-4">
          <Input
            v-model="filters.search"
            placeholder="Search by name or email..."
            prefix-icon="search"
          >
            <template #prefix>
              <i class="bi bi-search"></i>
            </template>
          </Input>
        </div>
        <div class="col-md-3">
          <Select
            v-model="filters.role"
            label="Role"
            :options="roleOptions"
            placeholder="All Roles"
          />
        </div>
        <div class="col-md-3">
          <Select
            v-model="filters.status"
            label="Status"
            :options="statusOptions"
            placeholder="All Statuses"
          />
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button @click="applyFilters" class="btn btn-primary w-100">
            <i class="bi bi-funnel me-1"></i>Filter
          </button>
        </div>
      </div>
    </Card>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted mb-1 small">Total Users</p>
              <h4 class="mb-0">{{ stats.total || 0 }}</h4>
            </div>
            <i class="bi bi-people text-primary opacity-25 display-6"></i>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted mb-1 small">Active Users</p>
              <h4 class="mb-0 text-success">{{ stats.active || 0 }}</h4>
            </div>
            <i class="bi bi-person-check text-success opacity-25 display-6"></i>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted mb-1 small">Inactive Users</p>
              <h4 class="mb-0 text-warning">{{ stats.inactive || 0 }}</h4>
            </div>
            <i class="bi bi-person-x text-warning opacity-25 display-6"></i>
          </div>
        </Card>
      </div>
      <div class="col-md-3">
        <Card>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-muted mb-1 small">Online Now</p>
              <h4 class="mb-0 text-info">{{ stats.online || 0 }}</h4>
            </div>
            <i class="bi bi-person-circle text-info opacity-25 display-6"></i>
          </div>
        </Card>
      </div>
    </div>

    <!-- Users Table -->
    <Card>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>User</th>
              <th>Email</th>
              <th>Role(s)</th>
              <th>Status</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users.data" :key="user.id">
              <td>
                <div class="d-flex align-items-center">
                  <div class="avatar bg-primary text-white rounded-circle me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    {{ getInitials(user.name) }}
                  </div>
                  <div>
                    <div class="fw-bold">{{ user.name }}</div>
                    <small class="text-muted">ID: {{ user.id }}</small>
                  </div>
                </div>
              </td>
              <td>{{ user.email }}</td>
              <td>
                <Badge
                  v-for="role in user.roles"
                  :key="role.id"
                  :variant="getRoleVariant(role.slug)"
                  class="me-1"
                >
                  {{ role.name }}
                </Badge>
              </td>
              <td>
                <Badge :variant="user.status === 'active' ? 'success' : user.status === 'suspended' ? 'danger' : 'warning'">
                  {{ user.status }}
                </Badge>
              </td>
              <td>
                <small v-if="user.last_login_at">
                  {{ formatDate(user.last_login_at) }}
                </small>
                <span v-else class="text-muted">Never</span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <Link :href="`/users/${user.id}`" class="btn btn-outline-primary" title="View">
                    <i class="bi bi-eye"></i>
                  </Link>
                  <Link :href="`/users/${user.id}/edit`" class="btn btn-outline-warning" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </Link>
                  <button
                    @click="toggleStatus(user)"
                    class="btn btn-outline-secondary"
                    :title="user.status === 'active' ? 'Deactivate' : 'Activate'"
                  >
                    <i :class="user.status === 'active' ? 'bi bi-pause-circle' : 'bi bi-play-circle'"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="users.data.length === 0">
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                No users found
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.data.length > 0" class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
          Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
        </div>
        <nav>
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: !users.prev_page_url }">
              <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="page-link">Previous</Link>
              <span v-else class="page-link">Previous</span>
            </li>
            <li
              v-for="page in paginationPages"
              :key="page"
              class="page-item"
              :class="{ active: page === users.current_page }"
            >
              <Link :href="`${users.path}?page=${page}`" class="page-link">{{ page }}</Link>
            </li>
            <li class="page-item" :class="{ disabled: !users.next_page_url }">
              <Link v-if="users.next_page_url" :href="users.next_page_url" class="page-link">Next</Link>
              <span v-else class="page-link">Next</span>
            </li>
          </ul>
        </nav>
      </div>
    </Card>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  users: Object,
  stats: Object,
  roles: Array,
  filters: Object
});

const filters = ref({
  search: props.filters?.search || '',
  role: props.filters?.role || '',
  status: props.filters?.status || ''
});

const roleOptions = computed(() => [
  { value: '', label: 'All Roles' },
  ...props.roles.map(role => ({ value: role.id, label: role.name }))
]);

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' }
];

const paginationPages = computed(() => {
  const pages = [];
  const current = props.users.current_page;
  const last = props.users.last_page;
  const delta = 2;

  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i);
  }

  return pages;
});

const applyFilters = () => {
  router.get('/users', filters.value, {
    preserveState: true,
    preserveScroll: true
  });
};

const toggleStatus = (user) => {
  if (confirm(`Are you sure you want to ${user.status === 'active' ? 'deactivate' : 'activate'} this user?`)) {
    router.put(`/users/${user.id}/status`, {
      status: user.status === 'active' ? 'inactive' : 'active'
    }, {
      preserveScroll: true
    });
  }
};

const getInitials = (name) => {
  return name
    .split(' ')
    .map(word => word[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

const getRoleVariant = (slug) => {
  const variants = {
    'provider-super-admin': 'danger',
    'institution-admin': 'primary',
    'supervisor': 'info',
    'credit-officer': 'success',
    'credit-analyst': 'warning',
    'underwriter': 'secondary',
    'collections-officer': 'dark',
    'accountant': 'light'
  };
  return variants[slug] || 'secondary';
};

const formatDate = (date) => {
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
