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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('s3_path')->nullable();
            $table->string('s3_output_path')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->bigInteger('output_file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('whatsapp_user_id')->nullable();
            $table->string('status')->default('uploaded'); // uploaded, processing, completed, failed
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
