<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('doi')->nullable()->index();
            $table->string('url')->nullable();
            $table->string('journal')->nullable();
            $table->string('book_title')->nullable();
            $table->string('volume')->nullable();
            $table->string('issue')->nullable();
            $table->string('pages')->nullable();
            $table->string('publisher')->nullable();
            $table->string('publisher_place')->nullable();
            $table->string('language', 10)->nullable();
            $table->integer('year')->nullable()->index();
            $table->json('keywords')->nullable();
            $table->string('document_type')->default('article');

            $table->string('provider')->default('unknown');
            $table->string('provider_id')->nullable();
            $table->string('arxiv_id')->nullable();
            $table->string('openalex_id')->nullable();
            $table->string('s2_id')->nullable();
            $table->string('pubmed_id')->nullable();
            $table->unsignedInteger('cited_by_count')->nullable();
            $table->string('query_id')->nullable();
            $table->string('query_text')->nullable();
            $table->timestamp('retrieved_at')->nullable();
            $table->unsignedInteger('cluster_id')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('status')->default('imported')->index();
            $table->string('exclusion_reason')->nullable();
            $table->unsignedBigInteger('merged_into_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
