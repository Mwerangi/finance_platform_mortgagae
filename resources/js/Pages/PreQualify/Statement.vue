<template>
    <AppLayout>
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Bank Statement Upload</h1>
                            <p class="text-muted mb-0">
                                Upload your bank statement for automated income and eligibility analysis.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
                                        <i class="bi bi-check-lg fw-bold"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Basic Information</div>
                                        <small class="text-success">Completed</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        2
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Bank Statement</div>
                                        <small class="text-muted">Current step</small>
                                    </div>
                                </div>
                                <i class="bi bi-arrow-right text-muted"></i>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        3
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold text-muted">Eligibility Results</div>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Prospect Summary Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person-badge me-2"></i>Applicant Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Name:</strong> {{ prospect.first_name }} {{ prospect.middle_name }} {{ prospect.last_name }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Phone:</strong> {{ prospect.phone }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Customer Type:</strong> 
                                        <span class="badge bg-info">{{ formatCustomerType(prospect.customer_type) }}</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Loan Purpose:</strong> {{ formatLoanPurpose(prospect.loan_purpose) }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Requested Amount:</strong> TZS {{ Number(prospect.requested_amount).toLocaleString() }}
                                    </p>
                                    <p class="mb-2">
                                        <strong>Tenure:</strong> {{ prospect.requested_tenure }} months
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>Upload Bank Statement
                            </h5>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <label for="bank_name" class="form-label">Bank Name</label>
                                    <input
                                        id="bank_name"
                                        v-model="form.bank_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.bank_name }"
                                        placeholder="e.g., CRDB Bank, NMB Bank"
                                    />
                                    <div v-if="form.errors.bank_name" class="invalid-feedback">
                                        {{ form.errors.bank_name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="account_number" class="form-label">Account Number</label>
                                    <input
                                        id="account_number"
                                        v-model="form.account_number"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.account_number }"
                                    />
                                    <div v-if="form.errors.account_number" class="invalid-feedback">
                                        {{ form.errors.account_number }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="file" class="form-label">
                                        Bank Statement File <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        id="file"
                                        type="file"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.file }"
                                        accept=".xlsx,.xls,.csv"
                                        @change="handleFileChange"
                                        required
                                    />
                                    <div v-if="form.errors.file" class="invalid-feedback">
                                        {{ form.errors.file }}
                                    </div>
                                    <small class="text-muted">
                                        Accepted formats: XLSX, XLS, CSV (Max 10MB)
                                    </small>
                                    
                                    <div v-if="selectedFile" class="mt-2">
                                        <div class="alert alert-info d-flex align-items-center">
                                            <i class="bi bi-file-earmark-check fs-4 me-2"></i>
                                            <div>
                                                <strong>{{ selectedFile.name }}</strong>
                                                <br>
                                                <small>{{ formatFileSize(selectedFile.size) }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Required Statement Period:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li v-if="prospect.customer_type === 'salaried'">
                                            <strong>Salaried Employees:</strong> Last 12 months bank statement
                                        </li>
                                        <li v-else>
                                            <strong>Self-Employed:</strong> Last 24 months bank statement
                                        </li>
                                    </ul>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <Link href="/dashboard" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>Back
                                    </Link>
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing || !selectedFile"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        <span v-if="form.processing">Analyzing Statement...</span>
                                        <span v-else>
                                            Upload & Run Eligibility Check
                                            <i class="bi bi-arrow-right ms-2"></i>
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Information Sidebar -->
                <div class="col-lg-4">
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-info-circle me-2"></i>Statement Requirements
                            </h6>
                            <ul class="mb-3">
                                <li>Excel or CSV format only</li>
                                <li>Must include transaction dates</li>
                                <li>Must show credits and debits</li>
                                <li>Running balance recommended</li>
                                <li>All pages/months required</li>
                            </ul>

                            <h6 class="card-title mt-4">
                                <i class="bi bi-cpu me-2"></i>Automated Analysis Will Calculate:
                            </h6>
                            <ul>
                                <li>Average monthly income</li>
                                <li>Income stability score</li>
                                <li>Existing debt obligations</li>
                                <li>Debt-to-Income (DTI) ratio</li>
                                <li>Debt Service Ratio (DSR)</li>
                                <li>Maximum affordable loan amount</li>
                                <li>Risk grade (A/B/C/D)</li>
                            </ul>

                            <div class="alert alert-success">
                                <i class="bi bi-shield-lock me-2"></i>
                                <strong>Your data is secure:</strong> All statements are encrypted and analyzed privately. We never share your financial information.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    prospect: Object,
});

const selectedFile = ref(null);

const form = useForm({
    file: null,
    bank_name: '',
    account_number: '',
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        selectedFile.value = file;
        form.file = file;
    }
};

const submit = () => {
    form.post(`/pre-qualify/${props.prospect.id}/statement`, {
        forceFormData: true, // Required for file uploads
    });
};

const formatCustomerType = (type) => {
    return type === 'salaried' ? 'Salaried Employee' : 'Self-Employed';
};

const formatLoanPurpose = (purpose) => {
    const map = {
        home_purchase: 'Home Purchase',
        home_refinance: 'Home Refinance',
        home_completion: 'Home Completion',
        home_construction: 'Home Construction',
        home_equity_release: 'Home Equity Release',
    };
    return map[purpose] || purpose;
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
};
</script>

<style scoped>
.card {
    border: 1px solid #e0e0e0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}
</style>
