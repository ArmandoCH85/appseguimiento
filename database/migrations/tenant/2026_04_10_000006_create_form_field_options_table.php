<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_field_id')->constrained('form_fields')->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_field_options');
    }
};
