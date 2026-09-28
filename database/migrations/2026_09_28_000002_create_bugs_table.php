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
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->index()->constrained()->cascadeOnDelete();
            // Assigned right after insert from the id (BUG-001), inside the same transaction.
            $table->string('bug_code', 20)->nullable()->unique();
            $table->string('title');
            $table->text('description');
            $table->string('category', 30)->nullable();
            $table->text('steps_to_reproduce')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->string('page_screen')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('device', 100)->nullable();

            // Reporter identity: admin-only, never exposed publicly.
            $table->string('reporter_name', 100)->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_ip', 45)->nullable();

            // Set by the admin after review.
            $table->string('severity', 20)->nullable()->index();
            $table->string('priority', 20)->nullable();
            $table->unsignedSmallInteger('score')->nullable();
            $table->string('verification_status', 20)->default('pending')->index();
            $table->string('status', 20)->default('open')->index();
            $table->text('admin_note')->nullable();

            $table->timestamps();
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
