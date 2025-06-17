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
        Schema::create('qwc_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('username');
            $table->string('password');
            $table->string('description')->nullable();
            $table->integer('run_every_n_minutes');
            $table->string('file_path');
            $table->string('download_url');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qwc_files');
    }
};
