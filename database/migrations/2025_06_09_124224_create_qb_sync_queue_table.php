<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qb_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');      // e.g. 'Item', 'Customer', etc.
            $table->unsignedBigInteger('entity_id');
            $table->unsignedInteger('priority')->default(10);
            $table->string('action')->default('add');
            $table->string('group')->nullable();          // e.g. 'high-priority', 'batch-job'
            $table->json('payload')->nullable();          // whatever data you need to pass
            $table->timestamp('queued_at')->useCurrent();
            $table->timestamp('available_at')->nullable(); // if you want to delay

            // NEW: track state and result
            $table->string('status')->default('pending')
                ->comment('pending, processing, completed, error');
            $table->text('result')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['available_at', 'priority']);
            $table->index(['entity_type',   'entity_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qb_sync_queue');
    }
};
