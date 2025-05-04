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
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rag_collection_id')->constrained('rag_collections')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('document_id')->nullable();
            $table->string('original_filename');
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->json('outlines')->nullable();
            $table->json('faqs')->nullable();
            $table->timestamps();

            $table->index(['rag_collection_id', 'status']);
            $table->unique(['rag_collection_id', 'original_filename']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_documents');
    }
}; 