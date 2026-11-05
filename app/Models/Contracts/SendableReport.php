<?php

namespace App\Models\Contracts;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Models\Customer;
use App\Models\ReportSend;
use App\Models\Vehicle;

/**
 * Documento (acta) que se envía al cliente por correo o WhatsApp con registro de envío.
 */
interface SendableReport
{
    public function reportCustomer(): Customer;

    public function reportVehicle(): Vehicle;

    public function reportDisk(): ?string;

    public function reportPath(): ?string;

    /** "inspection" | "delivery": prefijo de las traducciones del mensaje. */
    public function reportKind(): string;

    public function customerReportUrl(int $days): string;

    /** @return mixed */
    public function getKey();

    /**
     * Registra un envío (canal, destinatario, quién y cuándo).
     *
     * @param  array{channel: ReportChannel, recipient: string, sent_by: ?int, sent_at: \DateTimeInterface}  $attributes
     */
    public function recordSend(array $attributes): ReportSend;
}
