<template>
  <div class="min-vh-100 d-flex flex-column">
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container-fluid">
        <!-- Logo/Brand -->
        <a class="navbar-brand fw-bold" href="/dashboard">
          <i class="bi bi-bank2 me-2"></i>
          {{ $page.props.auth?.institution?.name || 'Mortgage Platform' }}
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <Link href="/dashboard" class="nav-link" :class="{ active: $page.url.startsWith('/dashboard') }">
                <i class="bi bi-speedometer2 me-1"></i>Dashboard
              </Link>
            </li>

            <!-- Loan Products -->
            <li class="nav-item">
              <Link href="/loan-products" class="nav-link" :class="{ active: $page.url.startsWith('/loan-products') }">
                <i class="bi bi-box-seam me-1"></i>Loan Products
              </Link>
            </li>

            <!-- Pre-Qualification -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" 
                 data-bs-toggle="dropdown" :class="{ active: $page.url.startsWith('/pre-qualify') || $page.url.startsWith('/prospects') }">
                <i class="bi bi-clipboard-check me-1"></i>Pre-Qualify
              </a>
              <ul class="dropdown-menu">
                <li><Link class="dropdown-item" href="/pre-qualify">New Pre-Qualification</Link></li>
                <li><Link class="dropdown-item" href="/prospects">All Prospects</Link></li>
                <li><hr class="dropdown-divider"></li>
                <li><Link class="dropdown-item" href="/prospects?status=eligibility_passed">Eligible Prospects</Link></li>
                <li><Link class="dropdown-item" href="/prospects?status=eligibility_failed">Rejected Prospects</Link></li>
              </ul>
            </li>

            <!-- Applications Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" 
                 data-bs-toggle="dropdown" :class="{ active: $page.url.startsWith('/applications') }">
                <i class="bi bi-file-earmark-text me-1"></i>Applications
              </a>
              <ul class="dropdown-menu">
                <li><Link class="dropdown-item" href="/applications">All Applications</Link></li>
                <li><Link class="dropdown-item" href="/applications/create">New Application</Link></li>
                <li><hr class="dropdown-divider"></li>
                <li><Link class="dropdown-item" href="/applications/pending">Pending Review</Link></li>
              </ul>
            </li>

            <!-- Loans Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" 
                 data-bs-toggle="dropdown" :class="{ active: $page.url.startsWith('/loans') }">
                <i class="bi bi-cash-coin me-1"></i>Loans
              </a>
              <ul class="dropdown-menu">
                <li><Link class="dropdown-item" href="/loans">Active Loans</Link></li>
                <li><Link class="dropdown-item" href="/loans/disbursements">Disbursements</Link></li>
                <li><Link class="dropdown-item" href="/loans/repayments">Repayments</Link></li>
              </ul>
            </li>

            <!-- Customers -->
            <li class="nav-item">
              <Link href="/customers" class="nav-link" :class="{ active: $page.url.startsWith('/customers') }">
                <i class="bi bi-people me-1"></i>Customers
              </Link>
            </li>

            <!-- User Management -->
            <li class="nav-item">
              <Link href="/users" class="nav-link" :class="{ active: $page.url.startsWith('/users') }">
                <i class="bi bi-person-badge me-1"></i>Users
              </Link>
            </li>

            <!-- Collections -->
            <li class="nav-item">
              <Link href="/collections" class="nav-link" :class="{ active: $page.url.startsWith('/collections') }">
                <i class="bi bi-telephone me-1"></i>Collections
              </Link>
            </li>

            <!-- Reports Dropdown -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" 
                 data-bs-toggle="dropdown" :class="{ active: $page.url.startsWith('/reports') }">
                <i class="bi bi-graph-up me-1"></i>Reports
              </a>
              <ul class="dropdown-menu">
                <li><Link class="dropdown-item" href="/reports/portfolio">Portfolio Reports</Link></li>
                <li><Link class="dropdown-item" href="/reports/analytics">Analytics</Link></li>
                <li><Link class="dropdown-item" href="/reports/exports">Exports</Link></li>
              </ul>
            </li>
          </ul>

          <!-- Right Side - User Menu -->
          <ul class="navbar-nav ms-auto">
            <!-- Notifications -->
            <li class="nav-item dropdown">
              <a class="nav-link position-relative" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-5"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                      v-if="notifications > 0">
                  {{ notifications }}
                </span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><h6 class="dropdown-header">Notifications</h6></li>
                <li><a class="dropdown-item" href="#">
                  <small class="text-muted">No new notifications</small>
                </a></li>
              </ul>
            </li>

            <!-- User Dropdown -->
            <li class="nav-item dropdown" v-if="$page.props.auth?.user">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-1"></i>
                {{ $page.props.auth?.user?.name }}
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><Link class="dropdown-item" href="/profile">
                  <i class="bi bi-person me-2"></i>Profile
                </Link></li>
                <li><Link class="dropdown-item" href="/settings">
                  <i class="bi bi-gear me-2"></i>Settings
                </Link></li>
                <li><hr class="dropdown-divider"></li>
                <li><Link class="dropdown-item" href="/logout" method="post" as="button">
                  <i class="bi bi-box-arrow-right me-2"></i>Logout
                </Link></li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="bg-light border-bottom" v-if="breadcrumb">
      <div class="container-fluid">
        <nav aria-label="breadcrumb" class="py-2">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">
              <Link href="/dashboard" class="text-decoration-none">
                <i class="bi bi-house-door me-1"></i>Home
              </Link>
            </li>
            <li 
              v-for="(item, index) in breadcrumbItems" 
              :key="index"
              class="breadcrumb-item"
              :class="{ 'active': index === breadcrumbItems.length - 1 }"
            >
              <Link 
                v-if="item.href && index !== breadcrumbItems.length - 1" 
                :href="item.href"
                class="text-decoration-none"
              >
                {{ item.label }}
              </Link>
              <span v-else>{{ item.label }}</span>
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
      <transition-group name="toast" tag="div">
        <div 
          v-for="toast in toasts" 
          :key="toast.id"
          class="toast show align-items-center border-0 mb-2"
          :class="{
            'text-bg-success': toast.type === 'success',
            'text-bg-danger': toast.type === 'error',
            'text-bg-warning': toast.type === 'warning',
            'text-bg-info': toast.type === 'info'
          }"
          role="alert"
        >
          <div class="d-flex">
            <div class="toast-body">
              <i 
                class="me-2"
                :class="{
                  'bi bi-check-circle': toast.type === 'success',
                  'bi bi-exclamation-triangle': toast.type === 'error',
                  'bi bi-exclamation-circle': toast.type === 'warning',
                  'bi bi-info-circle': toast.type === 'info'
                }"
              ></i>
              {{ toast.message }}
            </div>
            <button 
              type="button" 
              class="btn-close btn-close-white me-2 m-auto" 
              @click="removeToast(toast.id)"
            ></button>
          </div>
        </div>
      </transition-group>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 bg-light">
      <div class="container-fluid py-4">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 mt-auto">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6 text-muted small">
            © {{ new Date().getFullYear() }} Mortgage Platform. All rights reserved.
          </div>
          <div class="col-md-6 text-end small">
            <a href="/help" class="text-decoration-none me-3">Help</a>
            <a href="/privacy" class="text-decoration-none me-3">Privacy</a>
            <a href="/terms" class="text-decoration-none">Terms</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
  breadcrumb: [String, Array]
});

// Parse breadcrumb into array format
const breadcrumbItems = computed(() => {
  if (!props.breadcrumb) return [];
  
  // If already an array, return it
  if (Array.isArray(props.breadcrumb)) {
    return props.breadcrumb;
  }
  
  // If string with "/", parse it into array
  if (typeof props.breadcrumb === 'string' && props.breadcrumb.includes('/')) {
    return props.breadcrumb.split('/').map(item => ({
      label: item.trim(),
      href: null
    }));
  }
  
  // Single string breadcrumb
  return [{ label: props.breadcrumb, href: null }];
});

const notifications = ref(0); // Would come from API/props in real app
const toasts = ref([]);
let toastIdCounter = 0;

const addToast = (type, message) => {
  const id = ++toastIdCounter;
  toasts.value.push({ id, type, message });
  
  // Auto-dismiss after 5 seconds
  setTimeout(() => {
    removeToast(id);
  }, 5000);
};

const removeToast = (id) => {
  const index = toasts.value.findIndex(t => t.id === id);
  if (index > -1) {
    toasts.value.splice(index, 1);
  }
};

// Watch for flash messages from Inertia
const page = usePage();
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) {
      addToast('success', flash.success);
    }
    if (flash?.error) {
      addToast('error', flash.error);
    }
    if (flash?.warning) {
      addToast('warning', flash.warning);
    }
    if (flash?.info) {
      addToast('info', flash.info);
    }
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
/* Breadcrumb styling */
.breadcrumb-item a {
  color: #0d6efd;
  transition: color 0.2s ease;
}

.breadcrumb-item a:hover {
  color: #0a58ca;
  text-decoration: underline !important;
}

.breadcrumb-item.active {
  color: #6c757d;
  font-weight: 500;
}

.breadcrumb-item + .breadcrumb-item::before {
  color: #6c757d;
}

/* Toast transition animations */
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateX(100px);
}

.toast-leave-to {
  opacity: 0;
  transform: translateX(100px);
}

.toast-move {
  transition: transform 0.3s ease;
}
</style>
