<template>
  <AppLayout :breadcrumb="[
    { label: 'Users', href: '/users' },
    { label: 'Edit' }
  ]">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="mb-1">Edit User</h2>
            <p class="text-muted mb-0">Update user information and roles</p>
          </div>
          <div>
            <Link :href="`/users/${user.id}`" class="btn btn-outline-secondary me-2">
              <i class="bi bi-eye me-1"></i>View Profile
            </Link>
            <Link href="/users" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-1"></i>Back to Users
            </Link>
          </div>
        </div>

        <form @submit.prevent="submit">
          <!-- Personal Information -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-person me-2"></i>Personal Information
            </template>
            
            <div class="row g-3">
              <div class="col-md-12">
                <Input
                  v-model="form.name"
                  label="Full Name"
                  placeholder="Enter full name"
                  required
                  :error="form.errors.name"
                />
              </div>
              
              <div class="col-md-6">
                <Input
                  v-model="form.email"
                  type="email"
                  label="Email Address"
                  placeholder="user@example.com"
                  required
                  :error="form.errors.email"
                />
              </div>
              
              <div class="col-md-6">
                <Input
                  v-model="form.phone"
                  type="tel"
                  label="Phone Number"
                  placeholder="+255 XXX XXX XXX"
                  :error="form.errors.phone"
                />
              </div>
            </div>
          </Card>

          <!-- Account Status -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-shield-lock me-2"></i>Account Status
            </template>
            
            <div class="row g-3">
              <div class="col-md-6">
                <Select
                  v-model="form.status"
                  label="Status"
                  :options="statusOptions"
                  required
                  :error="form.errors.status"
                />
              </div>
              
              <div class="col-md-6" v-if="institutions.length > 0 && canChangeInstitution">
                <Select
                  v-model="form.institution_id"
                  label="Institution"
                  :options="institutionOptions"
                  required
                  :error="form.errors.institution_id"
                />
              </div>
              
              <div class="col-md-6" v-if="!canChangeInstitution">
                <label class="form-label">Institution</label>
                <input
                  type="text"
                  class="form-control"
                  :value="user.institution?.name"
                  readonly
                  disabled
                />
                <small class="form-text text-muted">Institution cannot be changed</small>
              </div>
            </div>
          </Card>

          <!-- Password Change -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-key me-2"></i>Change Password
            </template>
            
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              Leave password fields empty to keep the current password
            </div>
            
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.password"
                  type="password"
                  label="New Password"
                  placeholder="Enter new password"
                  :error="form.errors.password"
                  help="Minimum 8 characters"
                />
              </div>
              
              <div class="col-md-6">
                <Input
                  v-model="form.password_confirmation"
                  type="password"
                  label="Confirm New Password"
                  placeholder="Re-enter new password"
                  :error="form.errors.password_confirmation"
                />
              </div>
              
              <div class="col-12">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="require-password-change"
                    v-model="form.require_password_change"
                  />
                  <label class="form-check-label" for="require-password-change">
                    Require password change on next login
                  </label>
                </div>
              </div>
            </div>
          </Card>

          <!-- Role Assignment -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-shield-check me-2"></i>Role Assignment
            </template>
            
            <p class="text-muted small mb-3">
              Select one or more roles for this user. Changes take effect immediately.
            </p>
            
            <div class="row g-3">
              <div
                v-for="role in roles"
                :key="role.id"
                class="col-md-6"
              >
                <div class="form-check p-3 border rounded" :class="{ 'border-primary bg-light': form.roles.includes(role.id) }">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    :id="`role-${role.id}`"
                    :value="role.id"
                    v-model="form.roles"
                  />
                  <label class="form-check-label w-100" :for="`role-${role.id}`">
                    <div class="d-flex justify-content-between align-items-start">
                      <div>
                        <div class="fw-bold">{{ role.name }}</div>
                        <small class="text-muted">{{ role.description }}</small>
                      </div>
                      <Badge :variant="getRoleVariant(role.slug)" class="ms-2">
                        {{ role.slug }}
                      </Badge>
                    </div>
                  </label>
                </div>
              </div>
            </div>
            
            <div v-if="form.errors.roles" class="text-danger small mt-2">
              {{ form.errors.roles }}
            </div>
          </Card>

          <!-- Login History Info -->
          <Card class="mb-4 bg-light">
            <div class="row g-3 text-center">
              <div class="col-md-4">
                <div class="text-muted small">Last Login</div>
                <div class="fw-bold">
                  {{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}
                </div>
              </div>
              <div class="col-md-4">
                <div class="text-muted small">Account Created</div>
                <div class="fw-bold">{{ formatDate(user.created_at) }}</div>
              </div>
              <div class="col-md-4">
                <div class="text-muted small">Last Updated</div>
                <div class="fw-bold">{{ formatDate(user.updated_at) }}</div>
              </div>
            </div>
          </Card>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between">
            <button
              type="button"
              @click="confirmDelete"
              class="btn btn-outline-danger"
              v-if="canDelete"
            >
              <i class="bi bi-trash me-1"></i>Delete User
            </button>
            
            <div class="ms-auto">
              <Link href="/users" class="btn btn-outline-secondary me-2">
                Cancel
              </Link>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="form.processing"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                Update User
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  user: Object,
  roles: Array,
  institutions: Array,
  canDelete: {
    type: Boolean,
    default: false
  },
  canChangeInstitution: {
    type: Boolean,
    default: false
  }
});

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
  password: '',
  password_confirmation: '',
  status: props.user.status,
  institution_id: props.user.institution_id,
  roles: props.user.roles.map(role => role.id),
  require_password_change: false
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' }
];

const institutionOptions = computed(() => 
  props.institutions.map(inst => ({
    value: inst.id,
    label: inst.name
  }))
);

const submit = () => {
  form.put(`/users/${props.user.id}`, {
    onSuccess: () => {
      // Success notification will be handled by the controller
    }
  });
};

const confirmDelete = () => {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    router.delete(`/users/${props.user.id}`);
  }
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
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
