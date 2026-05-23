<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_comments', function (Blueprint $table): void {
            $table->id();
            $table->string('locale', 16);
            $table->text('translation_key');
            $table->string('key_hash', 40);
            $table->string('user_id')->nullable();
            $table->text('body');
            $table->string('type', 30)->default('comment');
            $table->timestamps();

            $table->index(['locale', 'key_hash']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_comments');
    }
};
