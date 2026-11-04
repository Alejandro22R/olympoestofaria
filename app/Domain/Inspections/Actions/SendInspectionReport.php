<?php

namespace App\Domain\Inspections\Actions;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Reports\SendReport;
use App\Models\Inspection;
use App\Models\ReportSend;
use App\Models\User;

/**
 * Envía el acta de recepción (genera el PDF si aún no existe).
 */
class SendInspectionReport
{
    public function __construct(
        private readonly SendReport $send,
        private readonly GenerateInspectionReport $generate,
    ) {}

    /**
     * @return array{send: ReportSend, url: ?string}
     */
    public function execute(Inspection $inspection, ReportChannel $channel, ?User $sender = null): array
    {
        if (! $inspection->hasReport()) {
            $this->generate->execute($inspection);
        }

        return $this->send->execute($inspection, $channel, $sender);
    }
}
