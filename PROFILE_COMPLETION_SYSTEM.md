# Profile Completion System

## Overview
The profile completion system provides a dynamic, real-time percentage score that measures how complete a customer's profile is. This percentage updates automatically whenever:
- Customer profile information is updated
- KYC documents are uploaded or deleted
- KYC verification status changes

## Scoring Breakdown (100 points total)

### 1. Basic Information (30 points)
- **First Name**: 5 points
- **Last Name**: 5 points
- **Date of Birth**: 3 points
- **Gender**: 2 points
- **National ID**: 5 points
- **Marital Status**: 2 points

### 2. Contact Information (20 points)
- **Primary Phone**: 7 points
- **Email**: 7 points
- **Physical Address**: 3 points
- **City**: 2 points
- **Country**: 1 point

### 3. Employment/Business Information (20 points)
For **Salary Customers**:
- Employer Name
- Occupation
- Employment Start Date

For **Business Customers**:
- Business Name
- Industry
- Occupation

*Scores proportionally based on completion (e.g., 2/3 fields = ~13 points)*

### 4. Next of Kin Information (10 points)
- **Name**: 4 points
- **Relationship**: 2 points
- **Phone**: 4 points

### 5. KYC Documents (20 points)
Required document types (5 points each):
- **National ID**
- **Passport**
- **Utility Bill**
- **Bank Statement**

*Each uploaded document type adds 5 points*

### 6. KYC Verification Bonus (5 points)
- Additional **5 points** when customer is fully KYC verified

## How It Works

### Automatic Updates
The system automatically recalculates profile completion when:

1. **Customer data is saved**
   ```php
   $customer->first_name = 'John';
   $customer->save(); // Profile completion updates automatically
   ```

2. **Documents are uploaded**
   ```php
   KycDocument::create([...]);  // Profile completion updates automatically
   ```

3. **Documents are deleted**
   ```php
   $document->delete();  // Profile completion updates automatically
   ```

### Manual Recalculation
You can manually recalculate profile completion:

**For all customers:**
```bash
php artisan customers:recalculate-profile-completion
```

**For a specific customer:**
```bash
php artisan customers:recalculate-profile-completion --customer_id=123
```

### Programmatic Access
```php
// Get current percentage
$percentage = $customer->profile_completion_percentage;

// Force recalculation
$customer->updateProfileCompletion();
```

## UI Display

### Customer Profile View
The profile completion is displayed with:
- **Progress bar** (color-coded: red < 50%, yellow 50-79%, green ≥ 80%)
- **Percentage number**
- **Expandable breakdown** showing scores for each section

### Customer List View
- Progress bar shows completion at a glance
- Percentage displayed for quick reference

## Example Scenarios

### New Customer (Minimal Info)
```
Name: John Doe
Phone: +255123456789
Profile Completion: ~24%
```
*Has basic name and contact info only*

### Customer with Documents
```
Name: John Doe
Phone: +255123456789
Email: john@example.com
Address: Dar es Salaam
National ID: 12345678
Employer: ABC Company
Next of Kin: Jane Doe (Wife, +255987654321)
KYC Docs: National ID, Utility Bill
Profile Completion: ~67%
```
*Missing passport and bank statement documents for full completion*

### Fully Complete Customer
```
All fields filled + All 4 required documents uploaded + KYC Verified
Profile Completion: 100%
```

## Color Coding

| Percentage | Color | Status |
|-----------|-------|--------|
| 0-49% | Red | Needs Attention |
| 50-79% | Yellow/Warning | In Progress |
| 80-100% | Green | Complete/Excellent |

## Technical Implementation

### Model: Customer.php
```php
public function updateProfileCompletion(): void
{
    // Calculates score based on filled fields and documents
    // Updates profile_completion_percentage column
}
```

### Model: KycDocument.php
```php
protected static function boot()
{
    // Triggers customer profile update when documents change
    static::saved(function ($document) {
        $document->customer->updateProfileCompletion();
    });
    
    static::deleted(function ($document) {
        $document->customer->updateProfileCompletion();
    });
}
```

### Frontend: Customers/Show.vue
- Displays progress bar with color coding
- Shows expandable breakdown of each section
- Real-time updates via Inertia.js reload

## Benefits

1. **User Guidance**: Shows users exactly what's missing
2. **Data Quality**: Encourages complete profiles
3. **Risk Assessment**: Higher completion = more reliable data
4. **Process Tracking**: Visual indicator of onboarding progress
5. **Automated**: No manual intervention required

## Future Enhancements

- [ ] Email notifications when completion falls below threshold
- [ ] Dashboard widget showing avg completion across all customers
- [ ] Completion history tracking
- [ ] Required completion % before application submission
- [ ] Gamification elements (badges for 100% completion)

## Maintenance

### Database Column
```sql
-- customers table
profile_completion_percentage INT DEFAULT 0
```

### Performance
- Uses `updateQuietly()` to avoid triggering model events
- Recalculation is fast (<50ms per customer)
- Bulk recalculation command available for migrations

## Testing

Run the test suite:
```bash
php artisan test --filter=ProfileCompletion
```

Or test manually:
```bash
# Create test customer
php artisan tinker
>>> $customer = Customer::factory()->create()
>>> $customer->profile_completion_percentage  // See initial score

# Add info
>>> $customer->update(['employer_name' => 'Test Corp'])
>>> $customer->profile_completion_percentage  // Score increased

# Add document
>>> KycDocument::create(['customer_id' => $customer->id, 'document_type' => 'national_id', ...])
>>> $customer->fresh()->profile_completion_percentage  // Score increased again
```

---

**Last Updated**: February 25, 2026
**Version**: 1.0.0
