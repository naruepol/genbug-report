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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code', 50)->unique();
            $table->string('name');
            $table->text('description');
            // Path on the "public" disk for uploaded images, or an absolute URL.
            $table->string('image_url', 2048)->nullable();
            $table->string('demo_url', 2048)->nullable();
            $table->string('repository_url', 2048)->nullable();
            $table->string('technology_stack')->nullable();
            $table->date('presentation_date')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
