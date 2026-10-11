<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role', 20)->default('staff')->index();
            $t->string('status', 20)->default('active');
        });

        Schema::create('assets', function (Blueprint $t) {
            $t->id();
            $t->string('asset_tag')->unique();
            $t->string('name');
            $t->string('category')->nullable();
            $t->string('location')->nullable();
            $t->string('status', 30)->default('active')->index();
            $t->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('maintenance_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('asset_id')->constrained()->restrictOnDelete();
            $t->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('priority', 20)->default('medium');
            $t->string('status', 20)->default('open')->index();
            $t->dateTime('scheduled_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('maintenance_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('maintenance_request_id')->constrained()->restrictOnDelete();
            $t->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $t->text('work_summary');
            $t->text('completion_notes')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        Schema::create('security_audits', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('scope')->nullable();
            $t->foreignId('auditor_id')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('in_progress')->index();
            $t->dateTime('started_at')->nullable();
            $t->dateTime('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('audit_findings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('security_audit_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('severity', 20)->index();
            $t->string('status', 20)->default('open')->index();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->date('due_date')->nullable();
            $t->dateTime('resolved_at')->nullable();
            $t->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action')->index();
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('outcome', 20)->default('success');
            $t->string('request_id', 36)->nullable();
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'audit_findings', 'security_audits', 'maintenance_records', 'maintenance_requests', 'assets'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'status']));
    }
};
