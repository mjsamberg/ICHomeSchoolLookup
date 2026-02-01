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
         Schema::create('assigmment_exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('student_uid');
            $table->string('student_name');
            $table->string('grade_level');
            $table->string('address');
            $table->string('current_school_number');
            $table->string('current_school_name');
            $table->string('home_school_number');
            $table->string('home_school_name');
            $table->boolean('is_mv')->default(false);
            $table->boolean('is_choice_school')->default(false);
            $table->boolean('is_reassigned')->default(false);
            $table->string('reassignment_reason')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
