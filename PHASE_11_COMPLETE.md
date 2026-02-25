# Phase 11: Reporting & Analytics Module - COMPLETE

## Overview
Phase 11 implements comprehensive reporting and analytics capabilities for the mortgage platform, providing executive dashboards, PDF reports, Excel exports, and real-time analytics across all modules.

## Completion Date
**Completed:** February 24, 2026

## Components Implemented

### 1. Services Layer (3 Services)

#### ReportService
**File:** `app/Services/ReportService.php`

**Purpose:** Generate PDF reports and JSON analytics using DomPDF and application data aggregation.

**Methods:**
1. **generateEligibilityReport($institutionId, $applicationId)**: PDF eligibility assessment report
   - Application details, customer info, loan product
   - Eligibility assessment results (DTI, DSR, LTV, ratios)
   - Risk grade and recommendation
   - Output: `storage/app/reports/eligibility-report-{id}-{timestamp}.pdf`

2. **generateBankStatementReport($institutionId, $statementId)**: PDF bank statement analytics
   - Statement import details
   - Transaction summary (income, expenses, net flow)
   - Category breakdown
   - Behavioral analysis (gambling, penalties, etc.)
   - Output: `storage/app/reports/bank-statement-report-{id}-{timestamp}.pdf`

3. **generateApplicationSummaryReport($institutionId, $applicationId)**: Comprehensive application PDF
   - Complete application details
   - Customer & KYC information
   - Bank statement analytics
   - Eligibility assessment
   - Underwriting decision
   - Output: `storage/app/reports/application-summary-{id}-{timestamp}.pdf`

4. **generateAffordabilityReport($institutionId, $applicationId)**: PDF affordability & stress test
   - Income and expense breakdown
   - Debt obligations
   - Affordability calculations (DTI, DSR, surplus)
   - Stress test scenarios
   - Output: `storage/app/reports/affordability-report-{id}-{timestamp}.pdf`

5. **generateMonthlyPortfolioPack($institutionId, $year, $month)**: PDF monthly portfolio report
   - Portfolio snapshot (outstanding, disbursements, collections)
   - Aging distribution
   - PAR (Portfolio at Risk) metrics
   - NPL (Non-Performing Loan) metrics
   - Month-over-month trends
   - Output: `storage/app/reports/portfolio-pack-{year}-{month}-{timestamp}.pdf`

6. **generateApprovalRateReport($institutionId, $months)**: JSON approval rate analysis
   - Applications by month
   - Approvals, declines, pending counts
   - Approval rate percentage
   - By product breakdown
   - Returns: JSON data structure

7. **generateRiskDistributionReport($institutionId)**: JSON risk grade distribution
   - Applications by risk grade (A, B, C, D, E)
   - Average DTI, DSR, LTV per grade
   - Risk profile summary
   - Returns: JSON data structure

8. **getDeclineReasonAnalysis($institutionId, $months)**: JSON decline reasons breakdown
   - Total declined applications
   - Decline reasons with counts and percentages
   - Top 5 decline reasons
   - Monthly trends
   - Returns: JSON data structure

#### ExportService
**File:** `app/Services/ExportService.php`

**Purpose:** Export data to Excel format using Maatwebsite/Excel package.

**Methods:**
1. **exportApplications($institutionId, $status, $startDate, $endDate)**: Export applications
   - **Filters:** status (pending/approved/declined), date range
   - **Columns:** Application ID, Customer Name, Email, Phone, Product, Requested Amount, Status, Risk Grade, Decision, Decision Date, Created At
   - Output: `storage/app/exports/applications-export-{timestamp}.xlsx`

2. **exportLoans($institutionId)**: Export loan portfolio
   - **Columns:** Loan ID, Contract Number, Customer, Product, Disbursed Amount, Outstanding Principal, Interest Outstanding, Penalties, Total Outstanding, Status, DPD, Aging Bucket, Risk Classification, Disbursement Date
   - Output: `storage/app/exports/loans-export-{timestamp}.xlsx`

3. **exportRepayments($institutionId, $startDate, $endDate, $loanId)**: Export payment history
   - **Filters:** date range, specific loan
   - **Columns:** Repayment ID, Loan ID, Contract Number, Customer, Payment Date, Amount, Principal, Interest, Penalties, Fees, Payment Method, Reference, Status
   - Output: `storage/app/exports/repayments-export-{timestamp}.xlsx`

4. **exportCollectionsQueue($institutionId)**: Export collections queue
   - **Columns:** Queue ID, Loan Contract, Customer, Priority Score, Level, DPD, Bucket, Total Arrears, Principal Arrears, Interest Arrears, Last Payment Date, Contact Attempts, Last Contact, Assigned Officer, Status
   - Output: `storage/app/exports/collections-queue-export-{timestamp}.xlsx`

5. **exportPortfolioSummary($institutionId)**: Multi-sheet Excel portfolio summary
   - **Sheet 1 - Portfolio Summary:** Total loans, active loans, disbursed, outstanding, collected, arrears
   - **Sheet 2 - Aging Distribution:** Count and amount by aging bucket (Current, 30, 60, 90, 180, NPL)
   - Output: `storage/app/exports/portfolio-summary-export-{timestamp}.xlsx`

**Helper Methods:**
- `generateExcel($data, $filename)`: Single sheet Excel generation
- `generateExcelMultiSheet($sheets, $filename)`: Multi-sheet Excel generation

#### DashboardService
**File:** `app/Services/DashboardService.php`

**Purpose:** Aggregate dashboard data from all modules (applications, portfolio, collections, trends).

**Methods:**
1. **getExecutiveDashboard($institutionId)**: Executive dashboard overview
   - Applications metrics (total, approved, declined, approval rate)
   - Portfolio metrics (total loans, disbursed, outstanding, NPL/PAR ratios)
   - Collections metrics (queue size, arrears, active PTPs)
   - 12-month trends (applications, disbursements, collections, PAR/NPL)
   - Returns: Comprehensive JSON structure

2. **getPortfolioPerformance($institutionId)**: Portfolio performance dashboard
   - Aging distribution (count by bucket)
   - Risk classification distribution
   - Product distribution (count, amount, outstanding)
   - Portfolio metrics summary
   - Returns: JSON data structure

3. **getCollectionsPerformance($institutionId, $startDate, $endDate)**: Collections dashboard
   - Actions taken (total, successful, success rate)
   - Promise to Pay performance (total, kept, broken, fulfillment rate)
   - Officer performance (actions per officer)
   - Queue metrics
   - Returns: JSON data structure

4. **getRiskTrends($institutionId, $months)**: Risk trends over time
   - PAR trend (PAR30, PAR60, PAR90 by month)
   - NPL trend (ratio, count, amount by month)
   - Collection rate trend (expected vs actual)
   - Returns: JSON time series data

5. **getMonthlyKPIs($institutionId, $year, $month)**: Monthly KPI summary
   - Application metrics
   - Disbursement metrics (count, amount, avg loan size)
   - Collection metrics (count, amount, principal, interest, penalties)
   - Portfolio snapshot
   - Returns: JSON KPI structure

**Protected Helper Methods:**
- `getApplicationMetrics()`: Application-specific calculations
- `getPortfolioMetrics()`: Portfolio-level aggregations
- `getCollectionsMetrics()`: Collections queue analytics
- `getTrendMetrics()`: Monthly trend data generation
- `getDisbursementKPIs()`: Disbursement period calculations
- `getCollectionKPIs()`: Collection period calculations

### 2. Export Classes (2 Classes)

#### GenericExport
**File:** `app/Exports/GenericExport.php`

**Purpose:** Single-sheet Excel export with automatic header extraction from data.

**Implements:**
- `FromCollection`: Uses Eloquent collection
- `WithHeadings`: Auto-extracts headers from first data row
- `WithStyles`: Bold header styling

**Usage:**
```php
$export = new GenericExport($data);
Excel::download($export, 'filename.xlsx');
```

#### MultiSheetExport
**File:** `app/Exports/MultiSheetExport.php`

**Purpose:** Multi-sheet Excel export with per-sheet styling.

**Implements:**
- `WithMultipleSheets`: Creates multiple sheets in single workbook

**Usage:**
```php
$sheets = [
    'Summary' => Collection::make([...]),
    'Details' => Collection::make([...]),
];
$export = new MultiSheetExport($sheets);
Excel::download($export, 'filename.xlsx');
```

### 3. Controller Layer

#### ReportController
**File:** `app/Http/Controllers/ReportController.php`

**Purpose:** Handle all report generation, export, and dashboard API requests.

**Endpoints (19 total):**

**PDF Report Endpoints (5):**
1. `GET /reports/{institutionId}/eligibility/{applicationId}` - Eligibility report PDF
2. `GET /reports/{institutionId}/bank-statement/{statementId}` - Bank statement report PDF
3. `GET /reports/{institutionId}/application/{applicationId}/summary` - Application summary PDF
4. `GET /reports/{institutionId}/application/{applicationId}/affordability` - Affordability report PDF
5. `POST /reports/{institutionId}/portfolio/monthly-pack` - Monthly portfolio pack PDF
   - **Body:** `year` (required), `month` (required)

**Analytics Endpoints (3):**
6. `GET /reports/{institutionId}/analytics/approval-rate` - Approval rate analysis (JSON)
   - **Query:** `months` (optional, default: 6, max: 24)
7. `GET /reports/{institutionId}/analytics/risk-distribution` - Risk distribution (JSON)
8. `GET /reports/{institutionId}/analytics/decline-reasons` - Decline reason analysis (JSON)
   - **Query:** `months` (optional, default: 6, max: 24)

**Export Endpoints (5):**
9. `POST /reports/{institutionId}/export/applications` - Export applications Excel
   - **Body:** `status` (optional: pending/approved/declined), `start_date`, `end_date`
10. `POST /reports/{institutionId}/export/loans` - Export loans Excel
11. `POST /reports/{institutionId}/export/repayments` - Export repayments Excel
    - **Body:** `start_date`, `end_date`, `loan_id` (optional)
12. `POST /reports/{institutionId}/export/collections-queue` - Export collections queue Excel
13. `POST /reports/{institutionId}/export/portfolio-summary` - Export portfolio summary Excel

**Dashboard Endpoints (5):**
14. `GET /dashboard/{institutionId}/executive` - Executive dashboard
15. `GET /dashboard/{institutionId}/portfolio-performance` - Portfolio performance dashboard
16. `GET /dashboard/{institutionId}/collections-performance` - Collections performance dashboard
    - **Query:** `start_date`, `end_date`
17. `GET /dashboard/{institutionId}/risk-trends` - Risk trends dashboard
    - **Query:** `months` (optional, default: 12, max: 24)
18. `GET /dashboard/{institutionId}/monthly-kpis` - Monthly KPI dashboard
    - **Query:** `year` (required), `month` (required)

### 4. Routes Configuration

**File:** `routes/api.php`

**Route Prefix:** `/api/v1/reports/{institutionId}` and `/api/v1/dashboard/{institutionId}`

**Middleware:**
- `auth:sanctum`: Authentication required
- `permission:applications.view`: Basic view permission
- `permission:reports.generate`: PDF generation permission
- `permission:reports.view`: Analytics view permission
- `permission:reports.export`: Excel export permission

**Permissions Required:**
- `reports.generate` - Generate PDF reports
- `reports.view` - View analytics reports
- `reports.export` - Export data to Excel

## API Documentation

### PDF Report Generation

#### Generate Eligibility Report
```http
GET /api/v1/reports/{institutionId}/eligibility/{applicationId}
Authorization: Bearer {token}
```

**Response:**
- Type: `application/pdf`
- Disposition: `attachment; filename="eligibility-report-{id}-{timestamp}.pdf"`

**Example:**
```bash
curl -X GET \
  'http://localhost/api/v1/reports/1/eligibility/123' \
  -H 'Authorization: Bearer {token}' \
  --output eligibility-report.pdf
```

#### Generate Monthly Portfolio Pack
```http
POST /api/v1/reports/{institutionId}/portfolio/monthly-pack
Authorization: Bearer {token}
Content-Type: application/json

{
  "year": 2026,
  "month": 2
}
```

**Response:**
- Type: `application/pdf`
- Disposition: `attachment; filename="portfolio-pack-2026-02-{timestamp}.pdf"`

### JSON Analytics

#### Get Approval Rate Report
```http
GET /api/v1/reports/{institutionId}/analytics/approval-rate?months=6
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Approval rate report generated successfully",
  "data": {
    "period_months": 6,
    "overall_stats": {
      "total_applications": 450,
      "total_approved": 320,
      "total_declined": 100,
      "total_pending": 30,
      "overall_approval_rate": 71.11
    },
    "monthly_breakdown": [
      {
        "month": "2025-09",
        "applications": 75,
        "approved": 52,
        "declined": 18,
        "pending": 5,
        "approval_rate": 74.29
      },
      // ... more months
    ],
    "by_product": [
      {
        "product_id": 1,
        "product_name": "Standard Mortgage",
        "applications": 200,
        "approved": 150,
        "approval_rate": 75.0
      }
    ]
  }
}
```

#### Get Risk Distribution
```http
GET /api/v1/reports/{institutionId}/analytics/risk-distribution
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Risk distribution report generated successfully",
  "data": {
    "total_applications": 450,
    "with_risk_grade": 380,
    "distribution": {
      "A": {
        "count": 120,
        "percentage": 31.58,
        "avg_dti": 25.5,
        "avg_dsr": 30.2,
        "avg_ltv": 65.0
      },
      "B": { "count": 100, "percentage": 26.32, ... },
      "C": { "count": 80, "percentage": 21.05, ... },
      "D": { "count": 50, "percentage": 13.16, ... },
      "E": { "count": 30, "percentage": 7.89, ... }
    }
  }
}
```

### Excel Exports

#### Export Applications
```http
POST /api/v1/reports/{institutionId}/export/applications
Authorization: Bearer {token}
Content-Type: application/json

{
  "status": "approved",
  "start_date": "2026-01-01",
  "end_date": "2026-02-24"
}
```

**Response:**
- Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Disposition: `attachment; filename="applications-export-{timestamp}.xlsx"`

**Columns (14):**
1. Application ID
2. Customer Name
3. Email
4. Phone
5. Loan Product
6. Requested Amount
7. Status
8. Risk Grade
9. DTI Ratio
10. DSR Ratio
11. LTV Ratio
12. Decision
13. Decision Date
14. Created At

#### Export Portfolio Summary
```http
POST /api/v1/reports/{institutionId}/export/portfolio-summary
Authorization: Bearer {token}
```

**Response:**
- Type: Excel workbook with 2 sheets
- **Sheet 1 - Portfolio Summary:**
  * Total Loans
  * Active Loans
  * Total Disbursed
  * Total Outstanding
  * Total Collected
  * Total Arrears
  * NPL Count
  * NPL Ratio
  * PAR30 Ratio
  
- **Sheet 2 - Aging Distribution:**
  * Bucket (Current, 30 days, 60 days, 90 days, 180 days, NPL)
  * Count
  * Outstanding Amount

### Dashboard APIs

#### Executive Dashboard
```http
GET /api/v1/dashboard/{institutionId}/executive
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Executive dashboard data retrieved successfully",
  "data": {
    "applications": {
      "total": 75,
      "approved": 52,
      "declined": 18,
      "pending": 5,
      "approval_rate": 74.29,
      "approved_amount": 15600000
    },
    "portfolio": {
      "total_loans": 420,
      "active_loans": 380,
      "total_disbursed": 125000000,
      "total_outstanding": 87500000,
      "total_collected": 37500000,
      "total_arrears": 5250000,
      "loans_in_arrears": 45,
      "npl_count": 12,
      "npl_amount": 3200000,
      "npl_ratio": 3.66,
      "par30_count": 28,
      "par30_amount": 4800000,
      "par30_ratio": 5.49,
      "avg_loan_size": 297619.05
    },
    "collections": {
      "total_in_queue": 45,
      "critical_items": 8,
      "high_priority_items": 15,
      "total_arrears_in_queue": 5250000,
      "avg_days_past_due": 42.5,
      "active_ptps": 18,
      "due_ptps": 5
    },
    "trends": [
      {
        "month": "2025-03",
        "month_name": "Mar 2025",
        "applications": 68,
        "approved_applications": 48,
        "disbursements": 14400000,
        "collections": 3200000,
        "portfolio_outstanding": 82000000,
        "par30_ratio": 5.2,
        "npl_ratio": 3.4
      },
      // ... 11 more months
    ],
    "generated_at": "2026-02-24T10:30:00.000000Z"
  }
}
```

#### Portfolio Performance Dashboard
```http
GET /api/v1/dashboard/{institutionId}/portfolio-performance
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Portfolio performance data retrieved successfully",
  "data": {
    "aging_distribution": {
      "current": 320,
      "bucket_30": 28,
      "bucket_60": 15,
      "bucket_90": 8,
      "bucket_180": 5,
      "npl": 4
    },
    "risk_distribution": {
      "performing": 335,
      "watch_list": 28,
      "substandard": 10,
      "doubtful": 5,
      "loss": 2
    },
    "product_distribution": {
      "1": {
        "count": 200,
        "amount": 60000000,
        "outstanding": 42000000
      },
      "2": {
        "count": 180,
        "amount": 65000000,
        "outstanding": 45500000
      }
    },
    "portfolio_metrics": {
      // ... same as executive dashboard portfolio section
    }
  }
}
```

#### Collections Performance Dashboard
```http
GET /api/v1/dashboard/{institutionId}/collections-performance?start_date=2026-01-01&end_date=2026-02-24
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Collections performance data retrieved successfully",
  "data": {
    "period": {
      "start": "2026-01-01",
      "end": "2026-02-24"
    },
    "actions": {
      "total": 245,
      "successful": 180,
      "success_rate": 73.47
    },
    "promises": {
      "total": 85,
      "kept": 68,
      "broken": 12,
      "fulfillment_rate": 80.0
    },
    "officer_performance": [
      {
        "officer_id": 5,
        "officer_name": "John Doe",
        "actions": 52
      },
      {
        "officer_id": 8,
        "officer_name": "Jane Smith",
        "actions": 48
      }
    ],
    "queue_metrics": {
      // ... same as executive dashboard collections section
    }
  }
}
```

#### Monthly KPIs
```http
GET /api/v1/dashboard/{institutionId}/monthly-kpis?year=2026&month=2
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Monthly KPI data retrieved successfully",
  "data": {
    "period": "February 2026",
    "applications": {
      "total": 75,
      "approved": 52,
      "declined": 18,
      "pending": 5,
      "approval_rate": 74.29,
      "approved_amount": 15600000
    },
    "disbursements": {
      "count": 48,
      "amount": 14400000,
      "avg_loan_size": 300000
    },
    "collections": {
      "count": 132,
      "amount": 3200000,
      "principal": 2400000,
      "interest": 720000,
      "penalties": 80000
    },
    "portfolio": {
      // ... portfolio metrics snapshot
    }
  }
}
```

## Data Structures

### Executive Dashboard Structure
```php
[
    'applications' => [
        'total' => int,
        'approved' => int,
        'declined' => int,
        'pending' => int,
        'approval_rate' => float,
        'approved_amount' => float
    ],
    'portfolio' => [
        'total_loans' => int,
        'active_loans' => int,
        'total_disbursed' => float,
        'total_outstanding' => float,
        'total_collected' => float,
        'total_arrears' => float,
        'loans_in_arrears' => int,
        'npl_count' => int,
        'npl_amount' => float,
        'npl_ratio' => float,
        'par30_count' => int,
        'par30_amount' => float,
        'par30_ratio' => float,
        'avg_loan_size' => float
    ],
    'collections' => [
        'total_in_queue' => int,
        'critical_items' => int,
        'high_priority_items' => int,
        'total_arrears_in_queue' => float,
        'avg_days_past_due' => float,
        'active_ptps' => int,
        'due_ptps' => int
    ],
    'trends' => [
        [
            'month' => string, // Y-m format
            'month_name' => string, // M Y format
            'applications' => int,
            'approved_applications' => int,
            'disbursements' => float,
            'collections' => float,
            'portfolio_outstanding' => float,
            'par30_ratio' => float,
            'npl_ratio' => float
        ],
        // ... 12 months
    ],
    'generated_at' => Carbon
]
```

## Error Handling

All endpoints follow consistent error response format:

### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "year": ["The year field is required."],
    "month": ["The month must be between 1 and 12."]
  }
}
```

### Server Error (500)
```json
{
  "message": "Failed to generate eligibility report",
  "error": "Application not found for institution ID 1"
}
```

### Authentication Error (401)
```json
{
  "message": "Unauthenticated."
}
```

### Permission Error (403)
```json
{
  "message": "Unauthorized action."
}
```

## Dependencies

### Laravel Packages
- **barryvdh/laravel-dompdf**: ^3.0 (PDF generation)
- **maatwebsite/excel**: ^3.1 (Excel import/export)
- **laravel/sanctum**: ^4.0 (API authentication)

### Models Used
- **Application**: Loan applications with eligibility assessments
- **Loan**: Active and closed loans portfolio
- **Repayment**: Payment history and tracking
- **CollectionsQueue**: Collections queue items
- **CollectionsAction**: Collections actions log
- **PromiseToPay**: Promise to pay records
- **PortfolioSnapshot**: Monthly/historical portfolio snapshots
- **BankStatementImport**: Bank statement imports
- **BankStatementAnalytics**: Statement analytics data

## Storage Structure

```
storage/
├── app/
│   ├── reports/
│   │   ├── eligibility-report-{id}-{timestamp}.pdf
│   │   ├── bank-statement-report-{id}-{timestamp}.pdf
│   │   ├── application-summary-{id}-{timestamp}.pdf
│   │   ├── affordability-report-{id}-{timestamp}.pdf
│   │   └── portfolio-pack-{year}-{month}-{timestamp}.pdf
│   │
│   └── exports/
│       ├── applications-export-{timestamp}.xlsx
│       ├── loans-export-{timestamp}.xlsx
│       ├── repayments-export-{timestamp}.xlsx
│       ├── collections-queue-export-{timestamp}.xlsx
│       └── portfolio-summary-export-{timestamp}.xlsx
```

**Note:** Files are automatically deleted after download (via `deleteFileAfterSend(true)`)

## Permission Matrix

| Action | Permission Required | Roles Allowed |
|--------|-------------------|---------------|
| Generate PDF Reports | `reports.generate` | Institution Admin, Provider Super Admin |
| View Analytics | `reports.view` | All authenticated users with `applications.view` |
| Export to Excel | `reports.export` | Institution Admin, Provider Super Admin |
| View Dashboards | `applications.view` | All authenticated users |

## Testing Recommendations

### Unit Tests

1. **ReportService Tests**
   - Test each report generation method with mock data
   - Verify PDF file creation
   - Test institution branding inclusion
   - Test missing data handling

2. **ExportService Tests**
   - Test each export method with sample data
   - Verify Excel file creation
   - Test filters (status, date range)
   - Test empty dataset handling

3. **DashboardService Tests**
   - Test metric calculations
   - Test trend aggregation
   - Test date range filtering
   - Test missing portfolio snapshots

### Integration Tests

1. **API Endpoint Tests**
   - Test authentication requirements
   - Test permission checks
   - Test validation rules
   - Test file download responses
   - Test JSON response structure

2. **End-to-End Tests**
   - Generate reports for real applications
   - Export data and verify Excel content
   - Test dashboard with complete data set

## Performance Considerations

### Optimization Strategies

1. **Eager Loading**
   - All export methods use `with()` to eager load relationships
   - Prevents N+1 query problems
   - Example: `$applications->with(['customer', 'loanProduct', 'underwritingDecision'])`

2. **Chunking for Large Datasets**
   - Consider using `chunk()` for exports with >10,000 records
   ```php
   Loan::where('institution_id', $institutionId)
       ->chunk(1000, function($loans) {
           // Process chunk
       });
   ```

3. **Caching Dashboard Data**
   - Executive dashboard calculates 12 months of trends
   - Consider caching for 5-10 minutes:
   ```php
   Cache::remember("dashboard-{$institutionId}", 600, function() {
       return $this->dashboardService->getExecutiveDashboard($institutionId);
   });
   ```

4. **Background Job Processing**
   - For large reports/exports, dispatch to queue:
   ```php
   GeneratePortfolioPackJob::dispatch($institutionId, $year, $month, $userId);
   ```

5. **Database Indexing**
   - Ensure indexes on commonly filtered columns:
     * `applications.institution_id, applications.created_at`
     * `loans.institution_id, loans.status`
     * `repayments.institution_id, repayments.payment_date`
     * `collections_queue.institution_id, collections_queue.status`

## Future Enhancements

### Phase 11.1: Advanced Analytics
- [ ] Predictive analytics for default probability
- [ ] Customer segmentation analysis
- [ ] Product performance comparison
- [ ] Seasonality trend detection

### Phase 11.2: Scheduled Reports
- [ ] Configure automated report generation (daily/weekly/monthly)
- [ ] Email delivery of reports to stakeholders
- [ ] Report scheduling UI
- [ ] Report history and archiving

### Phase 11.3: Interactive Dashboards
- [ ] Real-time WebSocket updates for dashboard metrics
- [ ] Drill-down capabilities (click portfolio metric → detailed aging breakdown)
- [ ] Custom dashboard builder for users
- [ ] Chart/graph visualization options

### Phase 11.4: Regulatory Reports
- [ ] Central Bank reporting templates
- [ ] Provisioning calculation reports
- [ ] Capital adequacy reports
- [ ] Compliance audit trails

### Phase 11.5: Data Warehouse Integration
- [ ] ETL pipeline for historical data
- [ ] Data warehouse schema design
- [ ] OLAP cube for multi-dimensional analysis
- [ ] Business Intelligence tool integration (Power BI, Tableau)

## Usage Examples

### Example 1: Generate Application Summary for Approved Loan
```php
// In your controller or service
use App\Services\ReportService;

$reportService = app(ReportService::class);

// Generate PDF report for application ID 123
$filePath = $reportService->generateApplicationSummaryReport(
    institutionId: 1,
    applicationId: 123
);

// Return as download
return response()->download($filePath, basename($filePath), [
    'Content-Type' => 'application/pdf'
])->deleteFileAfterSend(true);
```

### Example 2: Export Last Month's Repayments
```php
use App\Services\ExportService;

$exportService = app(ExportService::class);

$startDate = now()->subMonth()->startOfMonth();
$endDate = now()->subMonth()->endOfMonth();

$filePath = $exportService->exportRepayments(
    institutionId: 1,
    startDate: $startDate,
    endDate: $endDate
);

return response()->download($filePath, 'repayments-last-month.xlsx')
    ->deleteFileAfterSend(true);
```

### Example 3: Display Executive Dashboard in Frontend
```javascript
// Fetch executive dashboard data
const response = await fetch('/api/v1/dashboard/1/executive', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const { data } = await response.json();

// Display applications metrics
console.log(`Approval Rate: ${data.applications.approval_rate}%`);
console.log(`NPL Ratio: ${data.portfolio.npl_ratio}%`);
console.log(`Collections Queue: ${data.collections.total_in_queue} items`);

// Render 12-month trends chart
renderChart(data.trends);
```

## Configuration

### DomPDF Options
Configure in `config/dompdf.php`:
```php
'options' => [
    'default_font' => 'sans-serif',
    'enable_remote' => true, // For external images (logos)
    'enable_html5_parser' => true,
    'isPhpEnabled' => false,
],
```

### Excel Export Settings
Configure in `config/excel.php`:
```php
'exports' => [
    'chunk_size' => 1000,
    'pre_calculate_formulas' => false,
    'strict_null_comparison' => false,
],
```

## Troubleshooting

### Issue: PDF Generation Fails
**Symptoms:** 500 error when generating PDF reports

**Solutions:**
1. Check DomPDF installation: `composer require barryvdh/laravel-dompdf`
2. Publish DomPDF config: `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"`
3. Ensure storage directory is writable: `chmod -R 775 storage/app/reports`
4. Check view files exist in `resources/views/reports/`

### Issue: Excel Export Fails
**Symptoms:** 500 error when exporting to Excel

**Solutions:**
1. Check Maatwebsite/Excel installation: `composer require maatwebsite/excel`
2. Publish Excel config: `php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"`
3. Ensure storage directory is writable: `chmod -R 775 storage/app/exports`
4. Check PHP memory limit for large exports: `ini_set('memory_limit', '512M')`

### Issue: Dashboard Data is Slow
**Symptoms:** Dashboard API takes >3 seconds to respond

**Solutions:**
1. Add database indexes (see Performance Considerations)
2. Implement caching for dashboard metrics
3. Use `select()` to limit columns retrieved
4. Consider moving trend calculation to background job

### Issue: Permission Denied on Reports
**Symptoms:** 403 Unauthorized when accessing reports

**Solutions:**
1. Verify user has required permission: `reports.generate`, `reports.view`, or `reports.export`
2. Check institution ID matches user's institution
3. Verify middleware configuration in routes/api.php

## Summary

Phase 11 successfully implements a comprehensive reporting and analytics module with:
- ✅ 3 service classes (ReportService, ExportService, DashboardService)
- ✅ 2 export utility classes (GenericExport, MultiSheetExport)
- ✅ 1 controller with 19 API endpoints
- ✅ PDF report generation (5 report types)
- ✅ Excel data exports (5 export types)
- ✅ JSON analytics (3 analytics types)
- ✅ Dashboard APIs (5 dashboard views)
- ✅ Permission-based access control
- ✅ Multi-tenant institution scoping
- ✅ Comprehensive error handling

The reporting module provides stakeholders with actionable insights across all system modules, enabling data-driven decision making for loan origination, portfolio management, and collections optimization.

---

**Next Phase:** Phase 12 - Audit Logging & Activity Tracking
