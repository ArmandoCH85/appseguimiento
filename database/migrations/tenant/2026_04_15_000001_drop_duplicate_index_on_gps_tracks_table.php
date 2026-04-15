<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gps_tracks')) {
            return;
        }

        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->dropIndex('gps_tracks_device_time_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('gps_tracks')) {
            return;
        }

        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->index(['device_id', 'time'], 'gps_tracks_device_time_idx');
        });
    }
};
