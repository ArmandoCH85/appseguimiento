<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gps_tracks')) {
            return;
        }

        if (! $this->indexExists('gps_tracks', 'gps_tracks_device_time_idx')) {
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

        if ($this->indexExists('gps_tracks', 'gps_tracks_device_time_idx')) {
            return;
        }

        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->index(['device_id', 'time'], 'gps_tracks_device_time_idx');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'select 1 from information_schema.statistics where table_schema = ? and table_name = ? and index_name = ? limit 1',
            [$database, $table, $index],
        );

        return $result !== null;
    }
};
