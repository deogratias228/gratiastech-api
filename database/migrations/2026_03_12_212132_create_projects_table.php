<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('tracking_code')->unique(); // ex: GT-2024-001
            $table->string('status')->default('draft'); // , ['draft', 'in_progress', 'on_hold', 'completed', 'cancelled',]
            $table->string('type')->default('other');  //  , ['web_development','software','saas','maintenance','other',]
            $table->unsignedTinyInteger('progress')->default(0); // 0–100
            $table->date('started_at')->nullable();
            $table->date('estimated_end_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->json('tech_stack')->nullable();     // ["Laravel","Next.js",...]
            $table->text('internal_notes')->nullable(); // admin seulement
            $table->boolean('is_portfolio')->default(false);
            $table->json('portfolio_data')->nullable(); // données publiques
            $table->timestamps();
            $table->softDeletes();
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
