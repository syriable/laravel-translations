<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_glossary_terms', function (Blueprint $table): void {
            $table->id();
            $table->string('source_term')->unique();
            $table->text('context')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('exact_match')->default(false);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('translation_glossary_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('glossary_term_id')->constrained('translation_glossary_terms')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('translation');
            $table->string('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['glossary_term_id', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_glossary_translations');
        Schema::dropIfExists('translation_glossary_terms');
    }
};
