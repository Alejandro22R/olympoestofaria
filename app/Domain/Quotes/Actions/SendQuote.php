<?php

namespace App\Domain\Quotes\Actions;

use App\Domain\Inspections\Enums\ReportChannel;
use App\Domain\Projects\Actions\ChangeProjectStatus;
use App\Domain\Projects\Enums\ProjectStatus;
use App\Domain\Quotes\Enums\QuoteStatus;
use App\Domain\Reports\SendReport;
use App\Models\Quote;
use App\Models\ReportSend;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Envía la cotización al cliente (correo con PDF o WhatsApp con enlace a la página de aprobación).
 * Al primer envío fija el estado "enviada" y genera el PDF; reenviar solo registra otro envío.
 */
class SendQuote
{
    public function __construct(
        private readonly GenerateQuotePdf $pdf,
        private readonly SendReport $send,
        private readonly ChangeProjectStatus $projectStatus,
    ) {}

    /**
     * @return array{send: ReportSend, url: ?string}
     */
    public function execute(Quote $quote, ReportChannel $channel, ?User $user = null): array
    {
        if ($quote->status === QuoteStatus::Draft) {
            $this->assertSendable($quote);

            DB::transaction(function () use ($quote): void {
                $quote->status = QuoteStatus::Sent;
                $quote->sent_at = now();
                $quote->save();

                if ($quote->project !== null && $quote->project->status->canTransitionTo(ProjectStatus::Quoted)) {
                    $this->projectStatus->execute($quote->project, ProjectStatus::Quoted);
                }
            });
            $this->pdf->execute($quote);
        } elseif ($quote->status !== QuoteStatus::Sent) {
            throw ValidationException::withMessages(['channel' => __('quotes.errors.not_sendable', ['status' => $quote->status->getLabel()])]);
        } elseif ($quote->report_path === null) {
            $this->pdf->execute($quote);
        }

        return $this->send->execute($quote, $channel, $user);
    }

    private function assertSendable(Quote $quote): void
    {
        if ($quote->items()->count() === 0 || $quote->total_cents <= 0) {
            throw ValidationException::withMessages(['items' => __('quotes.errors.empty')]);
        }
        if ($quote->valid_until === null || $quote->valid_until->endOfDay()->isPast()) {
            throw ValidationException::withMessages(['valid_until' => __('quotes.errors.validity')]);
        }
    }
}
