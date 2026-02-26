<template>
  <AppLayout :breadcrumb="[
    { label: 'Loan Products', href: '/loan-products' },
    { label: 'Edit' }
  ]">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h2 class="mb-1">Edit Loan Product</h2>
            <p class="text-muted mb-0">Update loan product configuration</p>
          </div>
          <div class="d-flex gap-2">
            <button
              v-if="product.status === 'inactive'"
              @click="activateProduct"
              class="btn btn-success"
              :disabled="activating"
            >
              <i class="bi bi-check-circle me-1"></i>Activate
            </button>
            <button
              v-else
              @click="deactivateProduct"
              class="btn btn-warning"
              :disabled="deactivating"
            >
              <i class="bi bi-pause-circle me-1"></i>Deactivate
            </button>
          </div>
        </div>

        <!-- Status Info -->
        <div v-if="product.activated_at" class="alert alert-info mb-4">
          <i class="bi bi-info-circle me-2"></i>
          <strong>Status:</strong> {{ product.status }}
          <span v-if="product.activated_at"> | Activated: {{ formatDate(product.activated_at) }}</span>
          <span v-if="product.deactivated_at"> | Deactivated: {{ formatDate(product.deactivated_at) }}</span>
        </div>

        <form @submit.prevent="submit">
          <!-- Basic Information -->
          <Card title="Basic Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.name"
                  label="Product Name"
                  :error="form.errors.name"
                  required
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.code"
                  label="Product Code"
                  :error="form.errors.code"
                  required
                  placeholder="e.g., MORT-STD-001"
                />
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.description }"
                    rows="3"
                  ></textarea>
                  <div v-if="form.errors.description" class="invalid-feedback">
                    {{ form.errors.description }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Interest Configuration -->
          <Card title="Interest Configuration" class="mb-4">
            <div class="row g-3">
              <div class="col-md-4">
                <Select
                  v-model="form.interest_model"
                  label="Interest Model"
                  :options="interestModelOptions"
                  :error="form.errors.interest_model"
                  required
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.annual_interest_rate"
                  label="Annual Interest Rate (%)"
                  type="number"
                  step="0.01"
                  :error="form.errors.annual_interest_rate"
                  required
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.rate_type"
                  label="Rate Type"
                  :error="form.errors.rate_type"
                  placeholder="e.g., Fixed, Variable"
                />
              </div>
            </div>
          </Card>

          <!-- Tenure & Amount Limits -->
          <Card title="Tenure & Amount Limits" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-bold">Tenure Range (Months)</label>
                <div class="row g-2">
                  <div class="col-6">
                    <Input
                      v-model="form.min_tenure_months"
                      label="Minimum"
                      type="number"
                      :error="form.errors.min_tenure_months"
                      required
                    />
                  </div>
                  <div class="col-6">
                    <Input
                      v-model="form.max_tenure_months"
                      label="Maximum"
                      type="number"
                      :error="form.errors.max_tenure_months"
                      required
                    />
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold">Loan Amount Range</label>
                <div class="row g-2">
                  <div class="col-6">
                    <Input
                      v-model="form.min_loan_amount"
                      label="Minimum"
                      type="number"
                      step="0.01"
                      :error="form.errors.min_loan_amount"
                      required
                    />
                  </div>
                  <div class="col-6">
                    <Input
                      v-model="form.max_loan_amount"
                      label="Maximum"
                      type="number"
                      step="0.01"
                      :error="form.errors.max_loan_amount"
                      required
                    />
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Policy Rules -->
          <Card title="Policy Rules" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.max_ltv_percentage"
                  label="Max Loan-to-Value (LTV) %"
                  type="number"
                  step="0.01"
                  :error="form.errors.max_ltv_percentage"
                  placeholder="e.g., 80.00"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.max_dti_percentage"
                  label="Max Debt-to-Income (DTI) %"
                  type="number"
                  step="0.01"
                  :error="form.errors.max_dti_percentage"
                  placeholder="e.g., 40.00"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.max_dsr_salary_percentage"
                  label="Max DSR (Salary) %"
                  type="number"
                  step="0.01"
                  :error="form.errors.max_dsr_salary_percentage"
                  placeholder="e.g., 33.00"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.max_dsr_business_percentage"
                  label="Max DSR (Business) %"
                  type="number"
                  step="0.01"
                  :error="form.errors.max_dsr_business_percentage"
                  placeholder="e.g., 50.00"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.business_safety_factor"
                  label="Business Safety Factor"
                  type="number"
                  step="0.01"
                  :error="form.errors.business_safety_factor"
                  placeholder="e.g., 0.70"
                />
              </div>
            </div>
          </Card>

          <!-- Fees -->
          <Card title="Fees" class="mb-4">
            <div v-for="(fee, index) in form.fees" :key="index" class="mb-3 p-3 border rounded bg-light">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <Input
                    v-model="fee.type"
                    label="Fee Type"
                    placeholder="e.g., Processing Fee"
                  />
                </div>
                <div class="col-md-3">
                  <Input
                    v-model="fee.amount"
                    label="Amount"
                    type="number"
                    step="0.01"
                  />
                </div>
                <div class="col-md-3">
                  <Input
                    v-model="fee.frequency"
                    label="Frequency"
                    placeholder="e.g., One-time"
                  />
                </div>
                <div class="col-md-2">
                  <button
                    type="button"
                    @click="removeFee(index)"
                    class="btn btn-danger w-100"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            <button type="button" @click="addFee" class="btn btn-outline-primary">
              <i class="bi bi-plus-circle me-1"></i>Add Fee
            </button>
          </Card>

          <!-- Penalties -->
          <Card title="Penalties" class="mb-4">
            <div v-for="(penalty, index) in form.penalties" :key="index" class="mb-3 p-3 border rounded bg-light">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <Input
                    v-model="penalty.type"
                    label="Penalty Type"
                    placeholder="e.g., Late Payment"
                  />
                </div>
                <div class="col-md-3">
                  <Input
                    v-model="penalty.amount"
                    label="Amount"
                    type="number"
                    step="0.01"
                  />
                </div>
                <div class="col-md-3">
                  <Input
                    v-model="penalty.trigger"
                    label="Trigger"
                    placeholder="e.g., 30 days overdue"
                  />
                </div>
                <div class="col-md-2">
                  <button
                    type="button"
                    @click="removePenalty(index)"
                    class="btn btn-danger w-100"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            <button type="button" @click="addPenalty" class="btn btn-outline-primary">
              <i class="bi bi-plus-circle me-1"></i>Add Penalty
            </button>
          </Card>

          <!-- Form Actions -->
          <div class="d-flex gap-2 justify-content-end">
            <Link href="/loan-products" class="btn btn-outline-secondary">
              Cancel
            </Link>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
              Update Product
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';

const props = defineProps({
  product: Object
});

const activating = ref(false);
const deactivating = ref(false);

const form = useForm({
  name: props.product.name,
  code: props.product.code,
  description: props.product.description,
  interest_model: props.product.interest_model,
  annual_interest_rate: props.product.annual_interest_rate,
  rate_type: props.product.rate_type,
  min_tenure_months: props.product.min_tenure_months,
  max_tenure_months: props.product.max_tenure_months,
  min_loan_amount: props.product.min_loan_amount,
  max_loan_amount: props.product.max_loan_amount,
  max_ltv_percentage: props.product.max_ltv_percentage,
  max_dsr_salary_percentage: props.product.max_dsr_salary_percentage,
  max_dti_percentage: props.product.max_dti_percentage,
  business_safety_factor: props.product.business_safety_factor,
  max_dsr_business_percentage: props.product.max_dsr_business_percentage,
  fees: props.product.fees || [],
  penalties: props.product.penalties || [],
  credit_policy: props.product.credit_policy || []
});

const interestModelOptions = [
  { value: '', label: 'Select Model' },
  { value: 'reducing_balance', label: 'Reducing Balance' },
  { value: 'flat_rate', label: 'Flat Rate' }
];

const addFee = () => {
  form.fees.push({ type: '', amount: '', frequency: '' });
};

const removeFee = (index) => {
  form.fees.splice(index, 1);
};

const addPenalty = () => {
  form.penalties.push({ type: '', amount: '', trigger: '' });
};

const removePenalty = (index) => {
  form.penalties.splice(index, 1);
};

const submit = () => {
  form.put(`/loan-products/${props.product.id}`);
};

const activateProduct = () => {
  if (confirm('Are you sure you want to activate this product?')) {
    activating.value = true;
    router.put(`/loan-products/${props.product.id}/activate`, {}, {
      preserveScroll: true,
      onFinish: () => activating.value = false
    });
  }
};

const deactivateProduct = () => {
  if (confirm('Are you sure you want to deactivate this product? No new applications can be created with this product.')) {
    deactivating.value = true;
    router.put(`/loan-products/${props.product.id}/deactivate`, {}, {
      preserveScroll: true,
      onFinish: () => deactivating.value = false
    });
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
