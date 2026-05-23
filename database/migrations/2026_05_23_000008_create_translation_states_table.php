<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_states', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 16);
            $table->text('translation_key');
            $table->string('key_hash', 40);
            $table->string('status', 20);
            $table->boolean('ai_generated')->default(false);
            $table->string('reviewed_by')->nullable();
            $table->text('reviewer_feedback')->nullable();
            $table->timestamps();

            $table->unique(['locale', 'key_hash']);
            $table->index(['locale', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_states');
    }
};
