<?php

namespace Database\Seeders;

use App\Models\TrainingRecord;
use Illuminate\Database\Seeder;

class TrainingDataSeeder extends Seeder
{
    public function run(): void
    {
        TrainingRecord::query()->delete();

        $rooms = [
            ['key' => 'training-room-01', 'code' => 'TRAIN-01', 'name' => 'Room de práctica 01', 'description' => 'Espacio de prueba para aprender a programar una sesión.', 'status' => 'AVAILABLE'],
            ['key' => 'training-room-02', 'code' => 'TRAIN-02', 'name' => 'Room de práctica 02', 'description' => 'Room de prueba para practicar el control de uso.', 'status' => 'AVAILABLE'],
            ['key' => 'training-room-03', 'code' => 'TRAIN-03', 'name' => 'Room de práctica 03', 'description' => 'Room de prueba con mantenimiento simulado.', 'status' => 'MAINTENANCE'],
        ];

        foreach ($rooms as $room) {
            TrainingRecord::create(['type' => 'room', 'record_key' => $room['key'], 'payload' => $room]);
        }

        foreach ([
            ['key' => 'training-model-ana', 'code' => 'TRAIN-MOD-01', 'name' => 'Ana Modelo de prueba', 'first_name' => 'Ana', 'last_name' => 'Modelo de prueba', 'email' => 'ana.modelo@thevelvet.test', 'phone' => '+57 300 111 1111', 'city' => 'Bogotá', 'country' => 'Colombia', 'birth_date' => '2000-01-15', 'availability' => 'Tiempo completo'],
            ['key' => 'training-model-lina', 'code' => 'TRAIN-MOD-02', 'name' => 'Lina Modelo de prueba', 'first_name' => 'Lina', 'last_name' => 'Modelo de prueba', 'email' => 'lina.modelo@thevelvet.test', 'phone' => '+57 300 222 2222', 'city' => 'Medellín', 'country' => 'Colombia', 'birth_date' => '1999-08-20', 'availability' => 'Medio tiempo'],
        ] as $model) {
            TrainingRecord::create(['type' => 'model', 'record_key' => $model['key'], 'payload' => $model]);
        }

        TrainingRecord::create([
            'type' => 'reservation',
            'record_key' => 'training-reservation-01',
            'payload' => [
                'room_key' => 'training-room-01',
                'model_key' => 'training-model-ana',
                'purpose' => 'Sesión demostrativa',
                'starts_at' => now()->addDay()->setHour(9)->setMinute(0)->toIso8601String(),
                'ends_at' => now()->addDay()->setHour(10)->setMinute(0)->toIso8601String(),
                'status' => 'SCHEDULED',
                'notes' => 'Registro de prueba restaurado por el sistema.',
            ],
        ]);
    }
}
