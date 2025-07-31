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
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email')->unique();
        $table->string('phone_number')->nullable();
        $table->string('roll_number')->unique();
        $table->string('class_grade');
        $table->date('date_of_birth')->nullable();
        $table->date('admission_date')->nullable();
        $table->enum('status', ['Active', 'Inactive'])->default('Active');
        $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
