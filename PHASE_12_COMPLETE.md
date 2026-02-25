# Phase 12: Audit Logging & Activity Tracking - COMPLETE

## Overview
Phase 12 implements a comprehensive audit logging system for tracking all user actions, data modifications, and security events across the mortgage platform. This provides full compliance support, security monitoring, and forensic investigation capabilities.

## Completion Date
**Completed:** February 24, 2026

## Components Implemented

### 1. Database Schema

#### Audit Logs Table
**Migration:** `database/migrations/2026_02_24_090129_create_audit_logs_table.php`

**Schema:**
- **Institution & User Tracking:**
  - `institution_id` - Institution context
  - `user_id` - User who performed action
  - `user_name` - User name snapshot (retained if user deleted)
  - `user_role` - User's role at time of action

- **Event Information:**
  - `event_type` - Category: authentication, authorization, data_modification, decision, import, configuration, file_access, api_request
  - `event_category` - Specific event: login, application_created, decision_made, etc.
  - `action` - Action performed: create, update, delete, approve, decline, etc.
  - `description` - Human-readable event description

- **Entity Tracking:**
  - `entity_type` - Affected entity type (Application, Loan, Customer, etc.)
  - `entity_id` - ID of affected entity

- **Request Information:**
  - `http_method` - GET, POST, PUT, DELETE
  - `request_url` - Full request URL
  - `request_body` - Request payload (sanitized, JSON)
  - `response_status` - HTTP status code

- **Session Information:**
  - `ip_address` - Client IP address
  - `user_agent` - Browser/client user agent
  - `session_id` - Session identifier

- **Change Tracking:**
  - `old_values` - Before state (JSON)
  - `new_values` - After state (JSON)
  - `metadata` - Additional contextual data (JSON)

- **Risk & Compliance:**
  - `is_critical` - Flag for critical events (deletes, overrides, access denied)
  - `is_sensitive` - Contains sensitive data (PII, financial)
  - `severity` - low, medium, high, critical

**Indexes:**
- `institution_id`, `user_id`, `event_type`, `entity_type`, `entity_id`
- Composite: `(institution_id, event_type, created_at)`, `(user_id, created_at)`, `(entity_type, entity_id)`

### 2. Model Layer

#### AuditLog Model
**File:** `app/Models/AuditLog.php` (210 lines)

**Relationships:**
- `institution()` - BelongsTo Institution
- `user()` - BelongsTo User

**Query Scopes (9):**
1. `scopeEventType($query, $eventType)` - Filter by event type
2. `scopeEventCategory($query, $category)` - Filter by category
3. `scopeForEntity($query, $entityType, $entityId)` - Filter by entity
4. `scopeForInstitution($query, $institutionId)` - Filter by institution
5. `scopeByUser($query, $userId)` - Filter by user
6. `scopeCritical($query)` - Critical events only
7. `scopeDateRange($query, $startDate, $endDate)` - Date filtering
8. `scopeSeverity($query, $severity)` - Filter by severity level

**Key Methods:**
- `getChanges()` - Returns array of field changes (old vs new values)
- `hasSensitiveData()` - Check if log contains sensitive data
- `getEntityTimeline($entityType, $entityId, $limit)` - Get entity's audit history
- `getUserActivity($userId, $days)` - User activity summary statistics
- `getInstitutionActivity($institutionId, $days)` - Institution activity summary

### 3. Service Layer

#### AuditService
**File:** `app/Services/AuditService.php` (370 lines)

**Core Methods:**

1. **log($data)** - Generic audit log creation
   - Auto-fills authenticated user information
   - Auto-fills institution context
   - Returns created AuditLog instance

2. **logAuthentication($action, $description, $metadata)** - Authentication events
   - Events: login, logout, login_failed, password_reset, account_locked
   - Auto-flags critical events (failed logins, resets)
   - Example: Login attempts, password changes, MFA events

3. **logAuthorization($action, $resource, $description, $metadata)** - Authorization failures
   - Events: access_denied, permission_denied
   - Always flagged as critical
   - Example: Unauthorized access attempts

4. **logDataModification($action, $entityType, $entityId, $oldValues, $newValues, $description)** - Data changes
   - Events: create, update, delete, restore
   - Tracks before/after state
   - Auto-flags delete operations as critical
   - Example: Application updates, customer edits

5. **logDecision($action, $entityType, $entityId, $decision, $reason, $metadata)** - Approval/decline events
   - Events: approve, decline, override, review
   - Always high severity
   - Example: Loan approvals, underwriting decisions

6. **logImport($importType, $filename, $recordsProcessed, $recordsFailed, $metadata)** - File uploads
   - Events: bank_statement_import, repayment_import, kyc_upload
   - Flags as critical if records failed
   - Example: Bank statement CSV uploads

7. **logConfigurationChange($configType, $oldValues, $newValues, $description)** - System config changes
   - Events: loan_product_update, institution_settings_change
   - Always critical and high severity
   - Example: Interest rate changes, policy updates

8. **logFileAccess($action, $filename, $entityType, $entityId, $metadata)** - Document access
   - Events: view, download, upload, delete
   - Always marked as sensitive
   - Example: KYC document downloads

9. **logRequest($request, $responseStatus)** - HTTP request logging
   - Sanitizes request body (removes passwords, tokens)
   - Captures IP, user agent, session ID
   - Flags sensitive endpoints
   - Example: API calls to sensitive resources

**Helper Methods:**
- `isSensitiveEntity($entityType)` - Determines if entity contains PII
- `determineSeverity($eventType, $action)` - Auto-assigns severity level
- `getActionFromRequest($request)` - Maps HTTP method to action
- `sanitizeRequestBody($request)` - Removes sensitive fields
- `isRequestSensitive($request)` - Checks if endpoint is sensitive
- `getStatistics($institutionId, $days)` - Institution audit stats
- `getUserActivitySummary($userId, $days)` - User activity summary
- `exportLogs($filters)` - Filtered audit log export

### 4. Middleware

#### AuditMiddleware
**File:** `app/Http/Middleware/AuditMiddleware.php` (110 lines)

**Purpose:** Automatically log HTTP requests to API endpoints

**Configuration:**
- **Excluded Paths:** audit-logs, sanctum, health, _debugbar
- **Excluded Methods:** Optionally exclude GET requests

**Logging Rules:**
1. **Always log:** POST, PUT, PATCH, DELETE requests
2. **Always log:** Failed requests (4xx, 5xx status codes)
3. **Conditionally log:** GET requests to sensitive endpoints (KYC, bank statements, customers)

**Features:**
- Non-blocking (continues if audit logging fails)
- Request/response correlation
- Fails silently with error logging

**Usage:** Apply to routes in `bootstrap/app.php` or route groups

### 5. Controller Layer

#### AuditLogController
**File:** `app/Http/Controllers/AuditLogController.php` (380 lines)

**Endpoints (9 total):**

1. **GET /audit-logs/{institutionId}** - List audit logs with filters
   - Filters: event_type, event_category, action, user_id, entity_type/id, severity, is_critical, date range
   - Pagination: 10-100 per page (default 50)
   - Returns: Paginated logs with user & institution relationships

2. **GET /audit-logs/{institutionId}/{logId}** - Get single log details
   - Returns: Full audit log with relationships
   - Status 404 if not found

3. **GET /audit-logs/{institutionId}/entity/{entityType}/{entityId}** - Entity audit trail
   - Returns: Timeline of events for specific entity
   - Limit: 10-500 records (default 50)
   - Ordered by most recent first

4. **GET /audit-logs/{institutionId}/user/{userId}/activity** - User activity summary
   - Query: `days` (1-365, default 30), `include_logs` (boolean)
   - Returns: Activity statistics + optional recent logs
   - Includes: total actions, by event type, by action, by entity type

5. **GET /audit-logs/{institutionId}/statistics** - Institution audit statistics
   - Query: `days` (1-365, default 30)
   - Returns: Total actions, unique users, event type breakdown, severity breakdown

6. **POST /audit-logs/{institutionId}/export** - Export audit logs to Excel
   - Filters: event_type, user_id, entity_type/id, severity, is_critical, date range, limit
   - Returns: Excel file download
   - Columns: ID, Date/Time, User, Role, Event Type, Category, Action, Description, Entity, HTTP Method, IP, Severity, Critical, Sensitive

7. **GET /audit-logs/{institutionId}/critical-events** - Get critical events
   - Query: `days` (1-90, default 7), `per_page`
   - Returns: Paginated critical events only

8. **GET /audit-logs/{institutionId}/timeline** - Event timeline for dashboard
   - Query: `hours` (1-168, default 24), `event_types[]`
   - Returns: Grouped by hour with counts and event type breakdown

### 6. Routes Configuration

**File:** `routes/api.php`

**Route Prefix:** `/api/v1/audit-logs/{institutionId}`

**Middleware:**
- `auth:sanctum` - Authentication required
- `permission:applications.view` - Basic permission for audit log viewing
- `permission:reports.export` - Export permission for Excel exports

**Routes:**
```php
GET    /audit-logs/{institutionId}                              - List logs
GET    /audit-logs/{institutionId}/{logId}                      - Log details
GET    /audit-logs/{institutionId}/entity/{entityType}/{entityId} - Entity trail
GET    /audit-logs/{institutionId}/user/{userId}/activity       - User activity
GET    /audit-logs/{institutionId}/statistics                   - Statistics
GET    /audit-logs/{institutionId}/critical-events              - Critical events
GET    /audit-logs/{institutionId}/timeline                     - Timeline
POST   /audit-logs/{institutionId}/export                       - Export Excel
```

## Integration Examples

### Example 1: Log Application Creation

```php
use App\Services\AuditService;

$auditService = app(AuditService::class);

// When creating an application
$application = Application::create($data);

$auditService->logDataModification(
    action: 'create',
    entityType: 'Application',
    entityId: $application->id,
    oldValues: null,
    newValues: $application->toArray(),
    description: "New loan application created for {$customer->name}"
);
```

### Example 2: Log Approval Decision

```php
// When approving a loan application
$decision = UnderwritingDecision::find($id);
$decision->update(['final_decision' => 'approved', 'approved_amount' => $amount]);

$auditService->logDecision(
    action: 'approve',
    entityType: 'Application',
    entityId: $application->id,
    decision: 'approved',
    reason: $request->input('approval_notes'),
    metadata: [
        'approved_amount' => $amount,
        'decision_maker' => auth()->user()->name,
        'approval_conditions' => $conditions
    ]
);
```

### Example 3: Log Failed Login

```php
// In AuthController login method
if (!Auth::attempt($credentials)) {
    $auditService->logAuthentication(
        action: 'login_failed',
        description: "Failed login attempt for email: {$email}",
        metadata: [
            'email' => $email,
            'ip_address' => request()->ip()
        ]
    );
    
    return response()->json(['message' => 'Invalid credentials'], 401);
}
```

### Example 4: Log KYC Document Download

```php
// When downloading a KYC document
$document = KycDocument::findOrFail($id);

$auditService->logFileAccess(
    action: 'download',
    filename: $document->file_name,
    entityType: 'KycDocument',
    entityId: $document->id,
    metadata: [
        'customer_id' => $document->customer_id,
        'document_type' => $document->document_type,
        'file_size' => $document->file_size
    ]
);

return response()->download($document->file_path);
```

### Example 5: Log Configuration Change

```php
// When updating loan product interest rate
$product = LoanProduct::find($id);
$oldValues = $product->only(['interest_rate', 'interest_method']);

$product->update(['interest_rate' => $newRate]);

$auditService->logConfigurationChange(
    configType: 'LoanProduct',
    oldValues: $oldValues,
    newValues: $product->fresh()->only(['interest_rate', 'interest_method']),
    description: "Interest rate changed for {$product->name}"
);
```

## API Documentation

### Get Audit Logs (Filtered)

```http
GET /api/v1/audit-logs/{institutionId}?event_type=data_modification&start_date=2026-02-01&end_date=2026-02-24&per_page=50
Authorization: Bearer {token}
```

**Query Parameters:**
- `event_type` (optional) - authentication, authorization, data_modification, decision, import, configuration, file_access, api_request
- `event_category` (optional) - Specific category (e.g., application_created)
- `action` (optional) - create, update, delete, approve, decline, etc.
- `user_id` (optional) - Filter by specific user
- `entity_type` (optional) - Application, Loan, Customer, etc.
- `entity_id` (optional) - Specific entity ID (requires entity_type)
- `severity` (optional) - low, medium, high, critical
- `is_critical` (optional) - true/false
- `start_date` (optional) - YYYY-MM-DD
- `end_date` (optional) - YYYY-MM-DD
- `per_page` (optional) - 10-100 (default 50)

**Response:**
```json
{
  "message": "Audit logs retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1234,
        "institution_id": 1,
        "user_id": 5,
        "user_name": "John Doe",
        "user_role": "credit-officer",
        "event_type": "decision",
        "event_category": "application_approve",
        "action": "approve",
        "description": "Approved Application (ID: 123): approved - Meets all credit criteria",
        "entity_type": "Application",
        "entity_id": "123",
        "http_method": null,
        "request_url": null,
        "ip_address": "192.168.1.100",
        "user_agent": "Mozilla/5.0...",
        "old_values": null,
        "new_values": null,
        "metadata": {
          "decision": "approved",
          "reason": "Meets all credit criteria",
          "approved_amount": 5000000
        },
        "is_critical": true,
        "is_sensitive": false,
        "severity": "high",
        "created_at": "2026-02-24T10:30:00.000000Z",
        "user": {
          "id": 5,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "institution": {
          "id": 1,
          "name": "Demo MFI"
        }
      }
    ],
    "total": 1250,
    "per_page": 50,
    "last_page": 25
  }
}
```

### Get Entity Audit Trail

```http
GET /api/v1/audit-logs/{institutionId}/entity/Application/123?limit=50
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Entity audit trail retrieved successfully",
  "data": {
    "entity_type": "Application",
    "entity_id": "123",
    "total_events": 12,
    "logs": [
      {
        "id": 1240,
        "action": "approve",
        "description": "Approved application",
        "user_name": "Jane Smith",
        "created_at": "2026-02-24T14:20:00.000000Z",
        "user": {...}
      },
      {
        "id": 1235,
        "action": "update",
        "description": "Updated application: Risk grade changed B → A",
        "user_name": "John Doe",
        "created_at": "2026-02-24T10:15:00.000000Z",
        "user": {...}
      },
      {
        "id": 1220,
        "action": "create",
        "description": "New loan application created",
        "user_name": "John Doe",
        "created_at": "2026-02-23T09:00:00.000000Z",
        "user": {...}
      }
    ]
  }
}
```

### Get User Activity

```http
GET /api/v1/audit-logs/{institutionId}/user/5/activity?days=30&include_logs=true
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "User activity retrieved successfully",
  "data": {
    "user_id": 5,
    "summary": {
      "total_actions": 245,
      "by_event_type": {
        "data_modification": 120,
        "decision": 45,
        "api_request": 65,
        "file_access": 15
      },
      "by_action": {
        "create": 50,
        "update": 70,
        "approve": 30,
        "view": 65,
        "download": 15,
        "decline": 15
      },
      "by_entity_type": {
        "Application": 95,
        "Loan": 35,
        "Customer": 40,
        "KycDocument": 15
      },
      "critical_events": 45,
      "date_range": {
        "start": "2026-01-25",
        "end": "2026-02-24"
      }
    },
    "recent_logs": [...]
  }
}
```

### Get Statistics

```http
GET /api/v1/audit-logs/{institutionId}/statistics?days=30
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Audit statistics retrieved successfully",
  "data": {
    "total_actions": 1850,
    "unique_users": 15,
    "by_event_type": {
      "data_modification": 820,
      "api_request": 580,
      "decision": 185,
      "authentication": 145,
      "file_access": 90,
      "import": 25,
      "authorization": 5
    },
    "by_severity": {
      "low": 900,
      "medium": 550,
      "high": 350,
      "critical": 50
    },
    "critical_events": 185,
    "sensitive_events": 420,
    "date_range": {
      "start": "2026-01-25",
      "end": "2026-02-24"
    }
  }
}
```

### Export Audit Logs

```http
POST /api/v1/audit-logs/{institutionId}/export
Authorization: Bearer {token}
Content-Type: application/json

{
  "event_type": "decision",
  "severity": "high",
  "start_date": "2026-02-01",
  "end_date": "2026-02-24",
  "limit": 5000
}
```

**Response:**
- Type: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- Disposition: `attachment; filename="audit-logs-export-2026-02-24-143015.xlsx"`

**Excel Columns (15):**
1. ID
2. Date/Time
3. User
4. User Role
5. Event Type
6. Event Category
7. Action
8. Description
9. Entity Type
10. Entity ID
11. HTTP Method
12. IP Address
13. Severity
14. Critical (Yes/No)
15. Sensitive (Yes/No)

## Event Types & Categories

### Authentication Events
- **login** - Successful user login
- **logout** - User logout
- **login_failed** - Failed login attempt
- **password_reset** - Password reset request
- **password_changed** - Password successfully changed
- **account_locked** - Account locked due to failed attempts
- **mfa_enabled** - Two-factor authentication enabled
- **mfa_disabled** - Two-factor authentication disabled

### Authorization Events
- **access_denied** - Unauthorized access attempt
- **permission_denied** - Insufficient permissions for action

### Data Modification Events
- **application_created** - New loan application
- **application_updated** - Application edited
- **application_deleted** - Application removed
- **customer_created** - New customer added
- **customer_updated** - Customer information edited
- **loan_created** - New loan disbursed
- **loan_updated** - Loan details modified
- **user_created** - New user account
- **user_updated** - User details modified
- **product_created** - New loan product
- **product_updated** - Loan product configuration changed

### Decision Events
- **application_approve** - Loan application approved
- **application_decline** - Loan application declined
- **underwriting_review** - Credit review completed
- **override_request** - Override requested
- **override_approve** - Override approved
- **override_decline** - Override declined

### Import Events
- **bank_statement_import** - Bank statement CSV uploaded
- **repayment_import** - Repayment file uploaded
- **kyc_upload** - KYC document uploaded

### Configuration Events
- **config_change** - System configuration modified
- **institution_settings_change** - Institution settings updated
- **loan_product_config** - Loan product parameters changed
- **branding_change** - Institution branding updated

### File Access Events
- **file_view** - Document viewed
- **file_download** - Document downloaded
- **file_upload** - File uploaded
- **file_delete** - Document deleted

### API Request Events
- **http_request** - Generic API request logged

## Security Features

### 1. Sensitive Data Protection
- **PII Redaction:** Passwords, tokens, API keys automatically redacted from logs
- **Sensitive Flag:** Documents, bank statements, customer data marked as sensitive
- **Access Control:** Only authorized users can view audit logs

### 2. Immutable Logs
- Audit logs table has no update/delete functionality
- Once created, logs cannot be modified (append-only)
- Foreign key to users set to `onDelete('set null')` to preserve audit trail

### 3. Critical Event Flagging
- Deletes, overrides, failed logins automatically flagged
- Critical events filterable and reportable
- Severity levels for risk assessment

### 4. IP & Session Tracking
- IP address captured for all actions
- User agent logged for forensic analysis
- Session ID for correlation

## Compliance Support

### 1. Regulatory Requirements
- **BOT (Bank of Tanzania):** Financial institution activity tracking
- **GDPR/Data Protection:** User action accountability
- **SOX Compliance:** Financial data change tracking
- **ISO 27001:** Security event logging

### 2. Audit Reports
- Full export capability (Excel)
- Date range filtering
- Entity-specific audit trails
- User activity summaries

### 3. Data Retention
- Configure retention policy via scheduled cleanup job (future enhancement)
- Archive old logs to separate storage (future enhancement)
- Maintain compliance with regulatory retention periods

## Performance Considerations

### 1. Database Optimization
- **Indexes:** 7 indexes for common query patterns
- **Composite Indexes:** Institution + event type + date
- **Partitioning:** Consider table partitioning by date for large institutions (future)

### 2. Query Performance
- Pagination limits (10-100 per page)
- Export limits (max 10,000 records)
- Date range requirements for large queries

### 3. Storage Management
- JSON columns for flexible metadata
- Request body truncated at 10KB
- Consider archival strategy after 1-2 years

### 4. Async Logging
- Audit middleware fails silently (non-blocking)
- Consider queue-based logging for high-traffic endpoints (future)

## Best Practices

### 1. What to Log
✅ **Always Log:**
- Authentication events (login, logout, failures)
- Authorization failures (access denied)
- Data modifications (create, update, delete)
- Critical decisions (approve, decline, override)
- Configuration changes
- Sensitive file access

❌ **Don't Log:**
- High-frequency read operations (excessive GET requests)
- Health check endpoints
- Static asset requests

### 2. Logging Patterns

**Controller Actions:**
```php
public function approve(Request $request, $id)
{
    $application = Application::findOrFail($id);
    
    // Perform action
    $application->update(['status' => 'approved']);
    
    // Log the action
    app(AuditService::class)->logDecision(
        action: 'approve',
        entityType: 'Application',
        entityId: $application->id,
        decision: 'approved',
        reason: $request->input('notes')
    );
    
    return response()->json(['message' => 'Application approved']);
}
```

**Model Events (Alternative):**
```php
// In Application model boot method
protected static function boot()
{
    parent::boot();
    
    static::created(function ($application) {
        app(AuditService::class)->logDataModification(
            action: 'create',
            entityType: 'Application',
            entityId: $application->id,
            newValues: $application->toArray()
        );
    });
}
```

### 3. Metadata Best Practices
- Include context-specific information
- Keep metadata structured (use arrays/objects)
- Don't duplicate data already in standard fields
- Include IDs of related entities

## Testing Recommendations

### Unit Tests
1. **AuditLog Model:**
   - Test scopes (eventType, forEntity, critical, etc.)
   - Test getChanges() method
   - Test activity summaries

2. **AuditService:**
   - Test each log method
   - Test sanitization (password redaction)
   - Test severity assignment
   - Test sensitive entity detection

3. **AuditMiddleware:**
   - Test shouldSkip logic
   - Test shouldLog logic
   - Test non-blocking behavior

### Integration Tests
1. **API Endpoints:**
   - Test filtering combinations
   - Test pagination
   - Test export functionality
   - Test timeline aggregation

2. **End-to-End:**
   - Perform actions and verify logs created
   - Test entity audit trail accuracy
   - Test user activity summary calculations

## Enhancements for Phase 12.1+

### 1. Real-time Monitoring
- [ ] WebSocket integration for live audit stream
- [ ] Dashboard widget for recent critical events
- [ ] Alerting for suspicious activity patterns

### 2. Advanced Analytics
- [ ] Anomaly detection (unusual user behavior)
- [ ] Failed login attempt tracking and lockout
- [ ] Access pattern analysis
- [ ] Compliance report templates

### 3. Data Archival
- [ ] Automated archival job (move logs older than 2 years)
- [ ] Compressed archive storage
- [ ] Archive search capability

### 4. Enhanced Reporting
- [ ] Scheduled audit report emails
- [ ] PDF audit reports
- [ ] Compliance certification reports
- [ ] User activity heatmaps

### 5. Log Integrity
- [ ] Cryptographic hashing of log entries
- [ ] Blockchain-based log integrity verification
- [ ] Tamper detection

## Summary

Phase 12 successfully implements a comprehensive audit logging system with:
- ✅ Audit logs migration with 30+ fields
- ✅ AuditLog model with 9 query scopes + activity summaries
- ✅ AuditService with 11 logging methods
- ✅ AuditMiddleware for automatic HTTP request logging
- ✅ AuditLogController with 9 API endpoints
- ✅ Routes with permission-based access control
- ✅ Sensitive data redaction
- ✅ Critical event flagging
- ✅ Multi-tenant institution scoping
- ✅ Excel export capability
- ✅ Entity audit trails
- ✅ User activity summaries
- ✅ Statistical analysis
- ✅ Timeline visualization support

The audit system provides full visibility into all platform activities, supporting compliance, security monitoring, and forensic investigation requirements.

---

**Next Phase:** Phase 13 - Frontend/UI Development (React/Vue.js dashboard and forms)
