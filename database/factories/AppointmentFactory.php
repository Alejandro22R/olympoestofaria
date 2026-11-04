<?php

namespace Database\Factories;

use App\Domain\Appointments\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'scheduled_at' => now()->addDays(3)->setTime(10, 0),
            'status' => AppointmentStatus::Scheduled,
        ];
    }
}
