<?php

namespace ShubhKansara\PhpQuickbooksConnector\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;

class SyncManager
{
    /**
     * enqueue an entity for sync
     */

    /**
     * Enqueue an entity for sync.
     */

    public function enqueue(string $entityType, int $entityId, string $action, array $payload = [], int $priority = 10, ?string $group = null): QbSyncQueue
    {
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
    }

    /**
     * Grab the next pending job ( only those due now ), highest priority first.
     */

    public function nextJob(): ?QbSyncQueue
    {
        return DB::transaction(function () {
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
    }

    /**
     * Mark a job as “in progress” so it won’t be picked up again.
     */

    public function markStarted(QbSyncQueue $job): void
    {
        $job->update([
            'status'       => 'processing',
            'processed_at' => null,
            'result'       => null,
            'started_at'   => now(),
        ]);
    }

    /**
     * Mark that job as processed ( success or error ).
     */

    public function markProcessed(QbSyncQueue $job, bool $success, ?string $result = null): void
    {
        $job->update([
            'status'       => $success ? 'completed' : 'error',
            'processed_at' => now(),
            'result'       => $result,
        ]);
    }

    /**
     * How many jobs remain ( pending or in‐progress ) so QBWC knows to loop.
     */

    public function remaining(): int
    {
        return QbSyncQueue::whereIn('status', ['pending', 'processing'])->count();
    }
}
