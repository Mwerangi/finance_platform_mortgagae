<template>
  <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-5">
          <div class="card shadow">
            <div class="card-body p-5">
              <!-- Logo/Brand -->
              <div class="text-center mb-4">
                <i class="bi bi-bank2 text-primary display-3"></i>
                <h3 class="mt-3 mb-1">Mortgage Platform</h3>
                <p class="text-muted">Sign in to your account</p>
              </div>

              <!-- Login Form -->
              <form @submit.prevent="submit">
                <!-- Email -->
                <div class="mb-3">
                  <label for="email" class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input
                      id="email"
                      type="email"
                      class="form-control"
                      :class="{ 'is-invalid': form.errors.email }"
                      v-model="form.email"
                      placeholder="Enter your email"
                      required
                      autofocus
                    />
                    <div class="invalid-feedback" v-if="form.errors.email">
                      {{ form.errors.email }}
                    </div>
                  </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input
                      id="password"
                      type="password"
                      class="form-control"
                      :class="{ 'is-invalid': form.errors.password }"
                      v-model="form.password"
                      placeholder="Enter your password"
                      required
                    />
                    <div class="invalid-feedback" v-if="form.errors.password">
                      {{ form.errors.password }}
                    </div>
                  </div>
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-3">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    id="remember"
                    v-model="form.remember"
                  />
                  <label class="form-check-label" for="remember">
                    Remember me
                  </label>
                </div>

                <!-- Submit Button -->
                <button
                  type="submit"
                  class="btn btn-primary w-100 mb-3"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Signing in...
                  </span>
                  <span v-else>
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Sign In
                  </span>
                </button>

                <!-- Forgot Password Link -->
                <div class="text-center">
                  <a href="/forgot-password" class="text-decoration-none small">
                    Forgot your password?
                  </a>
                </div>
              </form>
            </div>
          </div>

          <!-- Footer -->
          <div class="text-center mt-4 text-muted small">
            <p>&copy; {{ new Date().getFullYear() }} Mortgage Platform. All rights reserved.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
  remember: false
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
