<?php

namespace App\Services;

use App\Models\CollectionsQueue;
use App\Models\Loan;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CollectionsService
{
    /**
     * Generate collections queue for an institution.
     * Identifies all delinquent loans and creates/updates queue items.
     */
    public function generateQueue(int $institutionId): array
    {
        $institution = Institution::findOrFail($institutionId);
        
        // Get all active loans with arrears
        $delinquentLoans = Loan::where('institution_id', $institutionId)
            ->where('status', 'active')
            ->where('days_past_due', '>', 0)
            ->with(['customer', 'repayments'])
            ->get();

        $created = 0;
        $updated = 0;

        foreach ($delinquentLoans as $loan) {
            $queueItem = CollectionsQueue::firstOrNew([
                'institution_id' => $institutionId,
                'loan_id' => $loan->id,
            ]);

            $isNew = !$queueItem->exists;

            // Update delinquency details
            $queueItem->customer_id = $loan->customer_id;
            $queueItem->days_past_due = $loan->days_past_due;
            $queueItem->total_arrears = $loan->total_arrears;
            $queueItem->principal_arrears = $loan->principal_arrears ?? 0;
            $queueItem->interest_arrears = $loan->interest_arrears ?? 0;
            $queueItem->penalty_arrears = $loan->penalty_arrears ?? 0;
            $queueItem->fees_arrears = $loan->fees_arrears ?? 0;

            // Update customer contact info
            $queueItem->customer_phone = $loan->customer->phone;
            $queueItem->customer_email = $loan->customer->email;
            $queueItem->customer_address = $loan->customer->physical_address;

            // Calculate priority
            $queueItem->priority_score = $queueItem->calculatePriorityScore();
            $queueItem->updatePriorityLevel();
            $queueItem->updateDelinquencyBucket();

            // Update PTP flag
            $queueItem->has_active_ptp = $loan->hasActivePTP();

            $queueItem->save();

            if ($isNew) {
                $created++;
            } else {
                $updated++;
            }
        }

        // Remove resolved items (loans that are no longer delinquent)
        CollectionsQueue::where('institution_id', $institutionId)
            ->whereNotIn('loan_id', $delinquentLoans->pluck('id'))
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->update(['status' => 'resolved']);

        return [
            'created' => $created,
            'updated' => $updated,
            'total_queue_size' => $delinquentLoans->count(),
        ];
    }

    /**
     * Get collections queue with filters and sorting.
     */
    public function getQueue(int $institutionId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = CollectionsQueue::where('institution_id', $institutionId)
            ->with(['loan', 'customer', 'assignedTo', 'latestAction']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority_level'])) {
            $query->where('priority_level', $filters['priority_level']);
        }

        if (!empty($filters['delinquency_bucket'])) {
            $query->where('delinquency_bucket', $filters['delinquency_bucket']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['has_active_ptp'])) {
            $query->where('has_active_ptp', $filters['has_active_ptp']);
        }

        if (isset($filters['is_legal_case'])) {
            $query->where('is_legal_case', $filters['is_legal_case']);
        }

        if (!empty($filters['min_dpd'])) {
            $query->where('days_past_due', '>=', $filters['min_dpd']);
        }

        if (!empty($filters['max_dpd'])) {
            $query->where('days_past_due', '<=', $filters['max_dpd']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'priority';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'priority':
                $query->orderByPriority();
                break;
            case 'dpd':
                $query->orderBy('days_past_due', $sortOrder);
                break;
            case 'amount':
                $query->orderBy('total_arrears', $sortOrder);
                break;
            case 'last_action':
                $query->orderBy('last_action_at', $sortOrder);
                break;
            default:
                $query->orderBy('id', $sortOrder);
        }

        return $query->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Assign collections queue items to officers.
     * Uses workload distribution algorithms.
     */
    public function assignToOfficers(int $institutionId, array $assignments): array
    {
        $results = [];

        DB::beginTransaction();
        try {
            foreach ($assignments as $assignment) {
                $queueIds = $assignment['queue_ids'];
                $officerId = $assignment['officer_id'];

                $updated = CollectionsQueue::whereIn('id', $queueIds)
                    ->where('institution_id', $institutionId)
                    ->update([
                        'assigned_to' => $officerId,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                    ]);

                $results[] = [
                    'officer_id' => $officerId,
                    'assigned_count' => $updated,
                ];
            }

            DB::commit();
            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Auto-distribute unassigned items to officers based on workload.
     */
    public function autoDistribute(int $institutionId, array $officerIds): array
    {
        // Get current workload for each officer
        $workloads = [];
        foreach ($officerIds as $officerId) {
            $workloads[$officerId] = CollectionsQueue::where('institution_id', $institutionId)
                ->where('assigned_to', $officerId)
                ->whereIn('status', ['assigned', 'in_progress', 'contacted'])
                ->count();
        }

        // Get unassigned items ordered by priority
        $unassignedItems = CollectionsQueue::where('institution_id', $institutionId)
            ->where('status', 'pending')
            ->orderByPriority()
            ->get();

        $assignments = [];

        foreach ($unassignedItems as $item) {
            // Find officer with lowest workload
            asort($workloads);
            $assignToOfficer = array_key_first($workloads);

            if (!isset($assignments[$assignToOfficer])) {
                $assignments[$assignToOfficer] = [];
            }

            $assignments[$assignToOfficer][] = $item->id;
            $workloads[$assignToOfficer]++;
        }

        // Perform assignments
        $results = [];
        foreach ($assignments as $officerId => $queueIds) {
            $updated = CollectionsQueue::whereIn('id', $queueIds)
                ->update([
                    'assigned_to' => $officerId,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                ]);

            $results[] = [
                'officer_id' => $officerId,
                'assigned_count' => $updated,
            ];
        }

        return $results;
    }

    /**
     * Get collections performance metrics.
     */
    public function getPerformanceMetrics(int $institutionId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $metrics = [];

        // Queue statistics
        $metrics['queue_stats'] = [
            'total_items' => CollectionsQueue::where('institution_id', $institutionId)->count(),
            'pending' => CollectionsQueue::where('institution_id', $institutionId)->status('pending')->count(),
            'assigned' => CollectionsQueue::where('institution_id', $institutionId)->status('assigned')->count(),
            'in_progress' => CollectionsQueue::where('institution_id', $institutionId)->status('in_progress')->count(),
            'resolved' => CollectionsQueue::where('institution_id', $institutionId)->status('resolved')->count(),
        ];

        // Priority distribution
        $metrics['priority_distribution'] = CollectionsQueue::where('institution_id', $institutionId)
            ->select('priority_level', DB::raw('count(*) as count'))
            ->groupBy('priority_level')
            ->pluck('count', 'priority_level')
            ->toArray();

        // Delinquency bucket distribution
        $metrics['bucket_distribution'] = CollectionsQueue::where('institution_id', $institutionId)
            ->select('delinquency_bucket', DB::raw('count(*) as count'), DB::raw('sum(total_arrears) as total_amount'))
            ->groupBy('delinquency_bucket')
            ->get()
            ->keyBy('delinquency_bucket')
            ->toArray();

        // Average days past due
        $metrics['avg_days_past_due'] = CollectionsQueue::where('institution_id', $institutionId)
            ->avg('days_past_due');

        // Total arrears in queue
        $metrics['total_arrears'] = CollectionsQueue::where('institution_id', $institutionId)
            ->sum('total_arrears');

        return $metrics;
    }

    /**
     * Get collections officer performance.
     */
    public function getOfficerPerformance(int $institutionId, int $officerId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        $performance = [];

        // Workload
        $performance['current_workload'] = CollectionsQueue::where('institution_id', $institutionId)
            ->where('assigned_to', $officerId)
            ->whereIn('status', ['assigned', 'in_progress', 'contacted'])
            ->count();

        // Actions taken
        $performance['actions_count'] = \App\Models\CollectionsAction::where('institution_id', $institutionId)
            ->where('performed_by', $officerId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->count();

        // Successful contacts
        $performance['successful_contacts'] = \App\Models\CollectionsAction::where('institution_id', $institutionId)
            ->where('performed_by', $officerId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->successful()
            ->count();

        // PTPs created
        $performance['ptps_created'] = \App\Models\PromiseToPay::where('institution_id', $institutionId)
            ->where('created_by', $officerId)
            ->whereBetween('promise_date', [$startDate, $endDate])
            ->count();

        // PTPs kept
        $performance['ptps_kept'] = \App\Models\PromiseToPay::where('institution_id', $institutionId)
            ->where('created_by', $officerId)
            ->whereBetween('promise_date', [$startDate, $endDate])
            ->kept()
            ->count();

        // Resolution rate
        $performance['items_resolved'] = CollectionsQueue::where('institution_id', $institutionId)
            ->where('assigned_to', $officerId)
            ->where('status', 'resolved')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Calculate rates
        $performance['success_rate'] = $performance['actions_count'] > 0 
            ? round(($performance['successful_contacts'] / $performance['actions_count']) * 100, 2)
            : 0;

        $performance['ptp_fulfillment_rate'] = $performance['ptps_created'] > 0
            ? round(($performance['ptps_kept'] / $performance['ptps_created']) * 100, 2)
            : 0;

        return $performance;
    }

    /**
     * Update queue item status.
     */
    public function updateStatus(int $queueId, string $status, array $data = []): CollectionsQueue
    {
        $queueItem = CollectionsQueue::findOrFail($queueId);
        
        $queueItem->status = $status;

        // Update timestamps based on status
        if ($status === 'in_progress' && !$queueItem->last_action_at) {
            $queueItem->last_action_at = now();
        }

        if ($status === 'resolved' || $status === 'closed') {
            $queueItem->last_action_at = now();
        }

        // Merge any additional data
        foreach ($data as $key => $value) {
            if (in_array($key, $queueItem->getFillable())) {
                $queueItem->$key = $value;
            }
        }

        $queueItem->save();

        return $queueItem;
    }

    /**
     * Escalate queue item to legal.
     */
    public function escalateToLegal(int $queueId, string $reason): CollectionsQueue
    {
        $queueItem = CollectionsQueue::findOrFail($queueId);
        
        $queueItem->status = 'escalated';
        $queueItem->is_legal_case = true;
        $queueItem->notes = ($queueItem->notes ?? '') . "\n\nEscalated to legal: " . $reason;
        $queueItem->save();

        return $queueItem;
    }
}
