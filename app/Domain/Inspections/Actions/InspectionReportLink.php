<?php

namespace App\Domain\Inspections\Actions;

use App\Domain\Settings\CheckinSettings;
use App\Models\Inspection;
use Illuminate\Support\Facades\URL;

/**
 * Enlaces firmados al acta: uno corto para el personal (requiere sesión y permiso)
 * y otro para el cliente, válido los días definidos en Ajustes > Check-in.
 */
class InspectionReportLink
{
    public const STAFF_TTL_MINUTES = 5;

    public function __construct(private readonly CheckinSettings $settings) {}

    public function forStaff(Inspection $inspection): string
    {
        return URL::temporarySignedRoute('admin.inspections.report', now()->addMinutes(self::STAFF_TTL_MINUTES), ['inspection' => $inspection->id]);
    }

    public function forCustomer(Inspection $inspection): string
    {
        return URL::temporarySignedRoute('inspections.report', now()->addDays($this->settings->report_link_days), ['inspection' => $inspection->id]);
    }
}
