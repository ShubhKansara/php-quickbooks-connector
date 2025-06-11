<?php

namespace ShubhKansara\PhpQuickbooksConnector\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksLogEvent;

class SyncManager
{
    /**
     * enqueue an entity for sync
     */

    /**
     * Enqueue an entity for sync.
     */

    public function enqueue(string $entityType, int $entityId, string $action, array $payload = [], int $priority = 10, ?string $group = null): ?QbSyncQueue
    {
        try {
            return QbSyncQueue::create([
                'entity_type'   => $entityType,
                'entity_id'     => $entityId,
                'action'        => $action,
                'payload'       => $payload,
                'group'         => $group,
                'priority'      => $priority,
                'status'        => 'pending',
                'queued_at'     => Carbon::now(),
                'available_at'  => Carbon::now(),  // you can delay if needed
            ]);
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', 'SyncManager enqueue error', ['exception' => $e->getMessage()]));
            return null;
        }
    }

    /**
     * Grab the next pending job ( only those due now ), highest priority first.
     */

    public function nextJob(): ?QbSyncQueue
    {
        try {
            return DB::transaction(function () {
                // Check if any job is already processing
                if (QbSyncQueue::where('status', 'processing')->exists()) {
                    // If yes, do not pick a new job
                    return null;
                }

                $job = QbSyncQueue::where('status', 'pending')
                    ->where('available_at', '<=', now())
                    ->lockForUpdate()
                    ->orderByDesc('priority')
                    ->orderBy('queued_at')
                    ->first();

                if ($job) {
                    $this->markStarted($job);
                }

                return $job;
            });
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', 'SyncManager nextJob error', ['exception' => $e->getMessage()]));
            return null;
        }
    }

    /**
     * Mark a job as “in progress” so it won’t be picked up again.
     */

    public function markStarted(QbSyncQueue $job): void
    {
        try {
            $job->update([
                'status'       => 'processing',
                'processed_at' => null,
                'result'       => null,
            ]);
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', 'SyncManager markStarted error', ['exception' => $e->getMessage(), 'job_id' => $job->id]));
        }
    }

    /**
     * Mark that job as processed ( success or error ).
     */

    public function markProcessed(QbSyncQueue $job, bool $success, ?string $result = null): void
    {
        try {
            $job->update([
                'status'       => $success ? 'completed' : 'error',
                'processed_at' => now(),
                'result'       => $result,
            ]);
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', 'SyncManager markProcessed error', ['exception' => $e->getMessage(), 'job_id' => $job->id]));
        }
    }

    /**
     * How many jobs remain ( pending or in‐progress ) so QBWC knows to loop.
     */

    public function remaining(): int
    {
        try {
            return QbSyncQueue::whereIn('status', ['pending', 'processing'])->count();
        } catch (\Throwable $e) {
            event(new QuickBooksLogEvent('error', 'SyncManager remaining error', ['exception' => $e->getMessage()]));
            return 0;
        }
    }
}
