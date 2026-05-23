<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_key_contexts', function (Blueprint $table): void {
            $table->id();
            $table->text('translation_key');
            $table->string('key_hash', 40);
            $table->string('file_path', 500);
            $table->unsignedInteger('line_number');
            $table->string('helper', 30)->nullable();
            $table->string('file_type', 20)->nullable();
            $table->timestamps();

            $table->index('key_hash');
            $table->unique(['key_hash', 'file_path', 'line_number'], 'translation_key_contexts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_key_contexts');
    }
};
