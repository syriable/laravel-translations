<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_ai_usage_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('model')->nullable();
            $table->string('source_locale', 16);
            $table->string('target_locale', 16);
            $table->unsignedInteger('keys');
            $table->unsignedInteger('input_characters');
            $table->unsignedInteger('output_characters');
            $table->decimal('estimated_cost', 10, 4)->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['provider', 'created_at']);
            $table->index('target_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_ai_usage_logs');
    }
};
