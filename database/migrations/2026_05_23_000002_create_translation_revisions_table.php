<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 16);
            $table->text('translation_key');
            $table->string('key_hash', 40);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('change_type', 20);
            $table->string('changed_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['locale', 'key_hash']);
            $table->index('change_type');
            $table->index('changed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_revisions');
    }
};
