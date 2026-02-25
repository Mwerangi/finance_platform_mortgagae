<template>
  <AppLayout breadcrumb="Create User">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h2 class="mb-1">Create New User</h2>
            <p class="text-muted mb-0">Add a new user to the system</p>
          </div>
          <Link href="/users" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
          </Link>
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
                  help="This will be used for login"
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

          <!-- Account Information -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-key me-2"></i>Account Information
            </template>
            
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.password"
                  type="password"
                  label="Password"
                  placeholder="Enter password"
                  required
                  :error="form.errors.password"
                  help="Minimum 8 characters"
                />
              </div>
              
              <div class="col-md-6">
                <Input
                  v-model="form.password_confirmation"
                  type="password"
                  label="Confirm Password"
                  placeholder="Re-enter password"
                  required
                  :error="form.errors.password_confirmation"
                />
              </div>
              
              <div class="col-md-6">
                <Select
                  v-model="form.status"
                  label="Status"
                  :options="statusOptions"
                  required
                  :error="form.errors.status"
                />
              </div>
              
              <div class="col-md-6" v-if="institutions.length > 0">
                <Select
                  v-model="form.institution_id"
                  label="Institution"
                  :options="institutionOptions"
                  required
                  :error="form.errors.institution_id"
                  help="Select user's institution"
                />
              </div>
            </div>
          </Card>

          <!-- Role Assignment -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-shield-check me-2"></i>Role Assignment
            </template>
            
            <p class="text-muted small mb-3">
              Select one or more roles for this user. Roles determine what the user can access and do in the system.
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

          <!-- Additional Settings -->
          <Card class="mb-4">
            <template #title>
              <i class="bi bi-gear me-2"></i>Additional Settings
            </template>
            
            <div class="row g-3">
              <div class="col-12">
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="send-welcome-email"
                    v-model="form.send_welcome_email"
                  />
                  <label class="form-check-label" for="send-welcome-email">
                    Send welcome email with login instructions
                  </label>
                </div>
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
                    Require password change on first login
                  </label>
                </div>
              </div>
            </div>
          </Card>

          <!-- Form Actions -->
          <div class="d-flex justify-content-between">
            <Link href="/users" class="btn btn-outline-secondary">
              Cancel
            </Link>
            <div>
              <button
                type="button"
                @click="saveAndCreateAnother"
                class="btn btn-outline-primary me-2"
                :disabled="form.processing"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                Save & Create Another
              </button>
              <button
                type="submit"
                class="btn btn-primary"
                :disabled="form.processing"
              >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
                Create User
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
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  roles: Array,
  institutions: Array
});

const form = useForm({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
  status: 'active',
  institution_id: props.institutions.length > 0 ? props.institutions[0].id : null,
  roles: [],
  send_welcome_email: true,
  require_password_change: true
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const institutionOptions = computed(() => 
  props.institutions.map(inst => ({
    value: inst.id,
    label: inst.name
  }))
);

const submit = () => {
  form.post('/users', {
    onSuccess: () => {
      // Redirect will be handled by the controller
    }
  });
};

const saveAndCreateAnother = () => {
  form.post('/users', {
    onSuccess: () => {
      form.reset();
      form.clearErrors();
    }
  });
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
</script>
