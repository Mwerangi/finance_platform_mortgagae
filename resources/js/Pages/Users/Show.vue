<template>
  <AppLayout :breadcrumb="`User: ${user.name}`">
    <div class="row">
      <!-- Left Column - Profile Overview -->
      <div class="col-lg-4 mb-4">
        <!-- Profile Card -->
        <Card class="text-center">
          <div class="avatar bg-primary text-white rounded-circle mx-auto mb-3" style="width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem;">
            {{ getInitials(user.name) }}
          </div>
          
          <h4 class="mb-1">{{ user.name }}</h4>
          <p class="text-muted mb-3">{{ user.email }}</p>
          
          <Badge :variant="user.status === 'active' ? 'success' : user.status === 'suspended' ? 'danger' : 'warning'" class="mb-3">
            {{ user.status }}
          </Badge>
          
          <div class="d-grid gap-2 mt-3">
            <Link :href="`/users/${user.id}/edit`" class="btn btn-primary">
              <i class="bi bi-pencil me-1"></i>Edit User
            </Link>
            <button @click="sendPasswordReset" class="btn btn-outline-secondary" :disabled="sending">
              <span v-if="sending" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-key me-1"></i>
              Send Password Reset
            </button>
          </div>
        </Card>

        <!-- Contact Information -->
        <Card class="mt-3">
          <template #title>
            <i class="bi bi-info-circle me-2"></i>Contact Information
          </template>
          
          <div class="mb-3">
            <small class="text-muted d-block">Email</small>
            <a :href="`mailto:${user.email}`" class="text-decoration-none">
              <i class="bi bi-envelope me-1"></i>{{ user.email }}
            </a>
          </div>
          
          <div class="mb-3" v-if="user.phone">
            <small class="text-muted d-block">Phone</small>
            <a :href="`tel:${user.phone}`" class="text-decoration-none">
              <i class="bi bi-telephone me-1"></i>{{ user.phone }}
            </a>
          </div>
          
          <div>
            <small class="text-muted d-block">Institution</small>
            <Link v-if="user.institution" :href="`/institutions/${user.institution.id}`" class="text-decoration-none">
              <i class="bi bi-building me-1"></i>{{ user.institution.name }}
            </Link>
            <span v-else class="text-muted">N/A</span>
          </div>
        </Card>

        <!-- Account Stats -->
        <Card class="mt-3">
          <template #title>
            <i class="bi bi-graph-up me-2"></i>Account Statistics
          </template>
          
          <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <span class="text-muted">Total Sessions</span>
            <Badge variant="primary">{{ stats.total_sessions || 0 }}</Badge>
          </div>
          
          <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
            <span class="text-muted">Applications Created</span>
            <Badge variant="success">{{ stats.applications_created || 0 }}</Badge>
          </div>
          
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Last Activity</span>
            <span class="fw-bold">{{ user.last_login_at ? formatDate(user.last_login_at) : 'Never' }}</span>
          </div>
        </Card>
      </div>

      <!-- Right Column - Detailed Information -->
      <div class="col-lg-8">
        <!-- Roles & Permissions -->
        <Card class="mb-4">
          <template #title>
            <i class="bi bi-shield-check me-2"></i>Roles & Permissions
          </template>
          
          <div class="mb-4">
            <h6 class="mb-3">Assigned Roles</h6>
            <div class="d-flex flex-wrap gap-2">
              <Badge
                v-for="role in user.roles"
                :key="role.id"
                :variant="getRoleVariant(role.slug)"
                size="lg"
              >
                <i class="bi bi-shield-fill-check me-1"></i>
                {{ role.name }}
              </Badge>
              <span v-if="user.roles.length === 0" class="text-muted">No roles assigned</span>
            </div>
          </div>
          
          <hr />
          
          <div>
            <h6 class="mb-3">Permissions</h6>
            <div class="row g-2">
              <div
                v-for="permission in user.permissions"
                :key="permission.id"
                class="col-md-6"
              >
                <div class="d-flex align-items-center p-2 border rounded">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  <div>
                    <div class="fw-bold small">{{ permission.name }}</div>
                    <small class="text-muted">{{ permission.slug }}</small>
                  </div>
                </div>
              </div>
              <div v-if="user.permissions.length === 0" class="col-12">
                <p class="text-muted mb-0">No direct permissions assigned. Permissions inherited from roles.</p>
              </div>
            </div>
          </div>
        </Card>

        <!-- Activity Timeline -->
        <Card class="mb-4">
          <template #title>
            <i class="bi bi-clock-history me-2"></i>Recent Activity
          </template>
          
          <div class="timeline">
            <div
              v-for="activity in activities"
              :key="activity.id"
              class="timeline-item mb-3 pb-3 border-bottom"
            >
              <div class="d-flex">
                <div class="timeline-icon me-3">
                  <div class="badge rounded-circle" :class="`bg-${getActivityColor(activity.type)}`" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <i :class="getActivityIcon(activity.type)"></i>
                  </div>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <div class="fw-bold">{{ activity.description }}</div>
                      <small class="text-muted">{{ activity.subject_type }}</small>
                    </div>
                    <small class="text-muted">{{ formatDate(activity.created_at) }}</small>
                  </div>
                  <div v-if="activity.properties" class="mt-2">
                    <code class="small">{{ JSON.stringify(activity.properties, null, 2) }}</code>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="activities.length === 0" class="text-center py-4 text-muted">
              <i class="bi bi-inbox display-4 d-block mb-2"></i>
              No activity recorded yet
            </div>
          </div>
          
          <div v-if="activities.length > 0" class="text-center mt-3">
            <Link :href="`/users/${user.id}/activity`" class="btn btn-outline-primary btn-sm">
              View Full Activity History
            </Link>
          </div>
        </Card>

        <!-- Login Sessions -->
        <Card>
          <template #title>
            <i class="bi bi-laptop me-2"></i>Active Sessions
          </template>
          
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <thead>
                <tr>
                  <th>Device</th>
                  <th>IP Address</th>
                  <th>Location</th>
                  <th>Last Activity</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="session in sessions" :key="session.id">
                  <td>
                    <i :class="getDeviceIcon(session.device)" class="me-1"></i>
                    {{ session.device || 'Unknown' }}
                  </td>
                  <td>{{ session.ip_address }}</td>
                  <td>{{ session.location || 'Unknown' }}</td>
                  <td>{{ formatDate(session.last_activity) }}</td>
                  <td>
                    <button
                      @click="revokeSession(session.id)"
                      class="btn btn-sm btn-outline-danger"
                      v-if="!session.is_current"
                    >
                      Revoke
                    </button>
                    <Badge variant="success" v-else>Current</Badge>
                  </td>
                </tr>
                <tr v-if="sessions.length === 0">
                  <td colspan="5" class="text-center text-muted py-3">
                    No active sessions
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
  user: Object,
  activities: Array,
  sessions: Array,
  stats: Object
});

const sending = ref(false);

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

const getActivityColor = (type) => {
  const colors = {
    'created': 'success',
    'updated': 'primary',
    'deleted': 'danger',
    'login': 'info',
    'logout': 'secondary'
  };
  return colors[type] || 'secondary';
};

const getActivityIcon = (type) => {
  const icons = {
    'created': 'bi bi-plus-circle',
    'updated': 'bi bi-pencil',
    'deleted': 'bi bi-trash',
    'login': 'bi bi-box-arrow-in-right',
    'logout': 'bi bi-box-arrow-left'
  };
  return icons[type] || 'bi bi-circle';
};

const getDeviceIcon = (device) => {
  if (!device) return 'bi bi-question-circle';
  const lower = device.toLowerCase();
  if (lower.includes('mobile') || lower.includes('phone')) return 'bi bi-phone';
  if (lower.includes('tablet')) return 'bi bi-tablet';
  return 'bi bi-laptop';
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

const sendPasswordReset = () => {
  if (confirm('Send password reset email to this user?')) {
    sending.value = true;
    router.post(`/users/${props.user.id}/password-reset`, {}, {
      onFinish: () => {
        sending.value = false;
      }
    });
  }
};

const revokeSession = (sessionId) => {
  if (confirm('Revoke this session?')) {
    router.delete(`/sessions/${sessionId}`, {
      preserveScroll: true
    });
  }
};
</script>

<style scoped>
.avatar {
  font-weight: 600;
}

.timeline-item:last-child {
  border-bottom: none !important;
}
</style>
