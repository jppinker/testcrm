<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('schedule_publications', function (Blueprint $table) { $table->id(); $table->date('from_date'); $table->date('to_date'); $table->text('snapshot'); $table->timestamp('published_at'); }); }
 public function down(): void { Schema::dropIfExists('schedule_publications'); }
};
