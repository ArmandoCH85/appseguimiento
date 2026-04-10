<?php

declare(strict_types=1);

use App\Enums\FormFieldType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_id')->constrained('forms')->cascadeOnDelete();
            $table->enum('type', array_map(
                static fn (FormFieldType $type): string => $type->value,
                FormFieldType::cases(),
            ));
            $table->string('label');
            $table->string('name');
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['form_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
