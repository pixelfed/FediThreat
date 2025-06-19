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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instance_id')->constrained()->onDelete('cascade');

            $table->enum('target_type', ['ip', 'email', 'url', 'username'])->index();
            $table->string('target_value', 512)->index();

            $table->enum('reason', [
                'spam',
                'abuse',
                'seo',
                'csam',
                'phishing',
                'malware',
                'other'
            ])->index();

            $table->unsignedTinyInteger('severity')->default(1)->index();

            $table->text('evidence')->nullable();
            $table->json('metadata')->nullable();

            $table->enum('status', [
                'pending',
                'confirmed',
                'rejected',
                'expired'
            ])->default('pending')->index();

            $table->string('reporter_ip', 45)->nullable();
            $table->string('reporter_fingerprint', 64)->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['target_type', 'target_value', 'status']);
            $table->index(['instance_id', 'created_at']);

            $table->unique([
                'instance_id',
                'target_type',
                'target_value'
            ], 'unique_instance_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
