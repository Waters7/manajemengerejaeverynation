<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How a program is recognised: "count" = repeatable journey (record of how many times,
        // for yourself and for others), "once" = one-time certificate (Leadership 113 / 215), "none".
        Schema::table('discipleship_programs', function (Blueprint $table) {
            $table->string('certificate_type', 10)->default('count')->after('is_milestone');
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->index();
            $table->foreignId('discipleship_program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('certificate_number', 40)->unique();
            $table->string('title');
            $table->date('issued_at');
            // Uploaded scan / PDF (private disk). Program certificates without a file are rendered by the app.
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'discipleship_program_id'], 'certificates_profile_program_unique');
        });

        Schema::create('prophetic_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('given_on')->nullable();
            $table->string('given_by')->nullable();
            $table->string('audio_path');
            $table->string('audio_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prophetic_words');
        Schema::dropIfExists('certificates');
        Schema::table('discipleship_programs', function (Blueprint $table) {
            $table->dropColumn('certificate_type');
        });
    }
};
