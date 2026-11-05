<?php

namespace App\Domain\Privacy;

use App\Domain\Privacy\Actions\AnonymizeCustomer;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Settings\PrivacySettings;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Política de retención (Ajustes > Privacidad), diaria:
 * - borra las solicitudes perdidas tras N meses;
 * - anonimiza a los clientes sin ninguna actividad en N meses (nunca si tienen algo abierto).
 */
class ApplyRetention
{
    public function __construct(
        private readonly PrivacySettings $settings,
        private readonly AnonymizeCustomer $anonymize,
    ) {}

    /**
     * @return array{leads_deleted: int, customers_anonymized: int}
     */
    public function execute(): array
    {
        $leads = 0;
        if ($this->settings->lost_lead_months > 0) {
            $leads = Project::query()->where('status', ProjectStatus::Lost)
                ->where('updated_at', '<', now()->subMonths($this->settings->lost_lead_months))
                ->whereNotIn('id', Quote::query()->whereNotNull('project_id')->select('project_id'))->delete();
        }

        $anonymized = 0;
        if ($this->settings->inactive_customer_months > 0) {
            $cutoff = now()->subMonths($this->settings->inactive_customer_months);

            Customer::query()->whereNull('anonymized_at')->where('updated_at', '<', $cutoff)
                ->each(function (Customer $customer) use ($cutoff, &$anonymized): void {
                    if ($this->lastActivity($customer)?->gte($cutoff)) {
                        return;
                    }
                    try {
                        $this->anonymize->execute($customer);
                        $anonymized++;
                    } catch (ValidationException) {
                        // tiene algo abierto: se revisa en la próxima ejecución
                    }
                });
        }

        return ['leads_deleted' => (int) $leads, 'customers_anonymized' => $anonymized];
    }

    private function lastActivity(Customer $customer): ?Carbon
    {
        $vehicleIds = $customer->vehicles()->withTrashed()->pluck('id');
        $dates = array_filter([
            Project::query()->where('customer_id', $customer->id)->max('updated_at'),
            Quote::query()->where('customer_id', $customer->id)->max('updated_at'),
            Payment::query()->where('customer_id', $customer->id)->max('updated_at'),
            Appointment::query()->whereIn('vehicle_id', $vehicleIds)->max('updated_at'),
            WorkOrder::query()->whereIn('vehicle_id', $vehicleIds)->max('updated_at'),
        ]);

        return $dates === [] ? null : Carbon::parse(max($dates));
    }
}
