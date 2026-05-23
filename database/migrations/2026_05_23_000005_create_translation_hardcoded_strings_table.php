<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_hardcoded_strings', function (Blueprint $table): void {
            $table->id();
            $table->string('file_path', 500);
            $table->unsignedInteger('line_number');
            $table->text('text');
            $table->string('text_hash', 40);
            $table->string('element_type', 50)->nullable();
            $table->string('scanner_type', 20);
            $table->string('status', 20)->default('pending');
            $table->text('converted_key')->nullable();
            $table->timestamps();

            $table->unique(['file_path', 'line_number', 'text_hash'], 'translation_hardcoded_unique');
            $table->index('status');
            $table->index('text_hash');
        });

        Schema::create('translation_hardcoded_ignores', function (Blueprint $table): void {
            $table->id();
            $table->string('text_hash', 40)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('note')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->index('text_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_hardcoded_ignores');
        Schema::dropIfExists('translation_hardcoded_strings');
    }
};
