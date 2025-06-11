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
        Schema::create('qb_entity_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qb_entity_id')->constrained('qb_entities')->onDelete('cascade');
            $table->string('action'); // e.g., 'sync', 'create', 'update'
            $table->text('request_template');
            $table->text('response_fields')->nullable();
            $table->string('handler_class')->nullable();
            $table->boolean('active')->default(true);
            $table->text('metadata')->nullable(); // Additional metadata if needed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qb_entity_actions');
    }
};
