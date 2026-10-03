<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 50)->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Campus Ministry users only manage the campuses assigned to them.
        Schema::create('campus_user', function (Blueprint $table) {
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['campus_id', 'user_id']);
        });

        // 4E curriculum: Stage -> Program -> Chapter/Session.
        Schema::create('discipleship_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('#0067B9');
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('discipleship_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipleship_stage_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 20)->default('book');
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->foreignId('prerequisite_id')->nullable()->constrained('discipleship_programs')->nullOnDelete();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_milestone')->default(false);
            $table->unsignedSmallInteger('total_sessions')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('curriculum_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discipleship_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();
            $table->unique(['discipleship_program_id', 'number']);
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->string('gender', 10)->nullable();
            $table->date('birth_date')->nullable()->index();
            $table->string('whatsapp', 20)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->text('address')->nullable();
            $table->string('area')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('school_name')->nullable();
            $table->string('life_stage', 30)->nullable();
            $table->string('photo_path')->nullable();
            $table->date('join_date')->nullable();
            $table->date('first_visit_date')->nullable();
            $table->string('source', 30)->nullable();
            $table->string('member_status', 30)->default('visitor')->index();
            $table->foreignId('current_stage_id')->nullable()->constrained('discipleship_stages')->nullOnDelete();
            $table->foreignId('current_program_id')->nullable()->constrained('discipleship_programs')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('newcomers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('journey_status', 30)->default('first_visit')->index();
            $table->date('first_visit_date')->nullable();
            $table->string('source', 30)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('timeline_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->nullableMorphs('subject');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_entries');
        Schema::dropIfExists('newcomers');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('curriculum_chapters');
        Schema::dropIfExists('discipleship_programs');
        Schema::dropIfExists('discipleship_stages');
        Schema::dropIfExists('campus_user');
        Schema::dropIfExists('campuses');
    }
};
