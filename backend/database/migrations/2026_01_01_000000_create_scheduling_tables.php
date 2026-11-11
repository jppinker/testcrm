<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('role')->default('агент · 24 ч'); $table->timestamps();
        });
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('start_time', 5); $table->string('end_time', 5); $table->string('color')->default('cornflower'); $table->timestamps();
        });
        Schema::create('shifts', function (Blueprint $table) {
            $table->id(); $table->foreignId('employee_id')->constrained()->cascadeOnDelete(); $table->foreignId('template_id')->nullable()->constrained('schedule_templates')->nullOnDelete(); $table->date('date'); $table->string('start_time', 5); $table->string('end_time', 5); $table->string('name')->default('Своя смена'); $table->string('color')->default('cornflower'); $table->timestamps(); $table->unique(['employee_id', 'date']);
        });
    }
    public function down(): void { Schema::dropIfExists('shifts'); Schema::dropIfExists('schedule_templates'); Schema::dropIfExists('employees'); }
};
