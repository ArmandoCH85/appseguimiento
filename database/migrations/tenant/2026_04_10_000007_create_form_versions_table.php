<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_id')->constrained('forms')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->json('schema_snapshot');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_versions');
    }
};
