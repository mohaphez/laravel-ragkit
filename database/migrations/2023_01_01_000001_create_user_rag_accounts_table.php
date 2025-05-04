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
        Schema::create('rag_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rag_account_id')->constrained('rag_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('collection_id')->nullable();
            $table->json('collection_metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['rag_account_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rag_collections');
    }
}; 