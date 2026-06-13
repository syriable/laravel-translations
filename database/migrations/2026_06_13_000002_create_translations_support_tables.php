<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function prefix(): string
    {
        return config('translations.database.prefix', 'tx_');
    }

    public function up(): void
    {
        $prefix = $this->prefix();

        Schema::create($prefix.'revisions', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('message_id')->constrained($prefix.'messages')->cascadeOnDelete();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('reason')->default('manual');
            $table->string('changed_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'created_at']);
            $table->index(['changed_by', 'created_at']);
        });

        Schema::create($prefix.'activities', function (Blueprint $table): void {
            $table->id();
            $table->string('member_id')->nullable()->index();
            $table->string('action')->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create($prefix.'ai_usages', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->foreignId('phrase_id')->nullable()->constrained($prefix.'phrases')->nullOnDelete();
            $table->string('source_locale', 10)->nullable();
            $table->string('target_locale', 10)->nullable();
            $table->unsignedInteger('input_chars')->default(0);
            $table->unsignedInteger('output_chars')->default(0);
            $table->decimal('cost', 10, 6)->default(0);
            $table->boolean('success')->default(true);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at']);
        });

        Schema::create($prefix.'terms', function (Blueprint $table): void {
            $table->id();
            $table->string('source')->unique();
            $table->text('note')->nullable();
            $table->boolean('case_sensitive')->default(false);
            $table->boolean('whole_word')->default(false);
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'term_definitions', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('term_id')->constrained($prefix.'terms')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained($prefix.'locales')->cascadeOnDelete();
            $table->string('value');
            $table->string('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['term_id', 'locale_id']);
        });

        Schema::create($prefix.'quality_issues', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('message_id')->constrained($prefix.'messages')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained($prefix.'locales')->cascadeOnDelete();
            $table->string('check')->index();
            $table->string('severity')->default('warning');
            $table->text('detail');
            $table->text('suggestion')->nullable();
            $table->boolean('fixable')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['locale_id', 'severity']);
        });

        Schema::create($prefix.'phrase_usages', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('phrase_id')->constrained($prefix.'phrases')->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedInteger('line')->nullable();
            $table->text('snippet')->nullable();
            $table->string('element_type')->nullable();
            $table->string('file_type')->nullable();
            $table->timestamps();

            $table->index('phrase_id');
        });

        Schema::create($prefix.'loose_strings', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->string('file_path', 500);
            $table->unsignedInteger('line')->nullable();
            $table->text('text');
            $table->string('text_hash', 64)->index();
            $table->string('element_type')->nullable();
            $table->string('scanner')->nullable();
            $table->string('status')->default('pending')->index();
            $table->foreignId('phrase_id')->nullable()->constrained($prefix.'phrases')->nullOnDelete();
            $table->json('placeholders')->nullable();
            $table->timestamps();

            $table->unique(['file_path', 'line', 'text_hash']);
        });

        Schema::create($prefix.'ignored_strings', function (Blueprint $table): void {
            $table->id();
            $table->string('file_path', 500)->nullable();
            $table->string('text_hash', 64)->nullable();
            $table->string('preview')->nullable();
            $table->string('scope')->default('global');
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $prefix = $this->prefix();

        $tables = [
            'ignored_strings', 'loose_strings', 'phrase_usages', 'quality_issues',
            'term_definitions', 'terms', 'ai_usages', 'activities', 'revisions',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($prefix.$table);
        }
    }

    public function getConnection(): ?string
    {
        return config('translations.database.connection');
    }
};
