<template>
  <AppLayout :breadcrumb="[
    { label: 'Applications', href: '/applications' },
    { label: 'Create New Application' }
  ]">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- Page Header -->
        <div class="mb-4">
          <h2 class="mb-1">New Loan Application</h2>
          <p class="text-muted mb-0">Create a new loan application</p>
        </div>

        <form @submit.prevent="submit">
          <!-- Customer Selection -->
          <Card title="Customer Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-12">
                <Select
                  v-model="form.customer_id"
                  label="Select Customer"
                  :options="customerOptions"
                  :error="form.errors.customer_id"
                  required
                />
                <small class="text-muted">Search and select the customer applying for the loan</small>
              </div>
            </div>
          </Card>

          <!-- Loan Product Selection -->
          <Card title="Loan Product" class="mb-4">
            <div class="row g-3">
              <div class="col-md-12">
                <Select
                  v-model="form.loan_product_id"
                  label="Loan Product"
                  :options="loanProductOptions"
                  :error="form.errors.loan_product_id"
                  required
                />
              </div>
              <div v-if="selectedProduct" class="col-md-12">
                <div class="alert alert-info">
                  <strong>{{ selectedProduct.name }}</strong>
                  <ul class="mb-0 mt-2">
                    <li>Interest Rate: {{ selectedProduct.annual_interest_rate }}% {{ selectedProduct.interest_model === 'reducing_balance' ? '(Reducing Balance)' : '(Flat Rate)' }}</li>
                    <li>Loan Amount: {{ formatCurrency(selectedProduct.min_loan_amount) }} - {{ formatCurrency(selectedProduct.max_loan_amount) }}</li>
                    <li>Tenure: {{ selectedProduct.min_tenure_months }} - {{ selectedProduct.max_tenure_months }} months</li>
                  </ul>
                </div>
              </div>
            </div>
          </Card>

          <!-- Loan Details -->
          <Card title="Loan Details" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.requested_amount"
                  label="Requested Loan Amount"
                  type="number"
                  step="0.01"
                  :error="form.errors.requested_amount"
                  required
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.requested_tenure_months"
                  label="Requested Tenure (Months)"
                  type="number"
                  :error="form.errors.requested_tenure_months"
                  required
                />
              </div>
            </div>
          </Card>

          <!-- Property Information -->
          <Card title="Property Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.property_type"
                  label="Property Type"
                  :error="form.errors.property_type"
                  placeholder="e.g., Residential, Commercial"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.property_value"
                  label="Property Value"
                  type="number"
                  step="0.01"
                  :error="form.errors.property_value"
                />
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Property Address</label>
                  <textarea
                    v-model="form.property_address"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.property_address }"
                    rows="3"
                  ></textarea>
                  <div v-if="form.errors.property_address" class="invalid-feedback">
                    {{ form.errors.property_address }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Additional Notes -->
          <Card title="Additional Information" class="mb-4">
            <div class="row g-3">
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Notes</label>
                  <textarea
                    v-model="form.notes"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.notes }"
                    rows="3"
                    placeholder="Any additional information about the application"
                  ></textarea>
                  <div v-if="form.errors.notes" class="invalid-feedback">
                    {{ form.errors.notes }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Form Actions -->
          <div class="d-flex gap-2 justify-content-end">
            <Link href="/applications" class="btn btn-outline-secondary">
              Cancel
            </Link>
            <button
              type="button"
              @click="saveDraft"
              class="btn btn-outline-primary"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
              Save as Draft
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
              Submit Application
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Input from '@/Components/UI/Form/Input.vue';
import Select from '@/Components/UI/Form/Select.vue';

const props = defineProps({
  customers: Array,
  loanProducts: Array
});

const form = useForm({
  customer_id: '',
  loan_product_id: '',
  requested_amount: '',
  requested_tenure_months: '',
  property_type: '',
  property_value: '',
  property_address: '',
  notes: '',
  save_as_draft: false
});

const customerOptions = computed(() => {
  return [
    { value: '', label: 'Select Customer' },
    ...(props.customers || []).map(c => ({
      value: c.id,
      label: `${c.full_name} (${c.customer_code})`
    }))
  ];
});

const loanProductOptions = computed(() => {
  return [
    { value: '', label: 'Select Loan Product' },
    ...(props.loanProducts || []).map(p => ({
      value: p.id,
      label: `${p.name} - ${p.annual_interest_rate}%`
    }))
  ];
});

const selectedProduct = computed(() => {
  if (!form.loan_product_id) return null;
  return props.loanProducts.find(p => p.id == form.loan_product_id);
});

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'TZS',
    minimumFractionDigits: 0
  }).format(amount);
};

const saveDraft = () => {
  form.save_as_draft = true;
  form.post('/applications');
};

const submit = () => {
  form.save_as_draft = false;
  form.post('/applications');
};
</script>
