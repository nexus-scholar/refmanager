<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reference_collection_document', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained('reference_collections')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->timestamp('added_at')->useCurrent();
            $table->text('note')->nullable();
            $table->primary(['collection_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_collection_document');
    }
};
