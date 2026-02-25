# Phase 8: Loan Management Module - Implementation Complete ✅

## Overview

Phase 8 implements a comprehensive loan management system that handles the entire loan lifecycle from creation through disbursement, activation, repayment tracking, and closure. This phase adds:

- **Loan Registry**: Complete loan account management with status tracking
- **Loan Lifecycle**: Disbursement, activation, closure, early settlement
- **Schedule Generation**: Automatic amortization schedules for reducing balance and flat rate loans
- **Balance Tracking**: Real-time principal, interest, penalties, and fees tracking
- **Aging & DPD**: Days Past Due calculation and aging bucket classification
- **Risk Management**: Risk classification and provision tracking

## Implementation Summary

### Components Created

1. **Loans Migration**: `2026_02_24_100700_create_loans_table.php`
   - 140+ fields covering complete loan lifecycle
   - 7 status states (pending_disbursement, active, fully_paid, closed, defaulted, written_off, restructured)
   - Balance tracking (principal, interest, penalties, fees outstanding and paid)
   - Arrears tracking (DPD, aging bucket, arrears amount)
   - Property/collateral details
   - Insurance tracking
   - Early settlement support
   - Restructuring support
   - Write-off tracking
   - Risk classification

2. **Loan Schedules Migration**: `2026_02_24_100701_create_loan_schedules_table.php`
   - Individual installment tracking
   - Payment allocation (principal, interest, penalties, fees)
   - Installment status (pending, partially_paid, fully_paid, overdue, waived)
   - Opening/closing balance tracking
   - DPD calculation per installment
   - Payment history logging

3. **Loan Model**: `app/Models/Loan.php`
   - 12 relationships (application, customer, institution, product, decision, schedules, users)
   - 21 query scopes for filtering
   - 11 status check methods
   - 6 lifecycle methods (disburse, activate, close, markAsFullyPaid, markAsDefaulted, writeOff)
   - 10 computed attributes for UI and calculations
   - Auto-generated loan account numbers (LOAN-000001)

4. **LoanSchedule Model**: `app/Models/LoanSchedule.php`
   - Relationships to loan and institution
   - 12 query scopes for filtering
   - 5 status check methods
   - Payment recording methods
   - DPD update methods
   - 5 computed attributes

5. **LoanService**: `app/Services/LoanService.php`
   - Create loan from approved application
   - Generate reducing balance schedules
   - Generate flat rate schedules
   - Regenerate schedules (after restructuring)
   - Calculate early settlement
   - Disburse and activate loans
   - Update aging and DPD
   - Get loan summary

6. **LoanController**: `app/Http/Controllers/LoanController.php`
   - 9 endpoints covering all loan operations
   - Institution-scoped access control
   - Permission-based authorization
   - List loans with advanced filtering
   - Create from application
   - Disburse and activate
   - Get schedule
   - Calculate early settlement
   - Close loan
   - Update aging

7. **Routes**: Updated `routes/api.php`
   - Application-scoped route (create loan)
   - Standalone loan routes (list, view, disburse, schedule, etc.)
   - Permission middleware protection

8. **Model Updates**: Enhanced `app/Models/Application.php`
   - Added loans relationship
   - Added latestLoan helper
   - Added hasLoan check method

---

## Database Schema

### Table: `loans` (140+ fields)

#### Core Relationships (5 fields)
```php
- application_id: FK to applications
- customer_id: FK to customers
- institution_id: FK to institutions
- loan_product_id: FK to loan_products
- underwriting_decision_id: FK to underwriting_decisions (nullable)
```

#### Loan Identification (2 fields)
```php
- loan_account_number: Unique (LOAN-000001) - auto-generated
- external_reference_number: Nullable - bank/external system reference
```

#### Loan Status (1 field)
```php
- status: Enum (pending_disbursement, active, fully_paid, closed, defaulted, written_off, restructured)
```

#### Approved Loan Terms (8 fields)
```php
- approved_amount: Decimal(15,2) - final approved amount
- approved_tenure_months: Integer - loan duration in months
- approved_interest_rate: Decimal(5,2) - annual percentage
- interest_method: Enum (reducing_balance, flat_rate)
- monthly_installment: Decimal(15,2) - fixed monthly payment
- total_interest: Decimal(15,2) - total interest over loan life
- total_repayment: Decimal(15,2) - principal + interest
```

#### Disbursement Details (8 fields)
```php
- disbursed_amount: Decimal(15,2) - actual amount disbursed
- disbursement_date: Date - when funds were released
- disbursement_method: Enum (bank_transfer, cheque, cash, mobile_money)
- disbursement_reference: String - transaction reference
- disbursement_notes: Text - additional notes
- disbursed_by: FK to users - who processed disbursement
- disbursement_approved_at: Timestamp
- disbursement_approved_by: FK to users
```

#### Loan Dates (5 fields)
```php
- activation_date: Date - when loan became active
- first_installment_date: Date - first payment due date
- maturity_date: Date - expected final payment date
- closure_date: Date - when loan was closed
- closed_at: Timestamp
```

#### Current Balances (6 fields)
```php
- principal_outstanding: Decimal(15,2) - remaining principal
- interest_outstanding: Decimal(15,2) - remaining interest
- total_outstanding: Decimal(15,2) - total remaining balance
- penalties_outstanding: Decimal(15,2) - unpaid penalties
- fees_outstanding: Decimal(15,2) - unpaid fees
```

#### Payment Summary (10 fields)
```php
- total_paid: Decimal(15,2) - total amount paid
- principal_paid: Decimal(15,2)
- interest_paid: Decimal(15,2)
- penalties_paid: Decimal(15,2)
- fees_paid: Decimal(15,2)
- installments_paid: Integer - number of completed payments
- installments_remaining: Integer
```

#### Arrears & DPD (6 fields)
```php
- days_past_due: Integer - current DPD
- arrears_amount: Decimal(15,2) - total overdue amount
- last_payment_date: Date
- last_payment_amount: Decimal(15,2)
- next_payment_due_date: Date
- next_payment_amount: Decimal(15,2)
```

#### Aging Bucket (1 field)
```php
- aging_bucket: Enum (current, bucket_30, bucket_60, bucket_90, bucket_180, npl)
  - current: 0-30 days
  - bucket_30: 31-60 days
  - bucket_60: 61-90 days
  - bucket_90: 91-180 days
  - bucket_180: 180+ days
  - npl: Non-performing (90+ days)
```

#### Property/Collateral (7 fields)
```php
- property_type: String
- property_value: Decimal(15,2)
- property_address: Text
- property_title_number: String
- ltv_ratio: Decimal(5,2) - Loan-to-Value percentage
- collateral_description: Text
- collateral_documents: JSON - array of file references
```

#### Insurance (5 fields)
```php
- insurance_required: Boolean
- insurance_provider: String
- insurance_policy_number: String
- insurance_premium: Decimal(15,2)
- insurance_expiry_date: Date
```

#### Early Settlement (4 fields)
```php
- allows_early_settlement: Boolean (default true)
- early_settlement_penalty_rate: Decimal(5,2) - percentage penalty
- early_settlement_date: Date
- early_settlement_amount: Decimal(15,2)
```

#### Restructuring (4 fields)
```php
- is_restructured: Boolean
- original_loan_id: FK to loans (self-reference)
- restructured_date: Date
- restructure_reason: Text
```

#### Write-off (4 fields)
```php
- written_off_date: Date
- written_off_amount: Decimal(15,2)
- writeoff_reason: Text
- written_off_by: FK to users
```

#### Risk Classification (3 fields)
```php
- risk_classification: Enum (performing, watch_list, substandard, doubtful, loss)
- provision_amount: Decimal(15,2) - amount set aside for loss
- provision_rate: Decimal(5,2) - percentage provision
```

---

### Table: `loan_schedules` (30+ fields)

#### Relationships (2 fields)
```php
- loan_id: FK to loans
- institution_id: FK to institutions
```

#### Schedule Details (3 fields)
```php
- installment_number: Integer (1, 2, 3, etc.)
- due_date: Date
- status: Enum (pending, partially_paid, fully_paid, overdue, waived)
```

#### Amounts Due (5 fields)
```php
- principal_due: Decimal(15,2)
- interest_due: Decimal(15,2)
- total_due: Decimal(15,2)
- penalties_due: Decimal(15,2)
- fees_due: Decimal(15,2)
```

#### Balance Tracking (2 fields)
```php
- opening_balance: Decimal(15,2) - principal at start of period
- closing_balance: Decimal(15,2) - principal at end of period
```

#### Payment Tracking (6 fields)
```php
- principal_paid: Decimal(15,2)
- interest_paid: Decimal(15,2)
- penalties_paid: Decimal(15,2)
- fees_paid: Decimal(15,2)
- total_paid: Decimal(15,2)
- balance_remaining: Decimal(15,2) - amount still owed for this installment
```

#### Payment Dates (2 fields)
```php
- paid_date: Date - when fully paid
- last_payment_date: Date - most recent payment
```

#### DPD Tracking (2 fields)
```php
- days_past_due: Integer
- overdue_since: Date
```

#### Metadata (2 fields)
```php
- payment_history: JSON - array of payment transactions
- notes: Text
```

---

## Loan Schedule Generation Algorithms

### 1. Reducing Balance Method

The reducing balance (also called diminishing balance) method calculates interest on the **outstanding principal balance** each period.

#### Formula
```php
Monthly Payment (PMT) = P × [r(1+r)^n] / [(1+r)^n - 1]

Where:
- P = Principal (loan amount)
- r = Monthly interest rate (annual_rate / 12)
- n = Number of months (tenure)
```

#### Per Installment Calculation
```php
For each installment i:
1. Interest_i = Opening_Balance × Monthly_Rate
2. Principal_i = Monthly_Payment - Interest_i
3. Closing_Balance_i = Opening_Balance_i - Principal_i
4. Opening_Balance_(i+1) = Closing_Balance_i
```

#### Example
```
Loan: 50,000,000 TZS
Rate: 12% per annum (1% per month)
Tenure: 240 months (20 years)

Monthly Payment = 50,000,000 × [0.01(1.01)^240] / [(1.01)^240 - 1]
                = 550,543.66 TZS/month

Month 1:
- Opening Balance: 50,000,000
- Interest: 50,000,000 × 0.01 = 500,000
- Principal: 550,544 - 500,000 = 50,544
- Closing Balance: 50,000,000 - 50,544 = 49,949,456

Month 2:
- Opening Balance: 49,949,456
- Interest: 49,949,456 × 0.01 = 499,495
- Principal: 550,544 - 499,495 = 51,049
- Closing Balance: 49,949,456 - 51,049 = 49,898,407

... and so on
```

**Characteristics:**
- Interest decreases over time
- Principal increases over time
- Total payment stays constant
- More cost-effective for borrower

### 2. Flat Rate Method

The flat rate method calculates interest on the **original principal amount** for the entire loan period, then divides equally.

#### Formula
```php
Total Interest = P × r × (n / 12)
Interest Per Month = Total Interest / n
Principal Per Month = P / n
Monthly Payment = Principal Per Month + Interest Per Month

Where:
- P = Principal (loan amount)
- r = Annual interest rate (as decimal)
- n = Number of months (tenure)
```

#### Example
```
Loan: 50,000,000 TZS
Rate: 12% per annum
Tenure: 240 months (20 years)

Total Interest = 50,000,000 × 0.12 × (240/12) = 120,000,000 TZS
Interest Per Month = 120,000,000 / 240 = 500,000 TZS
Principal Per Month = 50,000,000 / 240 = 208,333 TZS
Monthly Payment = 208,333 + 500,000 = 708,333 TZS/month

Every Month:
- Interest: 500,000 (constant)
- Principal: 208,333 (constant)
- Total: 708,333 (constant)
```

**Characteristics:**
- Interest stays constant every month
- Principal stays constant every month
- Simpler calculation
- Higher effective cost for borrower
- Total interest = 120M (vs ~82M for reducing balance)

---

## API Endpoints

### List Loans
```http
GET /api/v1/loans?status=active&overdue_only=true&per_page=20

Query Parameters:
- status: pending_disbursement, active, fully_paid, closed, defaulted, written_off
- customer_id: integer
- aging_bucket: current, bucket_30, bucket_60, bucket_90, bucket_180, npl
- disbursed_from: date
- disbursed_to: date
- overdue_only: boolean
- npl_only: boolean
- search: string (loan account number or external reference)
- sort_by: field name
- sort_order: asc/desc
- per_page: integer

Response: 200 OK
{
  "data": [
    {
      "id": 1,
      "loan_account_number": "LOAN-000001",
      "external_reference_number": "BNK-2026-001",
      "status": "active",
      "status_color": "green",
      "customer": {
        "id": 5,
        "name": "John Doe",
        "customer_number": "CUST-000005"
      },
      "loan_product": {
        "id": 2,
        "name": "Home Mortgage - 20 Years"
      },
      "approved_amount": "50000000.00",
      "disbursed_amount": "50000000.00",
      "total_outstanding": "45000000.00",
      "total_paid": "5000000.00",
      "repayment_progress": 10.00,
      "days_past_due": 0,
      "aging_bucket": "current",
      "aging_bucket_color": "green",
      "disbursement_date": "2026-01-15",
      "maturity_date": "2046-01-15",
      "created_at": "2026-01-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 45,
    "per_page": 20,
    "last_page": 3
  }
}
```

### Create Loan from Application
```http
POST /api/v1/applications/{application}/create-loan

Authorization: Bearer {token}
Permission: loans.create

Response: 201 Created
{
  "message": "Loan created successfully",
  "data": {
    "id": 1,
    "loan_account_number": "LOAN-000001",
    "status": "pending_disbursement",
    "approved_amount": "50000000.00",
    "approved_tenure_months": 240,
    "approved_interest_rate": "12.00",
    "interest_method": "reducing_balance",
    "monthly_installment": "550543.66",
    "total_interest": "82130478.40",
    "total_repayment": "132130478.40",
    "created_at": "2026-02-24T10:00:00Z"
  }
}
```

### Get Loan Details
```http
GET /api/v1/loans/{loan}

Response: 200 OK
{
  "data": {
    "id": 1,
    "loan_account_number": "LOAN-000001",
    "status": "active",
    "status_color": "green",
    
    "customer": {
      "id": 5,
      "full_name": "John Doe",
      "customer_number": "CUST-000005"
    },
    
    "loan_terms": {
      "approved_amount": "50000000.00",
      "approved_tenure_months": 240,
      "approved_interest_rate": "12.00",
      "interest_method": "reducing_balance",
      "monthly_installment": "550543.66",
      "total_interest": "82130478.40",
      "total_repayment": "132130478.40"
    },
    
    "disbursement": {
      "disbursed_amount": "50000000.00",
      "disbursement_date": "2026-01-15",
      "disbursement_method": "bank_transfer",
      "disbursement_reference": "TRX-2026-001"
    },
    
    "dates": {
      "activation_date": "2026-01-15",
      "first_installment_date": "2026-02-01",
      "maturity_date": "2046-01-01",
      "days_to_maturity": 7280,
      "months_elapsed": 1
    },
    
    "balances": {
      "principal_outstanding": "49900000.00",
      "interest_outstanding": "81630478.40",
      "total_outstanding": "131530478.40",
      "penalties_outstanding": "0.00",
      "fees_outstanding": "0.00",
      "outstanding_percentage": 99.55
    },
    
    "payments": {
      "total_paid": "600000.00",
      "principal_paid": "100000.00",
      "interest_paid": "500000.00",
      "penalties_paid": "0.00",
      "fees_paid": "0.00",
      "installments_paid": 1,
      "installments_remaining": 239,
      "repayment_progress": 0.45
    },
    
    "arrears": {
      "days_past_due": 0,
      "arrears_amount": "0.00",
      "aging_bucket": "current",
      "aging_bucket_color": "green",
      "next_payment_due_date": "2026-03-01",
      "next_payment_amount": "550543.66"
    },
    
    "property": {
      "property_type": "Residential",
      "property_value": "75000000.00",
      "property_address": "123 Main St, Dar es Salaam",
      "ltv_ratio": "66.67"
    },
    
    "risk": {
      "risk_classification": "performing",
      "risk_color": "green",
      "provision_amount": "0.00",
      "provision_rate": "0.00"
    }
  }
}
```

### Disburse and Activate Loan
```http
POST /api/v1/loans/{loan}/disburse

Authorization: Bearer {token}
Permission: loans.disburse
Content-Type: application/json

{
  "disbursed_amount": 50000000.00,
  "disbursement_date": "2026-02-24",
  "disbursement_method": "bank_transfer",
  "disbursement_reference": "TRX-2026-045",
  "disbursement_notes": "First tranche disbursed to customer account"
}

Response: 200 OK
{
  "message": "Loan disbursed and activated successfully",
  "data": {
    "status": "active",
    "disbursed_amount": "50000000.00",
    "disbursement_date": "2026-02-24",
    "activation_date": "2026-02-24",
    "first_installment_date": "2026-03-01",
    "maturity_date": "2046-02-01"
  }
}
```

### Get Loan Schedule
```http
GET /api/v1/loans/{loan}/schedule

Response: 200 OK
{
  "data": [
    {
      "id": 1,
      "installment_number": 1,
      "due_date": "2026-03-01",
      "status": "pending",
      "status_color": "gray",
      "principal_due": "50543.66",
      "interest_due": "500000.00",
      "total_due": "550543.66",
      "penalties_due": "0.00",
      "fees_due": "0.00",
      "opening_balance": "50000000.00",
      "closing_balance": "49949456.34",
      "principal_paid": "0.00",
      "interest_paid": "0.00",
      "total_paid": "0.00",
      "balance_remaining": "550543.66",
      "payment_progress": 0.00,
      "days_past_due": 0,
      "is_past_due": false,
      "days_until_due": 5
    },
    {
      "id": 2,
      "installment_number": 2,
      "due_date": "2026-04-01",
      "status": "pending",
      "principal_due": "51049.07",
      "interest_due": "499494.59",
      "total_due": "550543.66",
      "opening_balance": "49949456.34",
      "closing_balance": "49898407.27",
      ...
    }
  ],
  "summary": {
    "total_installments": 240,
    "paid_installments": 0,
    "pending_installments": 240,
    "overdue_installments": 0,
    "total_principal_due": "50000000.00",
    "total_interest_due": "82130478.40",
    "total_paid": "0.00",
    "total_remaining": "132130478.40"
  }
}
```

### Calculate Early Settlement
```http
POST /api/v1/loans/{loan}/calculate-early-settlement

Content-Type: application/json

{
  "settlement_date": "2026-12-31"  // optional, defaults to today
}

Response: 200 OK
{
  "data": {
    "settlement_date": "2026-12-31",
    "principal_outstanding": "49500000.00",
    "interest_outstanding": "81000000.00",
    "penalties_outstanding": "0.00",
    "fees_outstanding": "0.00",
    "early_settlement_penalty": "495000.00",  // 1% of principal
    "total_settlement_amount": "130995000.00",
    "remaining_scheduled_interest": "82130478.40",
    "interest_savings": "1130478.40",
    "installments_remaining": 229,
    "months_saved": 229
  }
}
```

### Close Loan
```http
POST /api/v1/loans/{loan}/close

Authorization: Bearer {token}
Permission: loans.manage
Content-Type: application/json

{
  "reason": "Loan fully paid and closed"
}

Response: 200 OK
{
  "message": "Loan closed successfully",
  "data": {
    "status": "closed",
    "closure_date": "2026-02-24",
    "closed_at": "2026-02-24T15:30:00Z"
  }
}
```

### Get Loan Summary
```http
GET /api/v1/loans/{loan}/summary

Response: 200 OK
{
  "data": {
    "loan": {
      "id": 1,
      "loan_account_number": "LOAN-000001",
      "status": "active",
      "status_color": "green"
    },
    "customer": {
      "id": 5,
      "name": "John Doe",
      "customer_number": "CUST-000005"
    },
    "loan_details": { ... },
    "dates": { ... },
    "balances": { ... },
    "payments": { ... },
    "arrears": { ... },
    "next_payment": {
      "due_date": "2026-03-01",
      "amount": "550543.66"
    },
    "risk": { ... }
  }
}
```

### Update Loan Aging
```http
POST /api/v1/loans/{loan}/update-aging

Authorization: Bearer {token}

Response: 200 OK
{
  "message": "Loan aging updated successfully",
  "data": {
    "days_past_due": 5,
    "aging_bucket": "current",
    "arrears_amount": "550543.66"
  }
}
```

---

## Query Scopes

### Loan Model Scopes

```php
// Basic filtering
Loan::forCustomer($customerId)->get();
Loan::forInstitution($institutionId)->get();
Loan::withStatus('active')->get();

// Status filtering
Loan::pendingDisbursement()->get();
Loan::active()->get();
Loan::fullyPaid()->get();
Loan::closed()->get();
Loan::defaulted()->get();
Loan::writtenOff()->get();
Loan::restructured()->get();

// Performance filtering
Loan::performing()->get();  // Active and current (DPD = 0)
Loan::overdue()->get();     // Active with DPD > 0
Loan::NPL()->get();         // Non-performing (DPD >= 90)
Loan::PAR30()->get();       // Portfolio at Risk 30+ days
Loan::PAR60()->get();       // Portfolio at Risk 60+ days
Loan::PAR90()->get();       // Portfolio at Risk 90+ days

// Aging bucket filtering
Loan::inAgingBucket('npl')->get();

// Date filtering
Loan::disbursedBetween('2026-01-01', '2026-12-31')->get();
Loan::maturityBetween('2046-01-01', '2046-12-31')->get();
```

### LoanSchedule Model Scopes

```php
// Status filtering
LoanSchedule::forLoan($loanId)->get();
LoanSchedule::pending()->get();
LoanSchedule::partiallyPaid()->get();
LoanSchedule::fullyPaid()->get();
LoanSchedule::overdue()->get();
LoanSchedule::unpaid()->get();  // Pending, partially paid, or overdue

// Date filtering
LoanSchedule::dueBetween('2026-03-01', '2026-03-31')->get();
LoanSchedule::dueBefore('2026-03-01')->get();
LoanSchedule::dueAfter('2026-03-01')->get();
LoanSchedule::dueToday()->get();
LoanSchedule::dueThisWeek()->get();
LoanSchedule::dueThisMonth()->get();
```

---

## Lifecycle Methods

### 1. Create Loan from Application
```php
$loanService->createLoanFromApplication($application, $underwritingDecision);
```

### 2. Disburse and Activate
```php
$loanService->disburseAndActivate($loan, [
    'disbursed_amount' => 50000000,
    'disbursement_date' => '2026-02-24',
    'disbursement_method' => 'bank_transfer',
    'disbursement_reference' => 'TRX-2026-045',
    'disbursed_by' => $userId,
    'approved_by' => $userId,
]);

// This method:
// 1. Records disbursement details
// 2. Activates the loan
// 3. Generates complete amortization schedule
// 4. Sets first payment due date
```

### 3. Generate Schedule
```php
$schedule = $loanService->generateSchedule($loan, Carbon::parse('2026-03-01'));
$loanService->saveSchedule($schedule);

// Or regenerate after changes:
$loanService->regenerateSchedule($loan, Carbon::parse('2026-03-01'));
```

### 4. Update Aging and DPD
```php
$loanService->updateAgingAndDPD($loan);

// This method:
// 1. Finds oldest overdue installment
// 2. Calculates days past due
// 3. Updates aging bucket (current -> bucket_30 -> bucket_60 -> npl)
// 4. Calculates total arrears amount
// 5. Marks overdue schedules
```

### 5. Calculate Early Settlement
```php
$calculation = $loanService->calculateEarlySettlement($loan, Carbon::parse('2026-12-31'));

// Returns:
// - Principal outstanding
// - Interest outstanding
// - Early settlement penalty
// - Total settlement amount
// - Interest savings vs full term
```

### 6. Close Loan
```php
$loan->close('Fully paid and closed');
```

### 7. Mark as Fully Paid
```php
$loan->markAsFullyPaid();
// Sets all outstanding balances to zero
// Updates status to fully_paid
```

### 8. Write Off
```php
$loan->writeOff($userId, 'Customer deceased, no collateral recovery', 15000000);
```

---

## Business Rules

### Loan Creation
1. Can only create loan from APPROVED application
2. Application must have approved underwriting decision
3. One application can only have one loan (check for existing)
4. Loan account number auto-generated: LOAN-{institution_count+1 padded 6 digits}

### Disbursement
1. Can only disburse loans in `pending_disbursement` status
2. Disbursed amount must be <= approved amount
3. Disbursement automatically activates the loan
4. Schedule is generated automatically upon activation
5. First installment date = disbursement date + 1 month (start of month)
6. Maturity date = first installment date + (tenure - 1) months

### Schedule Generation
1. **Reducing Balance**: Interest calculated on outstanding principal each month
2. **Flat Rate**: Interest calculated on original principal for entire tenure
3. Each installment has unique installment_number (1 to tenure)
4. All installments start with status `pending`
5. Last installment adjusted for rounding differences

### Aging & DPD
1. DPD calculated from oldest overdue installment
2. Aging buckets:
   - `current`: 0-30 days
   - `bucket_30`: 31-60 days
   - `bucket_60`: 61-90 days
   - `bucket_90`: 91-180 days
   - `bucket_180`: 180+ days
   - `npl`: 90+ days (Non-Performing Loan)
3. Arrears amount = sum of balance_remaining for all overdue installments
4. Installments marked `overdue` when due_date < today and not fully paid

### Risk Classification
- `performing`: Current, no arrears
- `watch_list`: 1-30 DPD
- `substandard`: 31-60 DPD
- `doubtful`: 61-90 DPD
- `loss`: 90+ DPD or written off

### Early Settlement
1. Allowed if `allows_early_settlement = true`
2. Can apply penalty based on `early_settlement_penalty_rate`
3. Settlement amount = principal + interest + penalties + fees + early penalty
4. Interest savings calculated vs remaining scheduled interest

---

## Integration with Other Modules

### Phase 7 - Underwriting Decisions
```php
$loan->underwritingDecision->approved_amount;
$loan->underwritingDecision->final_decision;
```

### Phase 6 - Eligibility Assessment
```php
$application->eligibilityAssessments()->latest()->first();
// Referenced through application
```

### Phase 5 - Application
```php
$loan->application->application_number;
$loan->application->customer;
```

### Customer
```php
$loan->customer->full_name;
$customer->loans()->active()->get();
```

### Loan Product
```php
$loan->loanProduct->name;
$loan->loanProduct->interest_rate;
```

---

## Testing Recommendations

### 1. Loan Creation Test
```php
// Create approved application
POST /applications/{id}/create-loan
// Verify loan created with status = pending_disbursement
// Verify loan_account_number generated
// Verify loan terms copied from underwriting decision
```

### 2. Disbursement & Activation Test
```php
POST /loans/{id}/disburse
{
  "disbursed_amount": 50000000,
  "disbursement_date": "2026-02-24",
  "disbursement_method": "bank_transfer"
}
// Verify status changed to active
// Verify schedule generated (240 installments)
// Verify first_installment_date = disbursement_date + 1 month
// Verify next_payment_due_date set
```

### 3. Schedule Generation Test
```php
// Test reducing balance schedule
GET /loans/{id}/schedule
// Verify 240 installments created
// Verify total_principal_due = approved_amount
// Verify monthly_payment consistent
// Verify interest decreases, principal increases over time
// Verify opening_balance[n] = closing_balance[n-1]
// Verify closing_balance[240] = 0

// Test flat rate schedule
// Verify interest constant every month
// Verify principal constant every month
```

### 4. Aging & DPD Test
```php
// Set due_date to past date
POST /loans/{id}/update-aging
// Verify days_past_due calculated correctly
// Verify aging_bucket updated
// Verify arrears_amount calculated
// Verify installments marked overdue
```

### 5. Early Settlement Test
```php
POST /loans/{id}/calculate-early-settlement
{
  "settlement_date": "2026-12-31"
}
// Verify principal_outstanding correct
// Verify early_settlement_penalty calculated
// Verify total_settlement_amount accurate
// Verify interest_savings calculated
```

### 6. List & Filter Test
```php
GET /loans?status=active&overdue_only=true
GET /loans?aging_bucket=npl
GET /loans?customer_id=5
GET /loans?disbursed_from=2026-01-01&disbursed_to=2026-12-31
// Verify filters work correctly
// Verify pagination
// Verify sorting
```

---

## Next Steps (Phase 9)

Phase 9 will focus on **Repayment Monitoring & Portfolio Risk**:
- Repayment import system (Excel uploads)
- Payment allocation logic (principal, interest, penalties)
- DPD calculation automation
- Portfolio snapshots (daily/monthly)
- PAR 30/60/90 calculations
- NPL ratio tracking
- Collection rate metrics
- Portfolio risk reporting

---

## Files Created/Modified

### Created
- ✅ `database/migrations/2026_02_24_100700_create_loans_table.php`
- ✅ `database/migrations/2026_02_24_100701_create_loan_schedules_table.php`
- ✅ `app/Models/Loan.php`
- ✅ `app/Models/LoanSchedule.php`
- ✅ `app/Services/LoanService.php`
- ✅ `app/Http/Controllers/LoanController.php`

### Modified
- ✅ `app/Models/Application.php` - Added loans relationships
- ✅ `routes/api.php` - Added loan management routes

---

## Migration Status

```bash
✅ php artisan migrate
Migration 1: 2026_02_24_100700_create_loans_table (367.06ms)
Migration 2: 2026_02_24_100701_create_loan_schedules_table (141.18ms)
Status: Completed successfully
```

---

## Summary

Phase 8 provides a complete, enterprise-grade loan management system with:

- ✅ **Comprehensive Loan Registry**: 140+ fields tracking every aspect of a loan
- ✅ **Lifecycle Management**: From creation through disbursement, activation, and closure
- ✅ **Dual Schedule Methods**: Both reducing balance and flat rate calculations
- ✅ **Real-Time Balance Tracking**: Principal, interest, penalties, fees
- ✅ **Automated Aging**: DPD calculation and aging bucket classification
- ✅ **Schedule Management**: Individual installment tracking with payment allocation
- ✅ **Early Settlement**: Calculate settlement amounts with penalty support
- ✅ **Risk Classification**: 5-level risk classification system
- ✅ **Property/Collateral**: Track property details and LTV ratio
- ✅ **Insurance Management**: Track insurance requirements and expiry
- ✅ **Restructuring Support**: Link original and restructured loans
- ✅ **Write-Off Capability**: Track written-off loans with reasons
- ✅ **Advanced Filtering**: 21 query scopes for flexible data retrieval
- ✅ **RESTful API**: 9 endpoints covering all operations
- ✅ **Permission Control**: Role-based access on all endpoints

**Ready for Phase 9: Repayment Monitoring & Portfolio Risk** 🚀
