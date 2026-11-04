<?php

namespace Database\Seeders;

use App\Domain\Appointments\Actions\ScheduleAppointment;
use App\Domain\Appointments\Services\AgendaService;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Holiday;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Validation\ValidationException;

/**
 * Datos operativos de demostración (solo desarrollo): clientes, vehículos, un feriado y citas.
 */
class DemoOperationsSeeder extends Seeder
{
    public function run(AgendaService $agenda, ScheduleAppointment $schedule): void
    {
        $branch = Branch::default();

        $customers = [
            ['Ana Souza', '+5511987650001', 'ana@example.com', 'ABC1D23', 'Volkswagen', 'Fusca', 1972],
            ['Bruno Lima', '+5511987650002', 'bruno@example.com', 'BRA-2E19', 'Chevrolet', 'Onix', 2021],
            ['Carla Mendes', '+5511987650003', null, 'QWE-4567', 'Toyota', 'Hilux', 2018],
            ['Diego Fernández', '+34612345678', 'diego@example.com', 'XYZ9A87', 'Fiat', 'Argo', 2022],
        ];

        $vehicles = [];
        foreach ($customers as [$name, $phone, $email, $plate, $brand, $model, $year]) {
            $customer = Customer::query()->firstOrCreate(['phone' => $phone], [
                'name' => $name,
                'email' => $email,
                'branch_id' => $branch?->id,
                'locale' => str_starts_with($phone, '+34') ? 'es' : 'pt_BR',
            ]);

            $vehicles[] = Vehicle::query()->firstOrCreate(['plate' => str_replace('-', '', $plate)], [
                'customer_id' => $customer->id,
                'brand' => $brand,
                'model' => $model,
                'year' => $year,
                'mileage_km' => random_int(5_000, 180_000),
            ]);
        }

        $tz = $agenda->timezone();
        $start = CarbonImmutable::now($tz)->addDays(2)->startOfDay();

        Holiday::query()->firstOrCreate(
            ['branch_id' => null, 'date' => $start->addDays(10)->toDateString()],
            ['name' => 'Fechamento para inventário'],
        );

        $created = 0;
        for ($offset = 0; $offset < 21 && $created < 8; $offset++) {
            $day = $start->addDays($offset);
            if (! $agenda->isWorkingDay($day->toDateString())) {
                continue;
            }

            try {
                $schedule->execute([
                    'vehicle_id' => $vehicles[$created % count($vehicles)]->id,
                    'branch_id' => $branch?->id,
                    'scheduled_at' => $day->setTime(9 + ($created % 3) * 2, 0)->utc(),
                    'promised_delivery_at' => $day->addDays(5)->setTime(17, 0)->utc(),
                ]);
                $created++;
            } catch (ValidationException) {
                // Día lleno o fuera de horario según los ajustes actuales: se salta.
            }
        }
    }
}
