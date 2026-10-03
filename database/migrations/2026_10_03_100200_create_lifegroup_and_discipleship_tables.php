<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 30)->index();
            $table->string('area')->nullable();
            $table->string('meeting_day', 10)->nullable();
            $table->time('meeting_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->foreignId('leader_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('co_leader_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('life_groups')->nullOnDelete();
            $table->boolean('accepting_members')->default(true);
            $table->boolean('is_public')->default(true);
            // Never rendered publicly — only shared after a join request is approved.
            $table->string('whatsapp_invite_url')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->date('launched_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('life_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('member');
            $table->string('status', 20)->default('active')->index();
            $table->date('joined_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();
            $table->unique(['life_group_id', 'profile_id']);
        });

        Schema::create('life_group_join_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('whatsapp', 20);
            $table->string('email')->nullable();
            $table->string('area')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('invite_shared_at')->nullable();
            $table->timestamps();
        });

        Schema::create('life_group_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_id')->constrained()->cascadeOnDelete();
            $table->date('meeting_date')->index();
            $table->string('topic')->nullable();
            $table->foreignId('leader_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('visitor_count')->default(0);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('life_group_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_group_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('present');
            $table->timestamps();
            $table->unique(['life_group_meeting_id', 'profile_id'], 'lga_unique');
        });

        Schema::create('class_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipleship_program_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('facilitator_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('registration_status', 20)->default('open');
            $table->string('status', 20)->default('planned')->index();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('session_number');
            $table->string('topic');
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->foreignId('facilitator_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->string('room')->nullable();
            $table->timestamps();
        });

        Schema::create('class_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('registered')->index();
            $table->timestamp('registered_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['class_batch_id', 'profile_id']);
        });

        Schema::create('class_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_participant_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('present');
            $table->timestamps();
            $table->unique(['class_session_id', 'class_participant_id'], 'ca_unique');
        });

        Schema::create('member_program_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipleship_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discipler_profile_id')->nullable()->constrained('profiles')->nullOnDelete();
            $table->foreignId('class_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('not_started')->index();
            $table->date('started_at')->nullable();
            $table->date('expected_completion_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->date('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['profile_id', 'discipleship_program_id'], 'mpp_unique');
        });

        Schema::create('member_chapter_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_program_progress_id')->constrained('member_program_progress')->cascadeOnDelete();
            $table->foreignId('curriculum_chapter_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('not_started');
            $table->date('completed_on')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_follow_up_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['member_program_progress_id', 'curriculum_chapter_id'], 'mcp_unique');
        });

        Schema::create('discipler_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipler_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('disciple_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('discipleship_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipler_relationship_id')->constrained()->cascadeOnDelete();
            $table->date('met_on');
            $table->string('topic')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_follow_up_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leadership_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('stage', 30)->default('potential')->index();
            $table->foreignId('recommended_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('recommendation')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['leadership_candidates', 'discipleship_meetings', 'discipler_relationships', 'member_chapter_progress',
            'member_program_progress', 'class_attendance', 'class_participants', 'class_sessions', 'class_batches',
            'life_group_attendances', 'life_group_meetings', 'life_group_join_requests', 'life_group_members', 'life_groups'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
