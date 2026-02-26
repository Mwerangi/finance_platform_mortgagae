<template>
  <AppLayout breadcrumb="Customers / Edit">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h2 class="mb-1">Edit Customer</h2>
            <p class="text-muted mb-0">Update customer profile information</p>
          </div>
          <div class="d-flex gap-2">
            <Link :href="`/customers/${customer.id}`" class="btn btn-outline-secondary">
              <i class="bi bi-eye me-1"></i>View Profile
            </Link>
            <button
              v-if="!customer.kyc_verified"
              @click="verifyKyc"
              class="btn btn-success"
              :disabled="verifying"
            >
              <i class="bi bi-check-circle me-1"></i>Verify KYC
            </button>
          </div>
        </div>

        <!-- KYC Status -->
        <div v-if="customer.kyc_verified" class="alert alert-success mb-4">
          <i class="bi bi-check-circle me-2"></i>
          <strong>KYC Verified</strong> - Verified on {{ formatDate(customer.kyc_verified_at) }}
        </div>
        <div v-else class="alert alert-warning mb-4">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong>KYC Pending</strong> - Please verify customer identity documents
        </div>

        <form @submit.prevent="submit">
          <!-- Customer Type -->
          <Card title="Customer Type" class="mb-4">
            <Select
              v-model="form.customer_type"
              label="Customer Type"
              :options="customerTypeOptions"
              :error="form.errors.customer_type"
              required
            />
            <small class="text-muted">
              Customer Code: <code>{{ customer.customer_code }}</code>
            </small>
          </Card>

          <!-- Personal Information -->
          <Card title="Personal Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-4">
                <Input
                  v-model="form.first_name"
                  label="First Name"
                  :error="form.errors.first_name"
                  required
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.middle_name"
                  label="Middle Name"
                  :error="form.errors.middle_name"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.last_name"
                  label="Last Name"
                  :error="form.errors.last_name"
                  required
                />
              </div>
              <div class="col-md-3">
                <Input
                  v-model="form.date_of_birth"
                  label="Date of Birth"
                  type="date"
                  :error="form.errors.date_of_birth"
                  required
                />
              </div>
              <div class="col-md-3">
                <Select
                  v-model="form.gender"
                  label="Gender"
                  :options="genderOptions"
                  :error="form.errors.gender"
                  required
                />
              </div>
              <div class="col-md-3">
                <Select
                  v-model="form.marital_status"
                  label="Marital Status"
                  :options="maritalStatusOptions"
                  :error="form.errors.marital_status"
                />
              </div>
              <div class="col-md-3">
                <Input
                  v-model="form.national_id"
                  label="National ID"
                  :error="form.errors.national_id"
                  required
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.passport_number"
                  label="Passport Number"
                  :error="form.errors.passport_number"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.tin"
                  label="TIN (Tax ID)"
                  :error="form.errors.tin"
                />
              </div>
            </div>
          </Card>

          <!-- Contact Information -->
          <Card title="Contact Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.phone_primary"
                  label="Primary Phone"
                  type="tel"
                  :error="form.errors.phone_primary"
                  required
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.phone_secondary"
                  label="Secondary Phone"
                  type="tel"
                  :error="form.errors.phone_secondary"
                />
              </div>
              <div class="col-12">
                <Input
                  v-model="form.email"
                  label="Email Address"
                  type="email"
                  :error="form.errors.email"
                />
              </div>
            </div>
          </Card>

          <!-- Address Information -->
          <Card title="Address Information" class="mb-4">
            <div class="row g-3">
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Physical Address</label>
                  <textarea
                    v-model="form.physical_address"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.physical_address }"
                    rows="2"
                  ></textarea>
                  <div v-if="form.errors.physical_address" class="invalid-feedback">
                    {{ form.errors.physical_address }}
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.city"
                  label="City"
                  :error="form.errors.city"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.region"
                  label="Region"
                  :error="form.errors.region"
                />
              </div>
              <div class="col-md-4">
                <Input
                  v-model="form.postal_code"
                  label="Postal Code"
                  :error="form.errors.postal_code"
                />
              </div>
              <div class="col-md-12">
                <Input
                  v-model="form.country"
                  label="Country"
                  :error="form.errors.country"
                />
              </div>
            </div>
          </Card>

          <!-- Employment/Business Information -->
          <Card :title="getEmploymentCardTitle()" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6" v-if="form.customer_type === 'salary' || form.customer_type === 'mixed'">
                <Input
                  v-model="form.employer_name"
                  label="Employer Name"
                  :error="form.errors.employer_name"
                />
              </div>
              <div class="col-md-6" v-if="form.customer_type === 'business' || form.customer_type === 'mixed'">
                <Input
                  v-model="form.business_name"
                  label="Business Name"
                  :error="form.errors.business_name"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.occupation"
                  label="Occupation"
                  :error="form.errors.occupation"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.industry"
                  label="Industry"
                  :error="form.errors.industry"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.employment_start_date"
                  label="Employment Start Date"
                  type="date"
                  :error="form.errors.employment_start_date"
                />
              </div>
            </div>
          </Card>

          <!-- Next of Kin Information -->
          <Card title="Next of Kin Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Input
                  v-model="form.next_of_kin_name"
                  label="Full Name"
                  :error="form.errors.next_of_kin_name"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.next_of_kin_relationship"
                  label="Relationship"
                  :error="form.errors.next_of_kin_relationship"
                />
              </div>
              <div class="col-md-6">
                <Input
                  v-model="form.next_of_kin_phone"
                  label="Phone Number"
                  type="tel"
                  :error="form.errors.next_of_kin_phone"
                />
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Address</label>
                  <textarea
                    v-model="form.next_of_kin_address"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.next_of_kin_address }"
                    rows="2"
                  ></textarea>
                  <div v-if="form.errors.next_of_kin_address" class="invalid-feedback">
                    {{ form.errors.next_of_kin_address }}
                  </div>
                </div>
              </div>
            </div>
          </Card>

          <!-- Additional Information -->
          <Card title="Additional Information" class="mb-4">
            <div class="row g-3">
              <div class="col-md-6">
                <Select
                  v-model="form.status"
                  label="Status"
                  :options="statusOptions"
                  :error="form.errors.status"
                />
              </div>
              <div class="col-md-6">
                <label class="text-muted small d-block mb-1">Profile Completion</label>
                <div class="d-flex align-items-center">
                  <div class="progress flex-grow-1 me-2">
                    <div 
                      class="progress-bar" 
                      :style="{ width: customer.profile_completion_percentage + '%' }"
                    ></div>
                  </div>
                  <span>{{ customer.profile_completion_percentage }}%</span>
                </div>
              </div>
              <div class="col-12">
                <div class="mb-3">
                  <label class="form-label">Notes</label>
                  <textarea
                    v-model="form.notes"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.notes }"
                    rows="3"
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
            <Link href="/customers" class="btn btn-outline-secondary">
              Cancel
            </Link>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="form.processing"
            >
              <span v-if="form.processing" class="spinner-border spinner-border-sm me-1"></span>
              Update Customer
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
  customer: Object
});

const verifying = ref(false);

const form = useForm({
  customer_type: props.customer.customer_type,
  first_name: props.customer.first_name,
  middle_name: props.customer.middle_name,
  last_name: props.customer.last_name,
  date_of_birth: props.customer.date_of_birth,
  gender: props.customer.gender,
  marital_status: props.customer.marital_status,
  national_id: props.customer.national_id,
  passport_number: props.customer.passport_number,
  tin: props.customer.tin,
  phone_primary: props.customer.phone_primary,
  phone_secondary: props.customer.phone_secondary,
  email: props.customer.email,
  physical_address: props.customer.physical_address,
  city: props.customer.city,
  region: props.customer.region,
  postal_code: props.customer.postal_code,
  country: props.customer.country,
  employer_name: props.customer.employer_name,
  business_name: props.customer.business_name,
  occupation: props.customer.occupation,
  industry: props.customer.industry,
  employment_start_date: props.customer.employment_start_date,
  next_of_kin_name: props.customer.next_of_kin_name,
  next_of_kin_relationship: props.customer.next_of_kin_relationship,
  next_of_kin_phone: props.customer.next_of_kin_phone,
  next_of_kin_address: props.customer.next_of_kin_address,
  notes: props.customer.notes,
  status: props.customer.status
});

const customerTypeOptions = [
  { value: '', label: 'Select Type' },
  { value: 'salary', label: 'Salary Client' },
  { value: 'business', label: 'Business Client' },
  { value: 'mixed', label: 'Mixed Income Client' }
];

const genderOptions = [
  { value: '', label: 'Select Gender' },
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
  { value: 'other', label: 'Other' }
];

const maritalStatusOptions = [
  { value: '', label: 'Select Status' },
  { value: 'single', label: 'Single' },
  { value: 'married', label: 'Married' },
  { value: 'divorced', label: 'Divorced' },
  { value: 'widowed', label: 'Widowed' }
];

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
];

const submit = () => {
  form.put(`/customers/${props.customer.id}`);
};

const verifyKyc = () => {
  if (confirm('Are you sure you want to verify this customer\'s KYC? This action confirms that all identity documents have been reviewed and verified.')) {
    verifying.value = true;
    router.post(`/customers/${props.customer.id}/verify-kyc`, {}, {
      preserveScroll: true,
      onFinish: () => verifying.value = false
    });
  }
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getEmploymentCardTitle = () => {
  if (form.customer_type === 'salary') {
    return 'Employment Information';
  } else if (form.customer_type === 'business') {
    return 'Business Information';
  } else if (form.customer_type === 'mixed') {
    return 'Employment & Business Information';
  }
  return 'Employment/Business Information';
};
</script>
