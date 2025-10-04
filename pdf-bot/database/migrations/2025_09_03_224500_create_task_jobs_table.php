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
        Schema::create('task_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->onDelete('cascade');
            $table->string('type'); // compress, convert, ocr, summarize, translate, secure
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->json('parameters')->nullable(); // mode, format, language, etc.
            $table->text('error_message')->nullable();
            $table->json('result_metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('processing_time_seconds')->nullable();
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['document_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_jobs');
    }
};
