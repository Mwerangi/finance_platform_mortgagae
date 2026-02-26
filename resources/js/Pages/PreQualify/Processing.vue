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

                            <!-- Timeout Warning -->
                            <div v-if="isStuck" class="alert alert-warning mt-4 mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Processing seems stuck!</strong> 
                                The system has been processing for over 5 minutes without progress. 
                                You may want to cancel and try again with a different file format.
                            </div>

                            <!-- Cancel Button -->
                            <div class="mt-4">
                                <button 
                                    type="button" 
                                    :class="isStuck ? 'btn btn-danger' : 'btn btn-outline-danger'"
                                    @click="showCancelModal = true"
                                    :disabled="cancelling || status.status === 'completed'"
                                >
                                    <i class="bi bi-x-circle me-2"></i>
                                    Cancel Processing
                                </button>
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

        <!-- Cancel Confirmation Modal -->
        <div class="modal fade" :class="{ 'show d-block': showCancelModal }" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" v-if="showCancelModal">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Cancel Processing?
                        </h5>
                        <button type="button" class="btn-close" @click="showCancelModal = false" :disabled="cancelling"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Are you sure you want to cancel the bank statement processing?</p>
                        <div class="alert alert-warning mt-3 mb-0">
                            <small>
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>Note:</strong> This will delete the uploaded statement and reset the process. You'll need to upload the statement again.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            @click="showCancelModal = false"
                            :disabled="cancelling"
                        >
                            No, Continue Processing
                        </button>
                        <button 
                            type="button" 
                            class="btn btn-danger" 
                            @click="confirmCancel"
                            :disabled="cancelling"
                        >
                            <span v-if="cancelling" class="spinner-border spinner-border-sm me-2"></span>
                            Yes, Cancel Processing
                        </button>
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
const showCancelModal = ref(false);
const cancelling = ref(false);
const startTime = ref(Date.now());
const lastProgress = ref(0);
const lastProgressTime = ref(Date.now());

const progress = computed(() => status.value.progress || 0);

// Check if processing is stuck (no progress for 5 minutes)
const isStuck = computed(() => {
    const now = Date.now();
    const timeSinceStart = (now - startTime.value) / 1000 / 60; // minutes
    const timeSinceLastProgress = (now - lastProgressTime.value) / 1000 / 60; // minutes
    
    // Consider stuck if:
    // 1. More than 5 minutes since start AND no progress
    // 2. OR More than 3 minutes with no progress change
    return (timeSinceStart > 5 && progress.value === 0) || 
           (timeSinceLastProgress > 3 && status.value.status === 'processing');
});

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
        
        // Track progress changes for stuck detection
        if (data.progress > lastProgress.value) {
            lastProgress.value = data.progress;
            lastProgressTime.value = Date.now();
        }
        
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

const confirmCancel = async () => {
    cancelling.value = true;
    
    router.post(`/pre-qualify/${props.prospect.id}/cancel-processing`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false;
            if (pollInterval.value) {
                clearInterval(pollInterval.value);
            }
        },
        onError: (errors) => {
            alert('Error: ' + (errors.error || 'Failed to cancel processing'));
        },
        onFinish: () => {
            cancelling.value = false;
        }
    });
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
