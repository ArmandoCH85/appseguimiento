<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GpsTrackService
{
    /**
     * Valida un batch de puntos GPS y retorna los datos listos para persistir.
     *
     * @param string $imei IMEI del dispositivo
     * @param array $points Array de puntos con latitud, longitud, time, elapsedRealtimeMillis, accuracy
     * @return array{device: Device, validPoints: array}
     */
    public function validateBatch(string $imei, array $points): array
    {
        $device = Device::where('imei', $imei)->first();

        if (! $device) {
            throw ValidationException::withMessages([
                'imei' => ['Dispositivo no encontrado. Registrá el IMEI en el sistema primero.'],
            ]);
        }

        $validPoints = [];

        foreach ($points as $index => $point) {
            $validator = Validator::make($point, [
                'latitud' => 'required|numeric',
                'longitud' => 'required|numeric',
                'time' => 'required|numeric|min:0',
                'elapsedRealtimeMillis' => 'required|numeric|min:0',
                'accuracy' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    "points.{$index}" => $validator->errors()->all(),
                ]);
            }

            $validPoints[] = [
                'id' => Str::ulid()->toBase32(),
                'device_id' => $device->id,
                'latitude' => (float) $point['latitud'],
                'longitude' => (float) $point['longitud'],
                'time' => (int) $point['time'],
                'elapsed_realtime_millis' => (int) $point['elapsedRealtimeMillis'],
                'accuracy' => (int) $point['accuracy'],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        if (empty($validPoints)) {
            throw ValidationException::withMessages([
                'points' => ['El batch de puntos está vacío.'],
            ]);
        }

        return [
            'device' => $device,
            'validPoints' => $validPoints,
        ];
    }

    /**
     * Inserta un batch de puntos GPS en la base de datos.
     *
     * @param array $validPoints Array de datos ya validados
     * @return int Cantidad de puntos insertados
     */
    public function insertBatch(array $validPoints): int
    {
        if (empty($validPoints)) {
            return 0;
        }

        GpsTrack::insert($validPoints);

        return count($validPoints);
    }
}
