<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('action')->index();
            $table->string('locale', 16)->nullable();
            $table->text('translation_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_activity_logs');
    }
};
