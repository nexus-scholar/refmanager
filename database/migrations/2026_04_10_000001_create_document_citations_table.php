<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_citations', function (Blueprint $table) {
            $table->foreignId('citing_document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('cited_document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('source')->default('unknown');
            $table->float('weight')->default(1.0);
            $table->timestamps();

            $table->unique(['citing_document_id', 'cited_document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_citations');
    }
};

