<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_templates', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('checklist_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('checklist_template_id')->constrained()->cascadeOnDelete();
            $t->string('question');
            $t->unsignedSmallInteger('position')->default(0);
        });

        // Per-audit answers. The question text is copied (snapshot) so history survives template edits.
        Schema::create('audit_checks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('security_audit_id')->constrained()->restrictOnDelete();
            $t->string('question');
            $t->unsignedSmallInteger('position')->default(0);
            $t->string('result', 20)->default('pending')->index(); // pending, pass, fail, not_applicable
            $t->text('notes')->nullable();
            $t->foreignId('finding_id')->nullable()->constrained('audit_findings')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_checks');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklist_templates');
    }
};
