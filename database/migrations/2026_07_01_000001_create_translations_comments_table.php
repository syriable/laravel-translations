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

        Schema::create($prefix.'comments', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('message_id')->constrained($prefix.'messages')->cascadeOnDelete();
            $table->string('member_id')->nullable()->index();
            $table->text('body');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix().'comments');
    }

    public function getConnection(): ?string
    {
        return config('translations.database.connection');
    }
};
