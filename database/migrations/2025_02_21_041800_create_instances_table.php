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

            $table->string('domain')->unique();
            $table->string('name');
            $table->string('api_key', 32)->unique();
            $table->string('software')->nullable();
            $table->string('software_version')->nullable();
            $table->integer('total_users')->default(0);
            $table->integer('reports_count')->default(0);
            $table->integer('threats_detected')->default(0);
            $table->string('admin_email')->nullable();
            $table->enum('status', [
                'pending',
                'active',
                'inactive',
                'suspended'
            ])->default('pending')->index();
            $table->boolean('can_report')->default(false);
            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('created_at');
            $table->index(['status', 'last_seen_at']);
            $table->index(['software', 'status']);
        });

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
