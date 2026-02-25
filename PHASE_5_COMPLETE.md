# Phase 5: Bank Statement Analytics Engine - Implementation Complete ✅

## Overview
Phase 5 implementation is complete. The system can now upload, parse, and analyze bank statements to extract income patterns, detect debt obligations, compute risk metrics, and generate affordability scores for loan underwriting decisions.

---

## Components Created

### 1. Database Schema (4 Migrations)

#### `applications` table
- Stores loan applications with property details and workflow tracking
- Fields: application_number (auto-generated), status (draft→submitted→under_review→approved/rejected), requested_amount/tenure, property details, workflow timestamps

#### `bank_statement_imports` table
- Tracks uploaded bank statement files and import progress
- Fields: file metadata, import_status (pending→processing→completed/failed), row counters, statement date range, error_log JSON

#### `bank_transactions` table
- Stores individual transactions from imported bank statements
- Fields: transaction_hash (MD5 for deduplication), date, description, debit/credit/balance, transaction_type enum, is_income/is_expense/is_debt_payment flags, risk_flags JSON

#### `statement_analytics` table
- Stores computed analytics results
- Fields: monthly aggregations (inflows/outflows/net_surplus arrays), income classification (salary/business/mixed), income stability score (0-100), debt obligations, volatility score, risk flags (negative balance, bounces, gambling), overall risk assessment (low/medium/high), debt-to-income ratio

### 2. Enums (4 Files)

- **ImportStatus**: PENDING, PROCESSING, COMPLETED, FAILED
- **TransactionType**: 20 types including SALARY, BUSINESS_INCOME, DEBT_PAYMENT, RENT_EXPENSE, GAMBLING, etc.
- **IncomeClassification**: SALARY, BUSINESS, MIXED, IRREGULAR, UNKNOWN
- **ApplicationStatus**: DRAFT, SUBMITTED, UNDER_REVIEW, APPROVED, REJECTED, DISBURSED, CLOSED

### 3. Models (4 Files + Customer Updated)

All models include comprehensive relationships, scopes, computed attributes, and business logic methods.

**BankStatementImport**: Status management, progress tracking, file size formatting
**BankTransaction**: Auto-hashing for deduplication, risk flag management, amount parsing
**StatementAnalytics**: Affordability score calculation (0-100), risk assessment, monthly data accessors
**Application**: Auto-generated application numbers, workflow state management, LTV ratio calculation
**Customer**: Added relationships to new Phase 5 tables

### 4. Business Logic

#### **BankStatementController** (9 endpoints)
- `index()`: List imports with filters (customer_id, status) and pagination
- `store()`: Upload bank statement (validates xlsx/xls/csv, max 50MB), stores to private disk, dispatches ParseBankStatementJob
- `show()`: View import details with customer, institution, transactions (limit 100), and analytics
- `transactions()`: Paginated transaction list with filters (type, flagged)
- `analytics()`: Get computed analytics for import
- `recomputeAnalytics()`: Re-run analytics computation
- `destroy()`: Delete import and file
- `download()`: Download original uploaded file
- `customerStats()`: Get customer's import statistics

#### **ParseBankStatementJob** (Async Queue Job)
- Validates Excel file headers (date, description, debit, credit, balance)
- Handles Excel serial dates and string dates
- Parses Tanzanian currency formatting (TZS, TSh, commas)
- Generates MD5 transaction hash for deduplication
- Batch inserts 500 rows at a time for performance
- Tracks statement date range and calculates analysis months
- Logs row-level errors to error_log JSON array
- Dispatches ComputeAnalyticsJob on successful completion
- Timeout: 10 minutes, Retries: 3 attempts

#### **StatementAnalyticsService** (Core Analytics Engine)
**Income Analysis:**
- Detects salary patterns (regular monthly credits, keywords: salary, wage, payroll, employer)
- Detects business income (keywords: sales, invoice, payment received, mpesa)
- Classifies income type: SALARY, BUSINESS, MIXED, IRREGULAR, UNKNOWN
- Calculates income stability score using coefficient of variation (0-100, higher = more stable)
- Estimates net income based on detected patterns

**Debt Analysis:**
- Pattern matches debt payments (keywords: loan, credit, repayment, installment, mortgage)
- Groups similar amounts to detect recurring debts
- Calculates total debt obligations and monthly average
- Marks transactions as debt payments

**Risk Metrics:**
- Cash flow volatility score (standard deviation of monthly net surplus)
- Negative balance day count
- Bounce/return transaction detection (keywords: bounce, insufficient, rejected)
- Gambling transaction detection (keywords: bet, betting, casino, gamble)
- Large unexplained outflows (> 50% of avg monthly inflow)
- Flags: high_volatility, frequent_negative_balance, bounced_transactions, gambling_activity

**Overall Risk Assessment:**
- Scoring algorithm (0-100 risk points):
  - Volatility: 0-30 points
  - Negative balance: 0-20 points
  - Bounces: 0-25 points (10 per bounce)
  - Gambling: 0-15 points
  - Income stability: 0-10 points
- Risk levels: HIGH (60+), MEDIUM (30-59), LOW (0-29)

**Financial Ratios:**
- Debt-to-Income ratio (monthly debt / monthly income * 100)
- Disposable Income ratio ((income - debt) / income * 100)

#### **ComputeAnalyticsJob** (Async Queue Job)
- Loads all transactions for the import
- Calls StatementAnalyticsService to compute comprehensive analytics
- Creates StatementAnalytics record with all metrics
- Logs computation results (risk assessment, income classification, DTI ratio)
- Timeout: 10 minutes, Retries: 3 attempts

### 5. API Routes (Added to routes/api.php)

All routes require `auth:sanctum` and `permission:customers.view` middleware:

```
GET    /api/v1/bank-statements                              # List imports
POST   /api/v1/bank-statements                              # Upload file (requires customers.manage-kyc)
GET    /api/v1/bank-statements/{id}                         # View import
DELETE /api/v1/bank-statements/{id}                         # Delete import (requires customers.manage-kyc)
GET    /api/v1/bank-statements/{id}/transactions            # List transactions
GET    /api/v1/bank-statements/{id}/analytics               # View analytics
POST   /api/v1/bank-statements/{id}/recompute-analytics     # Re-run analytics
GET    /api/v1/bank-statements/{id}/download                # Download file
GET    /api/v1/bank-statements/customers/{id}/stats         # Customer stats
```

### 6. Feature Tests

Created comprehensive test suite (`tests/Feature/BankStatementAnalyticsTest.php`):
- Upload validation (file type, size)
- List imports with pagination
- View import with transactions
- Customer statistics
- Sample CSV bank statement generator for testing

---

## Data Flow

1. **Upload**: User uploads Excel file via `POST /api/v1/bank-statements`
   - Controller validates file (type, size), stores to private disk
   - Creates import record with status PENDING
   - Dispatches `ParseBankStatementJob` to queue

2. **Parsing**: `ParseBankStatementJob` processes file asynchronously
   - Validates headers, parses rows, handles date/currency formatting
   - Deduplicates via MD5 hash check
   - Batch inserts transactions (500 rows per batch)
   - Updates import status to COMPLETED
   - Dispatches `ComputeAnalyticsJob`

3. **Analytics**: `ComputeAnalyticsJob` analyzes transactions
   - Groups transactions by month
   - Computes monthly aggregations (inflows, outflows, net surplus)
   - Detects income patterns (salary vs business)
   - Identifies debt obligations
   - Calculates risk metrics (volatility, negative balance, bounces, gambling)
   - Determines overall risk assessment
   - Creates `StatementAnalytics` record

4. **Consumption**: API consumers access results
   - View computed analytics via `GET /api/v1/bank-statements/{id}/analytics`
   - Browse transactions via `GET /api/v1/bank-statements/{id}/transactions`
   - Use affordability score, risk assessment, and DTI ratio for underwriting decisions

---

## Key Features

### Deduplication Strategy
- MD5 hash: `customer_id + date + description + debit + credit`
- Prevents duplicate transactions if same statement uploaded multiple times
- Checked before each insert

### Batch Processing
- Inserts 500 rows per batch to optimize database performance
- Can handle large statements (50k-200k rows) efficiently
- 10-minute timeout per job

### Error Handling
- Row-level error capture (doesn't fail entire import if one row invalid)
- Errors logged to `error_log` JSON array with row number and message
- Job failures logged with full stack trace
- 3 retry attempts before permanent failure

### Multi-tenancy Support
- All queries scoped by `institution_id`
- Files stored with path pattern: `bank-statements/{institution_id}/{customer_id}/{timestamp}_{filename}`
- Users can only access data from their own institution

### Performance Optimizations
- Chunk reading: Laravel Excel reads file in chunks to avoid memory issues
- Batch inserts: 500 rows at a time
- Database indexes: On customer_id, import_id, date, type, transaction_hash
- Lazy loading: Transactions limited to 100 in `show()` endpoint

---

## Testing Recommendations

### 1. Run Database Migrations
```bash
php artisan migrate
```

### 2. Run Feature Tests
```bash
php artisan test --filter BankStatementAnalyticsTest
```

### 3. Manual API Testing

**Upload Bank Statement:**
```bash
curl -X POST http://localhost/api/v1/bank-statements \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: multipart/form-data" \
  -F "customer_id=1" \
  -F "file=@/path/to/bank_statement.xlsx"
```

**View Import:**
```bash
curl -X GET http://localhost/api/v1/bank-statements/1 \
  -H "Authorization: Bearer {token}"
```

**View Analytics:**
```bash
curl -X GET http://localhost/api/v1/bank-statements/1/analytics \
  -H "Authorization: Bearer {token}"
```

### 4. Queue Worker
The jobs run asynchronously, so ensure queue worker is running:
```bash
php artisan queue:work
```

### 5. Test File Format
Bank statement Excel/CSV must have these columns (case-insensitive):
- Date (Excel serial or string format)
- Description
- Debit (amount debited from account)
- Credit (amount credited to account)
- Balance (running balance)

Sample row:
```
2026-01-05 | Salary Payment from ABC Corp | 0 | 3500000.00 | 4500000.00
```

---

## Database Status

All 18 migrations successfully executed:
- Phase 1-4: 14 tables (institutions, users, roles, permissions, customers, kyc_documents, loan_products, etc.)
- Phase 5: 4 new tables (applications, bank_statement_imports, bank_transactions, statement_analytics)

Seeded data intact:
- 2 institutions
- 8 roles with 51 permissions
- 2 users
- 4 loan products
- 5 customers with 7 KYC documents

---

## Next Steps (Phase 6: Eligibility Assessment Engine)

Based on PROJECT_ROADMAP.md, Phase 6 will build:
1. Eligibility rules engine with configurable KYC, credit bureau, income, debt ratio, age, and collateral checks
2. Automated rule evaluation with pass/fail/conditional results
3. Stress testing for income shocks and interest rate increases
4. Eligibility reports with detailed metrics and recommendations

Phase 5 provides the foundation: **StatementAnalytics** data (affordability score, DTI ratio, income stability, risk assessment) will feed directly into Phase 6 eligibility rules.

---

## Files Created/Modified

**Created:**
- `database/migrations/2026_02_24_100100_create_applications_table.php`
- `database/migrations/2026_02_24_100200_create_bank_statement_imports_table.php`
- `database/migrations/2026_02_24_100300_create_bank_transactions_table.php`
- `database/migrations/2026_02_24_100400_create_statement_analytics_table.php`
- `app/Enums/ImportStatus.php`
- `app/Enums/TransactionType.php`
- `app/Enums/IncomeClassification.php`
- `app/Enums/ApplicationStatus.php`
- `app/Models/BankStatementImport.php`
- `app/Models/BankTransaction.php`
- `app/Models/StatementAnalytics.php`
- `app/Models/Application.php`
- `app/Http/Controllers/BankStatementController.php`
- `app/Jobs/ParseBankStatementJob.php`
- `app/Jobs/ComputeAnalyticsJob.php`
- `app/Services/StatementAnalyticsService.php`
- `tests/Feature/BankStatementAnalyticsTest.php`

**Modified:**
- `app/Models/Customer.php` (added relationships)
- `routes/api.php` (added bank-statements endpoints)

---

## Summary

✅ **Phase 5 Implementation Complete**

The Bank Statement Analytics Engine is fully functional with:
- Excel file upload and validation
- Async parsing with deduplication and error handling
- Comprehensive analytics computation (income detection, debt analysis, risk scoring)
- RESTful API for accessing imports, transactions, and analytics
- Multi-tenant architecture with institution scoping
- Feature tests for quality assurance

**Ready for Phase 6: Eligibility Assessment Engine**
