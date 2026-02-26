<template>
  <AppLayout :breadcrumb="[
    { label: 'Customers', href: '/customers' },
    { label: customer.full_name }
  ]">
    <div class="row">
      <div class="col-lg-10 mx-auto">
        <!-- KYC Alert Banner -->
        <div v-if="!customer.kyc_verified" class="alert alert-warning d-flex align-items-center mb-4">
          <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
          <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">KYC Verification Pending</h5>
            <p class="mb-0">Please upload required KYC documents to complete customer onboarding and proceed with the loan application.</p>
          </div>
          <button 
            class="btn btn-warning ms-3" 
            @click="showUploadModal = true"
          >
            <i class="bi bi-cloud-upload me-1"></i>Upload KYC Documents
          </button>
        </div>

        <!-- Application Pending Notice -->
        <div v-if="pendingApplication" class="alert alert-info d-flex align-items-center mb-4">
          <i class="bi bi-file-earmark-text me-3 fs-4"></i>
          <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">Loan Application Ready</h5>
            <p class="mb-0">A draft application has been created from the prospect. Complete KYC verification to continue processing.</p>
          </div>
          <Link 
            :href="`/applications/${pendingApplication.id}`"
            class="btn btn-info ms-3"
          >
            <i class="bi bi-arrow-right-circle me-1"></i>View Application
          </Link>
        </div>
        
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-start mb-4">
          <div>
            <h2 class="mb-1">{{ customer.full_name }}</h2>
            <div class="d-flex align-items-center gap-2">
              <Badge :variant="getTypeVariant(customer.customer_type)">
                {{ formatCustomerType(customer.customer_type) }}
              </Badge>
              <span class="text-muted">•</span>
              <code>{{ customer.customer_code }}</code>
              <span class="text-muted">•</span>
              <Badge :variant="customer.kyc_verified ? 'success' : 'warning'">
                <i :class="customer.kyc_verified ? 'bi bi-check-circle' : 'bi bi-clock-history'" class="me-1"></i>
                {{ customer.kyc_verified ? 'KYC Verified' : 'KYC Pending' }}
              </Badge>
            </div>
          </div>
          <div class="d-flex gap-2">
            <Link :href="`/customers/${customer.id}/edit`" class="btn btn-primary">
              <i class="bi bi-pencil me-1"></i>Edit
            </Link>
            <button 
              v-if="!customer.kyc_verified && canVerifyKyc && kycDocuments && kycDocuments.length > 0"
              @click="verifyKyc"
              class="btn btn-success"
              :disabled="verifying"
              title="Verify customer KYC documents"
            >
              <span v-if="verifying" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-check-circle me-1"></i>
              {{ verifying ? 'Verifying...' : 'Verify KYC' }}
            </button>
            <span 
              v-else-if="!customer.kyc_verified && !canVerifyKyc && kycDocuments && kycDocuments.length > 0"
              class="text-muted small d-flex align-items-center"
              title="Only admins and managers can verify KYC"
            >
              <i class="bi bi-info-circle me-1"></i>Admin approval required
            </span>
          </div>
        </div>

        <div class="row g-4">
          <!-- Left Column -->
          <div class="col-lg-4">
            <!-- Profile Card -->
            <Card class="mb-4">
              <div class="text-center mb-3">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                     style="width: 100px; height: 100px; font-size: 2rem;">
                  <span class="fw-bold">{{ getInitials(customer) }}</span>
                </div>
                <h4 class="mb-1">{{ customer.full_name }}</h4>
                <p class="text-muted mb-0">{{ customer.occupation || 'N/A' }}</p>
                <Badge :variant="customer.status === 'active' ? 'success' : 'secondary'" class="mt-2">
                  {{ customer.status }}
                </Badge>
              </div>
              
              <hr>
              
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Profile Completion</label>
                <div class="d-flex align-items-center">
                  <div class="progress flex-grow-1 me-2">
                    <div 
                      class="progress-bar" 
                      :class="getProgressClass(customer.profile_completion_percentage)"
                      :style="{ width: customer.profile_completion_percentage + '%' }"
                    ></div>
                  </div>
                  <span class="fw-bold">{{ customer.profile_completion_percentage }}%</span>
                </div>
                
                <!-- Profile Completion Breakdown -->
                <div class="mt-2">
                  <button 
                    class="btn btn-sm btn-link text-decoration-none p-0" 
                    @click="showCompletionBreakdown = !showCompletionBreakdown"
                    type="button"
                  >
                    <i :class="showCompletionBreakdown ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    {{ showCompletionBreakdown ? 'Hide' : 'Show' }} details
                  </button>
                  
                  <div v-if="showCompletionBreakdown" class="mt-2 small">
                    <div class="card bg-light border-0">
                      <div class="card-body p-2">
                        <div class="mb-2">
                          <div class="d-flex justify-content-between">
                            <span>Basic Information</span>
                            <span :class="getBasicInfoScore() >= 25 ? 'text-success' : 'text-warning'">
                              {{ getBasicInfoScore() }}/30
                            </span>
                          </div>
                          <small class="text-muted">Name, Middle Name, DOB, ID, Gender, Marital Status, TIN, Passport</small>
                        </div>
                        
                        <div class="mb-2">
                          <div class="d-flex justify-content-between">
                            <span>Contact Details</span>
                            <span :class="getContactScore() >= 16 ? 'text-success' : 'text-warning'">
                              {{ getContactScore() }}/20
                            </span>
                          </div>
                          <small class="text-muted">Phone, Email, Address</small>
                        </div>
                        
                        <div class="mb-2">
                          <div class="d-flex justify-content-between">
                            <span>Employment Info</span>
                            <span :class="getEmploymentScore() >= 14 ? 'text-success' : 'text-warning'">
                              {{ getEmploymentScore() }}/20
                            </span>
                          </div>
                          <small class="text-muted">
                            {{ customer.customer_type === 'salary' ? 'Employer, Occupation' : 'Business, Industry' }}
                          </small>
                        </div>
                        
                        <div class="mb-2">
                          <div class="d-flex justify-content-between">
                            <span>Next of Kin</span>
                            <span :class="getNextOfKinScore() >= 8 ? 'text-success' : 'text-warning'">
                              {{ getNextOfKinScore() }}/10
                            </span>
                          </div>
                          <small class="text-muted">Name, Relationship, Contact</small>
                        </div>
                        
                        <div class="mb-2">
                          <div class="d-flex justify-content-between">
                            <span>KYC Documents</span>
                            <span :class="getKycDocScore() >= 16 ? 'text-success' : 'text-warning'">
                              {{ getKycDocScore() }}/20
                            </span>
                          </div>
                          <small class="text-muted">ID, Passport, Utility Bill, Bank Statement</small>
                        </div>
                        
                        <div v-if="customer.kyc_verified">
                          <div class="d-flex justify-content-between text-success">
                            <span><i class="bi bi-check-circle-fill"></i> KYC Verified</span>
                            <span>+5</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Customer Since</label>
                <div>{{ formatDate(customer.created_at) }}</div>
              </div>
            </Card>

            <!-- Contact Information -->
            <Card header="Contact Information" class="mb-4">
              <div class="mb-3">
                <label class="text-muted small d-block mb-1">Primary Phone</label>
                <a :href="`tel:${customer.phone_primary}`" class="text-decoration-none">
                  <i class="bi bi-telephone me-1"></i>{{ customer.phone_primary }}
                </a>
              </div>
              <div v-if="customer.phone_secondary" class="mb-3">
                <label class="text-muted small d-block mb-1">Secondary Phone</label>
                <a :href="`tel:${customer.phone_secondary}`" class="text-decoration-none">
                  <i class="bi bi-telephone me-1"></i>{{ customer.phone_secondary }}
                </a>
              </div>
              <div v-if="customer.email" class="mb-3">
                <label class="text-muted small d-block mb-1">Email</label>
                <a :href="`mailto:${customer.email}`" class="text-decoration-none">
                  <i class="bi bi-envelope me-1"></i>{{ customer.email }}
                </a>
              </div>
              <div v-if="customer.physical_address">
                <label class="text-muted small d-block mb-1">Address</label>
                <div>{{ customer.physical_address }}</div>
                <div v-if="customer.city || customer.region">
                  <small class="text-muted">{{ customer.city }}<span v-if="customer.city && customer.region">, </span>{{ customer.region }}</small>
                </div>
              </div>
            </Card>

            <!-- Statistics -->
            <Card header="Statistics" class="mb-4">
              <div class="row g-3 text-center">
                <div class="col-6">
                  <div class="border rounded p-2">
                    <div class="h4 mb-0">{{ stats.applications_count || 0 }}</div>
                    <small class="text-muted">Applications</small>
                  </div>
                </div>
                <div class="col-6">
                  <div class="border rounded p-2">
                    <div class="h4 mb-0">{{ stats.active_loans || 0 }}</div>
                    <small class="text-muted">Active Loans</small>
                  </div>
                </div>
              </div>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="col-lg-8">
            <!-- Personal Information -->
            <Card header="Personal Information" class="mb-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Date of Birth</label>
                  <div>{{ formatDate(customer.date_of_birth) }} ({{ calculateAge(customer.date_of_birth) }} years)</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Gender</label>
                  <div class="text-capitalize">{{ customer.gender || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Marital Status</label>
                  <div class="text-capitalize">{{ customer.marital_status || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">National ID</label>
                  <div>{{ customer.national_id || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">Passport Number</label>
                  <div>{{ customer.passport_number || 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                  <label class="text-muted small d-block mb-1">TIN</label>
                  <div>{{ customer.tin || 'N/A' }}</div>
                </div>
              </div>
            </Card>

            <!-- Employment/Business Information -->
            <Card :header="getEmploymentCardTitle()" class="mb-4">
              <div class="row g-3">
                <div class="col-md-6" v-if="customer.employer_name">
                  <label class="text-muted small d-block mb-1">Employer</label>
                  <div>{{ customer.employer_name }}</div>
                </div>
                <div class="col-md-6" v-if="customer.business_name">
                  <label class="text-muted small d-block mb-1">Business Name</label>
                  <div>{{ customer.business_name }}</div>
                </div>
                <div class="col-md-6" v-if="customer.occupation">
                  <label class="text-muted small d-block mb-1">Occupation</label>
                  <div>{{ customer.occupation }}</div>
                </div>
                <div class="col-md-6" v-if="customer.industry">
                  <label class="text-muted small d-block mb-1">Industry</label>
                  <div>{{ customer.industry }}</div>
                </div>
                <div class="col-md-6" v-if="customer.employment_start_date">
                  <label class="text-muted small d-block mb-1">Employment Start Date</label>
                  <div>{{ formatDate(customer.employment_start_date) }}</div>
                </div>
              </div>
              <div v-if="!customer.employer_name && !customer.business_name && !customer.occupation" class="text-muted text-center py-3">
                No employment information provided
              </div>
            </Card>

            <!-- Next of Kin Information -->
            <Card header="Next of Kin Information" class="mb-4">
              <div v-if="customer.next_of_kin_name">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="text-muted small d-block mb-1">Full Name</label>
                    <div>{{ customer.next_of_kin_name }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="text-muted small d-block mb-1">Relationship</label>
                    <div class="text-capitalize">{{ customer.next_of_kin_relationship || 'N/A' }}</div>
                  </div>
                  <div class="col-md-6">
                    <label class="text-muted small d-block mb-1">Phone</label>
                    <div>{{ customer.next_of_kin_phone || 'N/A' }}</div>
                  </div>
                  <div class="col-md-6" v-if="customer.next_of_kin_address">
                    <label class="text-muted small d-block mb-1">Address</label>
                    <div>{{ customer.next_of_kin_address }}</div>
                  </div>
                </div>
              </div>
              <div v-else class="text-muted text-center py-3">
                No next of kin information provided
              </div>
            </Card>

            <!-- KYC Documents -->
            <Card class="mb-4">
              <template #header>
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="mb-0">KYC Documents</h5>
                  <button 
                    class="btn btn-sm btn-primary"
                    @click="showUploadModal = true"
                  >
                    <i class="bi bi-plus-circle me-1"></i>Upload Document
                  </button>
                </div>
              </template>
              
              <!-- Required Documents Checklist -->
              <div class="alert alert-info mb-3">
                <div class="d-flex align-items-center mb-2">
                  <i class="bi bi-list-check fs-5 me-2"></i>
                  <strong>Required Documents Checklist</strong>
                </div>
                <div class="row g-2">
                  <div 
                    v-for="docType in getAvailableDocumentTypes().filter(d => d.required)" 
                    :key="docType.value"
                    class="col-md-6"
                  >
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        :checked="isDocumentUploaded(docType.value)"
                        disabled
                      >
                      <label class="form-check-label" :class="{ 'text-success fw-bold': isDocumentUploaded(docType.value) }">
                        {{ docType.label }}
                        <i v-if="isDocumentUploaded(docType.value)" class="bi bi-check-circle-fill text-success ms-1"></i>
                      </label>
                    </div>
                  </div>
                </div>
                <small class="text-muted d-block mt-2">
                  <i class="bi bi-info-circle me-1"></i>
                  {{ getAvailableDocumentTypes().filter(d => d.required && isDocumentUploaded(d.value)).length }} of 
                  {{ getAvailableDocumentTypes().filter(d => d.required).length }} required documents uploaded
                </small>
              </div>
              
              <div v-if="kycDocuments && kycDocuments.length > 0" class="table-responsive">
                <table class="table table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Document Type</th>
                      <th>Document Number</th>
                      <th>Status</th>
                      <th>Uploaded</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="doc in kycDocuments" :key="doc.id">
                      <td>
                        {{ formatDocumentType(doc.document_type) }}
                        <span v-if="isDocumentRequired(doc.document_type)" class="badge bg-danger ms-1" title="Required for KYC verification">
                          Required
                        </span>
                      </td>
                      <td>{{ doc.document_number || 'N/A' }}</td>
                      <td>
                        <Badge :variant="getVerificationVariant(doc.verification_status)">
                          {{ doc.verification_status }}
                        </Badge>
                      </td>
                      <td>
                        <small>{{ formatDate(doc.created_at) }}</small>
                      </td>
                      <td>
                        <button class="btn btn-sm btn-outline-primary me-1" title="View" @click="viewDocument(doc)">
                          <i class="bi bi-eye"></i>
                        </button>
                        <button 
                          v-if="doc.verification_status === 'pending'"
                          class="btn btn-sm btn-outline-danger" 
                          title="Delete"
                          @click="deleteDocument(doc.id)"
                        >
                          <i class="bi bi-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-muted text-center py-4">
                <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
                <p class="mb-0">No KYC documents uploaded</p>
                <button 
                  class="btn btn-sm btn-primary mt-2"
                  @click="showUploadModal = true"
                >
                  <i class="bi bi-cloud-upload me-1"></i>Upload First Document
                </button>
              </div>
            </Card>

            <!-- Notes -->
            <Card v-if="customer.notes" header="Notes" class="mb-4">
              <p class="mb-0">{{ customer.notes }}</p>
            </Card>
          </div>
        </div>
      </div>
    </div>

    <!-- Verify KYC Confirmation Modal -->
    <div 
      class="modal fade" 
      :class="{ 'show d-block': showVerifyModal }" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
      v-if="showVerifyModal"
      @click.self="showVerifyModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle me-2"></i>Verify KYC
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="showVerifyModal = false"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Customer:</strong> {{ customer.full_name }}
            </div>
            
            <p class="mb-3">Are you sure you want to verify KYC for this customer?</p>
            
            <div class="bg-light p-3 rounded mb-3">
              <h6 class="mb-2">This will:</h6>
              <ul class="mb-0">
                <li>Mark the customer as KYC verified</li>
                <li>Add +5% bonus to profile completion ({{ customer.profile_completion_percentage }}% → {{ customer.profile_completion_percentage + 5 }}%)</li>
                <li>Allow the customer to proceed with loan applications</li>
                <li>Record your verification in the system</li>
              </ul>
            </div>
            
            <div class="alert alert-warning mb-0">
              <i class="bi bi-exclamation-triangle me-2"></i>
              <strong>Note:</strong> Please ensure all KYC documents have been reviewed and are valid before proceeding.
            </div>
          </div>
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="showVerifyModal = false"
              :disabled="verifying"
            >
              <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-success" 
              @click="confirmVerifyKyc"
              :disabled="verifying"
            >
              <span v-if="verifying" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-check-circle me-1"></i>
              {{ verifying ? 'Verifying...' : 'Yes, Verify KYC' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload KYC Document Modal -->
    <div 
      class="modal fade" 
      :class="{ 'show d-block': showUploadModal }" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
      v-if="showUploadModal"
      @click.self="showUploadModal = false"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Upload KYC Document</h5>
            <button type="button" class="btn-close" @click="showUploadModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="uploadDocument">
              <div class="mb-3">
                <label class="form-label">Document Type <span class="text-danger">*</span></label>
                <select v-model="uploadForm.document_type" class="form-select" required>
                  <option value="">Select document type...</option>
                  <option 
                    v-for="docType in getAvailableDocumentTypes()" 
                    :key="docType.value"
                    :value="docType.value"
                    :disabled="isDocumentUploaded(docType.value)"
                  >
                    {{ docType.label }}
                    <span v-if="docType.required"> *</span>
                    <span v-if="isDocumentUploaded(docType.value)"> ✓ (Uploaded)</span>
                  </option>
                </select>
                <small class="text-muted d-block mt-1">
                  <i class="bi bi-info-circle me-1"></i>
                  Documents marked with * are required for KYC verification. 
                  Documents marked with ✓ are already uploaded.
                </small>
                <div v-if="uploadForm.document_type && isDocumentUploaded(uploadForm.document_type)" class="alert alert-warning mt-2 py-2 small">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  <strong>Note:</strong> This document type is already uploaded. To replace it, please delete the existing document first from the table below.
                </div>
                <div v-if="uploadErrors.document_type" class="text-danger small mt-1">
                  {{ uploadErrors.document_type[0] }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Document Number</label>
                <input 
                  v-model="uploadForm.document_number" 
                  type="text" 
                  class="form-control"
                  placeholder="e.g., ID number, passport number"
                >
                <div v-if="uploadErrors.document_number" class="text-danger small mt-1">
                  {{ uploadErrors.document_number[0] }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Expiry Date (if applicable)</label>
                <input 
                  v-model="uploadForm.expiry_date" 
                  type="date" 
                  class="form-control"
                  :min="new Date().toISOString().split('T')[0]"
                >
                <div v-if="uploadErrors.expiry_date" class="text-danger small mt-1">
                  {{ uploadErrors.expiry_date[0] }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea 
                  v-model="uploadForm.description" 
                  class="form-control" 
                  rows="2"
                  placeholder="Optional notes about this document"
                ></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">File <span class="text-danger">*</span></label>
                <input 
                  type="file" 
                  class="form-control" 
                  @change="handleFileChange"
                  accept=".pdf,.jpg,.jpeg,.png"
                  required
                >
                <small class="text-muted">Accepted formats: PDF, JPG, PNG (Max 10MB)</small>
                <div v-if="uploadErrors.file" class="text-danger small mt-1">
                  {{ uploadErrors.file[0] }}
                </div>
              </div>

              <div v-if="uploadProgress > 0" class="mb-3">
                <div class="progress">
                  <div 
                    class="progress-bar progress-bar-striped progress-bar-animated" 
                    :style="{ width: uploadProgress + '%' }"
                  >
                    {{ uploadProgress }}%
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="showUploadModal = false"
              :disabled="uploading"
            >
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="uploadDocument"
              :disabled="uploading || !uploadForm.file"
            >
              <span v-if="uploading" class="spinner-border spinner-border-sm me-1"></span>
              {{ uploading ? 'Uploading...' : 'Upload Document' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const page = usePage();

const props = defineProps({
  customer: Object,
  kycDocuments: Array,
  stats: Object,
  pendingApplication: Object
});

// Check if current user can verify KYC
const canVerifyKyc = computed(() => {
  const user = page.props.auth?.user;
  if (!user || !user.roles || !Array.isArray(user.roles)) {
    return false;
  }
  
  const allowedRoles = ['provider-super-admin', 'institution-admin', 'credit-manager'];
  return user.roles.some(role => allowedRoles.includes(role.slug));
});

const showUploadModal = ref(false);
const showVerifyModal = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadErrors = ref({});
const verifying = ref(false);
const showCompletionBreakdown = ref(false);

const uploadForm = ref({
  document_type: '',
  document_number: '',
  expiry_date: '',
  description: '',
  file: null
});

// Get already uploaded document types
const uploadedDocumentTypes = () => {
  return (props.kycDocuments || []).map(doc => doc.document_type);
};

// Check if a document type is already uploaded
const isDocumentUploaded = (docType) => {
  return uploadedDocumentTypes().includes(docType);
};

// Check if a document type is required
const isDocumentRequired = (docType) => {
  const doc = getAvailableDocumentTypes().find(d => d.value === docType);
  return doc ? doc.required : false;
};

// Get available document types based on customer type
const getAvailableDocumentTypes = () => {
  const customerType = props.customer.customer_type;
  
  // Common documents for all customer types
  const commonDocs = [
    { value: 'national_id', label: 'National ID (NIDA)', required: true },
    { value: 'passport', label: 'Passport', required: false },
    { value: 'drivers_license', label: 'Driver\'s License', required: false },
    { value: 'utility_bill', label: 'Utility Bill (Proof of Address)', required: true },
    { value: 'bank_statement', label: 'Bank Statement (Last 6 months)', required: true },
  ];
  
  // Documents specific to salaried clients
  const salariedDocs = [
    { value: 'employment_letter', label: 'Employment Letter', required: true },
  ];
  
  // Documents specific to business clients
  const businessDocs = [
    { value: 'business_license', label: 'Business License/Registration', required: true },
    { value: 'tax_certificate', label: 'Tax Certificate (TIN)', required: true },
  ];
  
  // Other documents
  const otherDocs = [
    { value: 'other', label: 'Other Document', required: false },
  ];
  
  let documentTypes = [...commonDocs];
  
  if (customerType === 'salary') {
    documentTypes = [...documentTypes, ...salariedDocs, ...otherDocs];
  } else if (customerType === 'business') {
    documentTypes = [...documentTypes, ...businessDocs, ...otherDocs];
  } else if (customerType === 'mixed') {
    documentTypes = [...documentTypes, ...salariedDocs, ...businessDocs, ...otherDocs];
  } else {
    // If type not set, show all
    documentTypes = [...documentTypes, ...salariedDocs, ...businessDocs, ...otherDocs];
  }
  
  return documentTypes;
};

const handleFileChange = (event) => {
  uploadForm.value.file = event.target.files[0];
  uploadErrors.value = {};
};

const uploadDocument = async () => {
  if (!uploadForm.value.file) {
    toast.error('Please select a file to upload');
    return;
  }

  uploading.value = true;
  uploadProgress.value = 0;
  uploadErrors.value = {};

  const formData = new FormData();
  formData.append('document_type', uploadForm.value.document_type);
  formData.append('file', uploadForm.value.file);
  if (uploadForm.value.document_number) {
    formData.append('document_number', uploadForm.value.document_number);
  }
  if (uploadForm.value.expiry_date) {
    formData.append('expiry_date', uploadForm.value.expiry_date);
  }
  if (uploadForm.value.description) {
    formData.append('description', uploadForm.value.description);
  }

  try {
    const response = await window.axios.post(
      `/customers/${props.customer.id}/kyc-documents`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
        onUploadProgress: (progressEvent) => {
          uploadProgress.value = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          );
        },
      }
    );

    // Success - reload the page to show the new document
    showUploadModal.value = false;
    router.reload({
      only: ['kycDocuments', 'customer'],
      onSuccess: () => {
        // Show success notification
        toast.success('Document uploaded successfully!');
      }
    });

    // Reset form
    uploadForm.value = {
      document_type: '',
      document_number: '',
      expiry_date: '',
      description: '',
      file: null
    };
  } catch (error) {
    if (error.response?.status === 422) {
      uploadErrors.value = error.response.data.errors || {};
      if (error.response.data.message) {
        toast.error(error.response.data.message);
      }
    } else {
      toast.error('Failed to upload document: ' + (error.response?.data?.message || error.message));
    }
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
  }
};

const deleteDocument = async (documentId) => {
  if (!confirm('Are you sure you want to delete this document?')) {
    return;
  }

  try {
    await window.axios.delete(`/kyc-documents/${documentId}`);
    
    // Reload to refresh the list
    router.reload({
      only: ['kycDocuments', 'customer'],
      onSuccess: () => {
        toast.success('Document deleted successfully!');
      }
    });
  } catch (error) {
    toast.error('Failed to delete document: ' + (error.response?.data?.message || error.message));
  }
};

const viewDocument = (doc) => {
  // TODO: Implement document viewer
  toast.info('Document viewer will be implemented. Document ID: ' + doc.id);
};

const verifyKyc = () => {
  showVerifyModal.value = true;
};

const confirmVerifyKyc = async () => {
  verifying.value = true;
  showVerifyModal.value = false;

  try {
    await window.axios.post(`/customers/${props.customer.id}/verify-kyc`);
    
    // Reload to refresh customer data
    router.reload({
      only: ['customer'],
      onSuccess: () => {
        toast.success('Customer KYC verified successfully!');
      }
    });
  } catch (error) {
    toast.error('Failed to verify KYC: ' + (error.response?.data?.message || error.message));
  } finally {
    verifying.value = false;
  }
};

const getInitials = (customer) => {
  const first = customer.first_name?.[0] || '';
  const last = customer.last_name?.[0] || '';
  return (first + last).toUpperCase();
};

const getTypeVariant = (type) => {
  const variants = {
    salary: 'primary',
    business: 'success',
    mixed: 'info'
  };
  return variants[type] || 'secondary';
};

const formatCustomerType = (type) => {
  const labels = {
    salary: 'Salary Client',
    business: 'Business Client',
    mixed: 'Mixed Income'
  };
  return labels[type] || type;
};

const getProgressClass = (percentage) => {
  if (percentage >= 80) return 'bg-success';
  if (percentage >= 50) return 'bg-warning';
  return 'bg-danger';
};

const getVerificationVariant = (status) => {
  const variants = {
    verified: 'success',
    pending: 'warning',
    rejected: 'danger',
    expired: 'secondary'
  };
  return variants[status] || 'secondary';
};

const formatDocumentType = (type) => {
  return type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const getEmploymentCardTitle = () => {
  if (props.customer.customer_type === 'salary') {
    return 'Employment Information';
  } else if (props.customer.customer_type === 'business') {
    return 'Business Information';
  } else if (props.customer.customer_type === 'mixed') {
    return 'Employment & Business Information';
  }
  return 'Employment/Business Information';
};

const calculateAge = (dob) => {
  const birthDate = new Date(dob);
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }
  
  return age;
};

// Profile completion score calculators
const getBasicInfoScore = () => {
  let score = 0;
  if (props.customer.first_name) score += 5;
  if (props.customer.last_name) score += 5;
  if (props.customer.middle_name) score += 2;
  if (props.customer.date_of_birth) score += 3;
  if (props.customer.gender) score += 2;
  if (props.customer.national_id) score += 5;
  if (props.customer.marital_status) score += 2;
  if (props.customer.tin) score += 3;
  if (props.customer.passport_number) score += 3;
  return score;
};

const getContactScore = () => {
  let score = 0;
  if (props.customer.phone_primary) score += 7;
  if (props.customer.email) score += 7;
  if (props.customer.physical_address) score += 3;
  if (props.customer.city) score += 2;
  if (props.customer.country) score += 1;
  return score;
};

const getEmploymentScore = () => {
  let score = 0;
  const maxScore = 20;
  
  if (props.customer.customer_type === 'salary') {
    let filled = 0;
    if (props.customer.employer_name) filled++;
    if (props.customer.occupation) filled++;
    if (props.customer.employment_start_date) filled++;
    score = Math.round((filled / 3) * maxScore);
  } else if (props.customer.customer_type === 'business') {
    let filled = 0;
    if (props.customer.business_name) filled++;
    if (props.customer.industry) filled++;
    if (props.customer.occupation) filled++;
    score = Math.round((filled / 3) * maxScore);
  } else {
    if (props.customer.employer_name || props.customer.business_name || props.customer.occupation) {
      score = Math.round(maxScore / 2);
    }
  }
  
  return score;
};

const getNextOfKinScore = () => {
  let score = 0;
  if (props.customer.next_of_kin_name) score += 4;
  if (props.customer.next_of_kin_relationship) score += 2;
  if (props.customer.next_of_kin_phone) score += 4;
  return score;
};

const getKycDocScore = () => {
  const requiredDocTypes = ['national_id', 'passport', 'utility_bill', 'bank_statement'];
  const kycDocs = props.kycDocuments || [];
  
  const uploadedTypes = [...new Set(kycDocs.map(doc => doc.document_type))];
  
  let score = 0;
  requiredDocTypes.forEach(docType => {
    if (uploadedTypes.includes(docType)) {
      score += 5; // 20 points / 4 required docs = 5 points each
    }
  });
  
  return score;
};
</script>
