<?php

namespace App\Services;

use App\Models\LoanSchedule;
use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for handling loan installment reminders and notifications
 * 
 * This service identifies upcoming and overdue installments that need reminders
 * to be sent to customers and loan officers.
 */
class LoanReminderService
{
    /**
     * Get installments that need reminders (upcoming within 7 days)
     *
     * @param int|null $institutionId Filter by institution
     * @return Collection
     */
    public function getUpcomingInstallments(?int $institutionId = null): Collection
    {
        $today = Carbon::today();
        $reminderDate = Carbon::today()->addDays(7);
        
        $query = LoanSchedule::query()
            ->with(['loan.customer', 'loan.loanProduct'])
            ->whereIn('status', ['pending', 'partially_paid'])
            ->whereBetween('due_date', [$today, $reminderDate])
            ->whereHas('loan', function($q) {
                $q->where('status', 'active');
            });
        
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        return $query->orderBy('due_date', 'asc')->get();
    }
    
    /**
     * Get overdue installments that need follow-up
     *
     * @param int|null $institutionId Filter by institution
     * @return Collection
     */
    public function getOverdueInstallments(?int $institutionId = null): Collection
    {
        $query = LoanSchedule::query()
            ->with(['loan.customer', 'loan.loanProduct'])
            ->where('status', 'overdue')
            ->whereHas('loan', function($q) {
                $q->whereIn('status', ['active', 'defaulted']);
            });
        
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        return $query->orderBy('days_past_due', 'desc')->get();
    }
    
    /**
     * Get installments by reminder priority
     * 
     * Returns categorized installments:
     * - critical: >30 days overdue
     * - urgent: 1-30 days overdue
     * - warning: Due within 3 days
     * - upcoming: Due within 4-7 days
     *
     * @param int|null $institutionId Filter by institution
     * @return array
     */
    public function getInstallmentsByPriority(?int $institutionId = null): array
    {
        $today = Carbon::today();
        
        $query = LoanSchedule::query()
            ->with(['loan.customer', 'loan.loanProduct'])
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->whereHas('loan', function($q) {
                $q->whereIn('status', ['active', 'defaulted']);
            });
        
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }
        
        $allInstallments = $query->get();
        
        return [
            'critical' => $allInstallments->filter(function($i) {
                return $i->days_past_due > 30;
            }),
            'urgent' => $allInstallments->filter(function($i) {
                return $i->days_past_due > 0 && $i->days_past_due <= 30;
            }),
            'warning' => $allInstallments->filter(function($i) use ($today) {
                $daysUntilDue = $today->diffInDays($i->due_date, false);
                return $daysUntilDue >= 0 && $daysUntilDue <= 3;
            }),
            'upcoming' => $allInstallments->filter(function($i) use ($today) {
                $daysUntilDue = $today->diffInDays($i->due_date, false);
                return $daysUntilDue > 3 && $daysUntilDue <= 7;
            }),
        ];
    }
    
    /**
     * Send reminder notification to customer
     * 
     * TODO: Implement SMS/Email notification
     * This will send reminders via SMS, Email, or Push notification
     *
     * @param LoanSchedule $installment
     * @param string $type 'upcoming'|'overdue'
     * @return bool
     */
    public function sendCustomerReminder(LoanSchedule $installment, string $type = 'upcoming'): bool
    {
        // Placeholder for notification implementation
        // Will integrate with notification channels (SMS, Email, Push)
        
        return true;
    }
    
    /**
     * Send alert to loan officer
     * 
     * TODO: Implement officer notification
     * Notifies assigned loan officer about overdue payments
     *
     * @param LoanSchedule $installment
     * @return bool
     */
    public function sendOfficerAlert(LoanSchedule $installment): bool
    {
        // Placeholder for officer alert implementation
        
        return true;
    }
}
