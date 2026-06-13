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

        Schema::create($prefix.'locales', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->boolean('rtl')->default(false);
            $table->boolean('is_source')->default(false);
            $table->string('tone')->default('neutral');
            $table->boolean('enabled')->default(true)->index();
            $table->timestamps();
        });

        Schema::create($prefix.'bundles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('namespace')->nullable();
            $table->string('file_path')->nullable();
            $table->string('format')->default('php');
            $table->timestamps();

            $table->unique(['name', 'namespace']);
        });

        Schema::create($prefix.'phrases', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('bundle_id')->constrained($prefix.'bundles')->cascadeOnDelete();
            $table->string('key');
            $table->text('note')->nullable();
            $table->json('placeholders')->nullable();
            $table->boolean('is_html')->default(false);
            $table->boolean('is_plural')->default(false);
            $table->string('priority')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['bundle_id', 'key']);
        });

        Schema::create($prefix.'messages', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('phrase_id')->constrained($prefix.'phrases')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained($prefix.'locales')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('status')->default('open')->index();
            $table->string('translated_by')->nullable()->index();
            $table->string('reviewed_by')->nullable();
            $table->text('review_note')->nullable();
            $table->boolean('ai_generated')->default(false)->index();
            $table->string('ai_provider')->nullable();
            $table->timestamps();

            $table->unique(['phrase_id', 'locale_id']);
        });

        Schema::create($prefix.'members', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role')->default('translator');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create($prefix.'member_locale', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignUlid('member_id')->constrained($prefix.'members')->cascadeOnDelete();
            $table->foreignId('locale_id')->constrained($prefix.'locales')->cascadeOnDelete();

            $table->unique(['member_id', 'locale_id']);
        });

        Schema::create($prefix.'import_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('locale_count')->default(0);
            $table->unsignedInteger('phrase_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('source')->default('cli');
            $table->string('triggered_by')->nullable();
            $table->boolean('fresh')->default(false);
            $table->timestamps();
        });

        Schema::create($prefix.'export_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('locale_count')->default(0);
            $table->unsignedInteger('file_count')->default(0);
            $table->unsignedInteger('phrase_count')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->string('source')->default('cli');
            $table->string('triggered_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $prefix = $this->prefix();

        foreach (['export_records', 'import_records', 'member_locale', 'members', 'messages', 'phrases', 'bundles', 'locales'] as $table) {
            Schema::dropIfExists($prefix.$table);
        }
    }

    public function getConnection(): ?string
    {
        return config('translations.database.connection');
    }
};
