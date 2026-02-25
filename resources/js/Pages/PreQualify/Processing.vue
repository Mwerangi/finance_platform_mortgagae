<template>
    <AppLayout>
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Processing Bank Statement</h1>
                            <p class="text-muted mb-0">
                                Please wait while we analyze your bank statement...
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
                                         style="width: 40px; height: 40px; font-weight: bold;">
                                        <i class="bi bi-check"></i>
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
                                        <div class="spinner-border spinner-border-sm" role="status">
                                            <span class="visually-hidden">Processing...</span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold">Bank Statement</div>
                                        <small class="text-primary">Processing...</small>
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

            <!-- Processing Status -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>

                            <h4 class="mb-3">{{ statusMessage }}</h4>

                            <!-- Progress Bar -->
                            <div class="progress mb-4" style="height: 25px;">
                                <div 
                                    class="progress-bar progress-bar-striped progress-bar-animated" 
                                    role="progressbar" 
                                    :style="{ width: progress + '%' }"
                                    :aria-valuenow="progress"
                                    aria-valuemin="0" 
                                    aria-valuemax="100"
                                >
                                    {{ progress }}%
                                </div>
                            </div>

                            <div class="text-muted">
                                <p class="mb-2">
                                    <strong>Rows Processed:</strong> {{ status.rows_processed || 0 }} / {{ status.rows_total || 0 }}
                                </p>
                                <p class="mb-0">
                                    <small>This may take a few moments depending on the size of your statement.</small>
                                </p>
                            </div>

                            <!-- Applicant Summary -->
                            <div class="mt-4 pt-4 border-top">
                                <div class="row text-start">
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Applicant:</strong><br>
                                            {{ prospect.first_name }} {{ prospect.last_name }}
                                        </p>
                                        <p class="mb-2">
                                            <strong>Phone:</strong> {{ prospect.phone }}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2">
                                            <strong>Requested Amount:</strong><br>
                                            TZS {{ Number(prospect.requested_amount).toLocaleString() }}
                                        </p>
                                        <p class="mb-2">
                                            <strong>Tenure:</strong> {{ prospect.requested_tenure }} months
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';

const props = defineProps({
    prospect: Object,
    statementImport: Object,
});

const status = ref({
    status: props.statementImport?.import_status || 'pending',
    rows_processed: props.statementImport?.rows_processed || 0,
    rows_total: props.statementImport?.rows_total || 0,
    progress: 0,
    ready: false,
});

const pollInterval = ref(null);

const progress = computed(() => status.value.progress || 0);

const statusMessage = computed(() => {
    switch (status.value.status) {
        case 'pending':
            return 'Preparing to process your bank statement...';
        case 'processing':
            return 'Analyzing transactions and computing financial metrics...';
        case 'completed':
            if (!status.value.ready) {
                return 'Computing eligibility assessment...';
            }
            return 'Processing complete! Redirecting...';
        case 'failed':
            return 'Processing failed';
        default:
            return 'Processing...';
    }
});

const checkStatus = async () => {
    try {
        const response = await fetch(`/pre-qualify/${props.prospect.id}/status`);
        
        if (!response.ok) {
            console.error('Status check failed:', response.status);
            return;
        }
        
        const data = await response.json();
        
        status.value = data;

        // If processing is complete and assessment is ready, redirect to results
        if (data.status === 'completed' && data.ready) {
            clearInterval(pollInterval.value);
            setTimeout(() => {
                router.visit(`/pre-qualify/${props.prospect.id}/results`);
            }, 1000);
        }

        // If failed, stop polling and show error
        if (data.status === 'failed') {
            clearInterval(pollInterval.value);
        }
    } catch (error) {
        console.error('Failed to check status:', error);
    }
};

onMounted(() => {
    // Start polling every 2 seconds
    pollInterval.value = setInterval(checkStatus, 2000);
    
    // Check immediately
    checkStatus();
});

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
});
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

.progress-bar-animated {
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    0% {
        background-position: 1rem 0;
    }
    100% {
        background-position: 0 0;
    }
}
</style>
