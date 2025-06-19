<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threat_scores', function (Blueprint $table) {
            $table->id();

            $table->enum('target_type', ['ip', 'email', 'url', 'username'])
                  ->index();
            $table->string('target_value', 512)
                  ->index();

            $table->decimal('score', 5, 2)
                  ->index();
            $table->enum('risk_level', ['low', 'medium', 'high'])
                  ->index();

            $table->json('provider_results');
            $table->json('instance_reports');
            $table->json('recommendations');

            $table->integer('total_reports')->default(0);
            $table->integer('unique_instances')->default(0);
            $table->json('severity_breakdown')->nullable();
            $table->json('reason_breakdown')->nullable();

            $table->timestamp('first_seen_at');
            $table->timestamp('last_reported_at');
            $table->timestamps();

            $table->unique(['target_type', 'target_value'], 'unique_target');

            $table->index(['risk_level', 'score']);
            $table->index(['target_type', 'score']);
            $table->index(['created_at', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threat_scores');
    }
};
