<?php

namespace App\Domain\Projects\Actions;

use App\Domain\Projects\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

/**
 * Seguimiento del lead con transiciones permitidas (auditadas por el activity log).
 */
class ChangeProjectStatus
{
    public function execute(Project $project, ProjectStatus $to, ?string $reason = null): Project
    {
        if ($project->status === $to) {
            return $project;
        }

        if (! $project->status->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('projects.errors.invalid_transition', [
                'from' => $project->status->getLabel(), 'to' => $to->getLabel(),
            ])]);
        }

        if ($to === ProjectStatus::Lost && blank($reason)) {
            throw ValidationException::withMessages(['reason' => __('validation.required', ['attribute' => __('projects.fields.lost_reason')])]);
        }

        $project->status = $to;
        $project->lost_reason = $to === ProjectStatus::Lost ? $reason : $project->lost_reason;
        $project->save();

        return $project;
    }
}
