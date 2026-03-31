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
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
