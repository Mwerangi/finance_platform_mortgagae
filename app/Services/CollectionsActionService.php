<?php

namespace App\Services;

use App\Models\CollectionsAction;
use App\Models\CollectionsQueue;
use App\Models\PromiseToPay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CollectionsActionService
{
    /**
     * Log a collections action.
     */
    public function logAction(array $data): CollectionsAction
    {
        DB::beginTransaction();
        try {
            $action = CollectionsAction::create($data);

            // Update collections queue
            if ($action->queue_id) {
                $queueItem = CollectionsQueue::find($action->queue_id);
                if ($queueItem) {
                    $queueItem->last_action_at = $action->action_date;
                    $queueItem->contact_attempts++;

                    if ($action->isSuccessful()) {
                        $queueItem->successful_contacts++;
                    }

                    // Update status based on outcome
                    if ($action->outcome === 'payment_received') {
                        $queueItem->status = 'resolved';
                    } elseif ($action->outcome === 'payment_promised') {
                        $queueItem->status = 'ptp_made';
                    } elseif ($action->isSuccessful()) {
                        $queueItem->status = 'contacted';
                    }

                    $queueItem->save();
                }
            }

            // Create PTP if payment was promised
            if ($action->outcome === 'payment_promised' && $action->amount_committed && $action->commitment_date) {
                $ptp = $this->createPromiseToPayFromAction($action);
                $action->promise_to_pay_id = $ptp->id;
                $action->save();
            }

            DB::commit();
            return $action->load(['loan', 'customer', 'performedBy', 'queue']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get action history for a loan.
     */
    public function getLoanHistory(int $loanId, ?Carbon $startDate = null, ?Carbon $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CollectionsAction::where('loan_id', $loanId)
            ->with(['performedBy', 'promiseToPay'])
            ->orderBy('action_date', 'desc');

        if ($startDate) {
            $query->where('action_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('action_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get action history for a customer.
     */
    public function getCustomerHistory(int $customerId, ?Carbon $startDate = null, ?Carbon $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CollectionsAction::where('customer_id', $customerId)
            ->with(['loan', 'performedBy', 'promiseToPay'])
            ->orderBy('action_date', 'desc');

        if ($startDate) {
            $query->where('action_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('action_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Get actions by officer.
     */
    public function getOfficerActions(int $officerId, ?Carbon $startDate = null, ?Carbon $endDate = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CollectionsAction::where('performed_by', $officerId)
            ->with(['loan', 'customer'])
            ->orderBy('action_date', 'desc');

        if ($startDate) {
            $query->where('action_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('action_date', '<=', $endDate);
        }

        return $query->get();
    }

    /**
     * Create a promise to pay from a collections action.
     */
    protected function createPromiseToPayFromAction(CollectionsAction $action): PromiseToPay
    {
        $ptp = PromiseToPay::create([
            'institution_id' => $action->institution_id,
            'loan_id' => $action->loan_id,
            'customer_id' => $action->customer_id,
            'collections_action_id' => $action->id,
            'created_by' => $action->performed_by,
            'promise_date' => $action->action_date,
            'commitment_date' => $action->commitment_date,
            'promised_amount' => $action->amount_committed,
            'status' => 'open',
        ]);

        // Update queue to reflect active PTP
        if ($action->queue_id) {
            CollectionsQueue::where('id', $action->queue_id)
                ->update(['has_active_ptp' => true]);
        }

        return $ptp;
    }

    /**
     * Create a promise to pay manually.
     */
    public function createPromiseToPay(array $data): PromiseToPay
    {
        DB::beginTransaction();
        try {
            $ptp = PromiseToPay::create($data);

            // Update queue to reflect active PTP
            $queueItem = CollectionsQueue::where('loan_id', $data['loan_id'])
                ->where('institution_id', $data['institution_id'])
                ->first();

            if ($queueItem) {
                $queueItem->has_active_ptp = true;
                $queueItem->status = 'ptp_made';
                $queueItem->next_action_due = $data['commitment_date'];
                $queueItem->save();
            }

            DB::commit();
            return $ptp->load(['loan', 'customer', 'createdBy']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update promise to pay status.
     */
    public function updatePromiseStatus(int $ptpId, array $data): PromiseToPay
    {
        $ptp = PromiseToPay::findOrFail($ptpId);

        DB::beginTransaction();
        try {
            if (isset($data['status'])) {
                $ptp->status = $data['status'];
            }

            if (isset($data['amount_paid'])) {
                $ptp->amount_paid = $data['amount_paid'];
            }

            if (isset($data['actual_payment_date'])) {
                $ptp->actual_payment_date = $data['actual_payment_date'];
            }

            if (isset($data['payment_id'])) {
                $ptp->payment_id = $data['payment_id'];
            }

            $ptp->save();

            // Update queue if PTP is kept or broken
            if (in_array($ptp->status, ['kept', 'partially_kept', 'broken'])) {
                $queueItem = CollectionsQueue::where('loan_id', $ptp->loan_id)
                    ->where('institution_id', $ptp->institution_id)
                    ->first();

                if ($queueItem) {
                    $queueItem->has_active_ptp = false;

                    if ($ptp->status === 'kept') {
                        $queueItem->status = 'resolved';
                    } elseif ($ptp->status === 'broken') {
                        $queueItem->broken_promises++;
                        // Recalculate priority with penalty
                        $queueItem->priority_score = $queueItem->calculatePriorityScore();
                        $queueItem->updatePriorityLevel();
                    }

                    $queueItem->save();
                }
            }

            DB::commit();
            return $ptp->load(['loan', 'customer']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get promise to pay by ID.
     */
    public function getPromiseToPay(int $ptpId): PromiseToPay
    {
        return PromiseToPay::with(['loan', 'customer', 'createdBy', 'collectionsAction', 'payment'])
            ->findOrFail($ptpId);
    }

    /**
     * Get promises to pay with filters.
     */
    public function getPromisesToPay(int $institutionId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PromiseToPay::where('institution_id', $institutionId)
            ->with(['loan', 'customer', 'createdBy']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['loan_id'])) {
            $query->where('loan_id', $filters['loan_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['commitment_date_from'])) {
            $query->where('commitment_date', '>=', $filters['commitment_date_from']);
        }

        if (!empty($filters['commitment_date_to'])) {
            $query->where('commitment_date', '<=', $filters['commitment_date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'commitment_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Monitor and update PTPs (called by scheduled job).
     */
    public function monitorPromisesToPay(int $institutionId): array
    {
        $results = [
            'broken' => 0,
            'due_today' => 0,
            'upcoming' => 0,
        ];

        // Mark overdue PTPs as broken
        $overduePTPs = PromiseToPay::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->where('commitment_date', '<', now()->startOfDay())
            ->get();

        foreach ($overduePTPs as $ptp) {
            $ptp->markAsBroken();
            $results['broken']++;
        }

        // Get PTPs due today
        $results['due_today'] = PromiseToPay::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->whereDate('commitment_date', now())
            ->count();

        // Get upcoming PTPs (next 7 days)
        $results['upcoming'] = PromiseToPay::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->whereBetween('commitment_date', [now()->addDay(), now()->addDays(7)])
            ->count();

        return $results;
    }

    /**
     * Get action effectiveness analysis.
     */
    public function getActionEffectiveness(int $institutionId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $effectiveness = [];

        // By action type
        $effectiveness['by_type'] = CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->select('action_type', 
                DB::raw('count(*) as total'),
                DB::raw('sum(case when outcome in ("successful", "payment_promised", "payment_received", "partial_payment") then 1 else 0 end) as successful')
            )
            ->groupBy('action_type')
            ->get()
            ->map(function ($item) {
                $item->success_rate = $item->total > 0 ? round(($item->successful / $item->total) * 100, 2) : 0;
                return $item;
            })
            ->toArray();

        // By outcome
        $effectiveness['by_outcome'] = CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->select('outcome', DB::raw('count(*) as count'))
            ->groupBy('outcome')
            ->pluck('count', 'outcome')
            ->toArray();

        // Overall success rate
        $totalActions = CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->count();

        $successfulActions = CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->successful()
            ->count();

        $effectiveness['overall_success_rate'] = $totalActions > 0
            ? round(($successfulActions / $totalActions) * 100, 2)
            : 0;

        return $effectiveness;
    }
}
