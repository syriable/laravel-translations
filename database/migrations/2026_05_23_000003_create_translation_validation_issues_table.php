<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_validation_issues', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 16);
            $table->text('translation_key');
            $table->string('key_hash', 40);
            $table->string('check');
            $table->string('severity', 20);
            $table->text('message');
            $table->text('suggestion')->nullable();
            $table->boolean('auto_fixable')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['locale', 'key_hash']);
            $table->index(['locale', 'severity']);
            $table->index('check');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_validation_issues');
    }
};
