<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Projects\ProjectSummary;
use App\Models\Project;
use Illuminate\Contracts\View\View;

/**
 * Confirmación del proyecto enviado (enlace firmado): resumen, estimado y botón de WhatsApp.
 */
class ProjectSentController
{
    public function __invoke(Project $project, ProjectSummary $summary): View
    {
        return view('portal.project-sent', [
            'project' => $project,
            'lines' => $summary->lines($project),
            'whatsappUrl' => $summary->whatsappUrl($project),
        ]);
    }
}
