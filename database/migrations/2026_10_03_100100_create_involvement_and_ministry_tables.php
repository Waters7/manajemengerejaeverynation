<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ministries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('accepting_volunteers')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ministry_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('ministry_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ministry_role_id')->nullable()->constrained()->nullOnDelete();
            $table->json('skills')->nullable();
            $table->json('availability')->nullable();
            $table->date('joined_at')->nullable();
            $table->string('status', 20)->default('applicant')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['ministry_id', 'profile_id']);
        });

        // "Saya tertarik untuk..." — configurable by admin.
        Schema::create('involvement_interests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon', 40)->nullable();
            // Stable behaviour hook independent from the (editable) label.
            $table->string('action', 30)->nullable();
            $table->boolean('on_connect_card')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('involvement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('get_involved');
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('whatsapp', 20)->index();
            $table->string('email')->nullable();
            $table->string('area')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->string('campus_name')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('life_stage', 30)->nullable();
            $table->string('source', 30)->nullable();
            $table->string('source_other')->nullable();
            $table->text('experience')->nullable();
            $table->json('skills')->nullable();
            $table->json('availability')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->string('submitted_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('involvement_request_interest', function (Blueprint $table) {
            $table->foreignId('involvement_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('involvement_interest_id')->constrained()->cascadeOnDelete();
            $table->primary(['involvement_request_id', 'involvement_interest_id'], 'iri_primary');
        });

        Schema::create('involvement_request_ministry', function (Blueprint $table) {
            $table->foreignId('involvement_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->primary(['involvement_request_id', 'ministry_id'], 'irm_primary');
        });

        Schema::create('volunteer_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('involvement_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('whatsapp', 20);
            $table->string('email')->nullable();
            $table->string('area')->nullable();
            $table->string('church_connection')->nullable();
            $table->json('skills')->nullable();
            $table->text('experience')->nullable();
            $table->json('availability')->nullable();
            $table->text('motivation')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('interview_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('volunteer_application_ministry', function (Blueprint $table) {
            $table->foreignId('volunteer_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->primary(['volunteer_application_id', 'ministry_id'], 'vam_primary');
        });

        Schema::create('serving_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ministry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ministry_role_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->date('serve_date')->index();
            $table->string('service_label')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('follow_up_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('subject');
            $table->string('title');
            $table->string('category', 30)->default('general')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->index();
            $table->string('status', 20)->default('open')->index();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Contact log / internal notes attached to a person (and optionally a record).
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('notable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');
            $table->text('body');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['follow_ups', 'follow_up_tasks', 'serving_schedules', 'volunteer_application_ministry', 'volunteer_applications',
            'involvement_request_ministry', 'involvement_request_interest', 'involvement_requests', 'involvement_interests',
            'ministry_members', 'ministry_roles', 'ministries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
