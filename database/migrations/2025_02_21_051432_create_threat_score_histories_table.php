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
        Schema::create('threat_score_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('threat_score_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->decimal('score', 5, 2);
            $table->enum('risk_level', ['low', 'medium', 'high']);
            $table->json('provider_results');
            $table->integer('total_reports');
            $table->integer('unique_instances');

            $table->string('trigger_type');
            $table->json('change_details')->nullable();

            $table->timestamps();

            $table->index(['threat_score_id', 'created_at']);
            $table->index(['created_at', 'trigger_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threat_score_histories');
    }
};
