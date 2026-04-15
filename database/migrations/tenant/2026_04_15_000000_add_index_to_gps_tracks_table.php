<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

// Index ['device_id', 'time'] already created in 2026_04_13_000002_create_gps_tracks_table.php
// This migration is intentionally a no-op to avoid creating a duplicate index.
return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
