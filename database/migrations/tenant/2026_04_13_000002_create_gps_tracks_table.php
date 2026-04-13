<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gps_tracks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('device_id')->constrained('devices')->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->bigInteger('time');
            $table->bigInteger('elapsed_realtime_millis');
            $table->integer('accuracy');
            $table->timestamps();

            $table->index(['device_id', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gps_tracks');
    }
};
