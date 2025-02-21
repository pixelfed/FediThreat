<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instances', function (Blueprint $table) {
            $table->id();

            // Basic instance information
            $table->string('domain')->unique();
            $table->string('name');
            $table->string('api_key', 32)->unique();

            // Software details
            $table->string('software')->index();
            $table->string('software_version');

            // Instance stats
            $table->integer('total_users')->default(0);
            $table->integer('reports_count')->default(0);
            $table->integer('threats_detected')->default(0);

            // Contact information
            $table->string('admin_email');

            // Instance status
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended'
            ])->default('pending')->index();

            // Configuration and metadata
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();

            // Timestamps for important events
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            // Standard timestamps and soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('created_at');
            $table->index(['status', 'last_seen_at']);
            $table->index(['software', 'status']);
        });

        // Create instance activity log table
        Schema::create('instance_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['instance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instance_activities');
        Schema::dropIfExists('instances');
    }
};
