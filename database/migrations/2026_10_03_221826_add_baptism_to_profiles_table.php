<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('baptism_status', 20)->default('not_yet')->index()->after('current_program_id');
            $table->date('baptism_date')->nullable()->after('baptism_status');
            $table->string('baptism_place')->nullable()->after('baptism_date');
            $table->text('baptism_notes')->nullable()->after('baptism_place');
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['baptism_status']);
            $table->dropColumn(['baptism_status', 'baptism_date', 'baptism_place', 'baptism_notes']);
        });
    }
};
