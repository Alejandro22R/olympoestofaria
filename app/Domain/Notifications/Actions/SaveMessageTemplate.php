<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Notifications\Customer\MessageEvent;
use App\Domain\Notifications\Customer\TemplateRenderer;
use App\Models\MessageTemplate;
use Illuminate\Validation\ValidationException;

/**
 * Edición de plantillas: solo variables que el evento ofrece, y en WhatsApp la lista de
 * parámetros (orden {{1}}, {{2}}…) también debe usar variables válidas.
 */
class SaveMessageTemplate
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(MessageTemplate $template, array $data): MessageTemplate
    {
        $event = MessageEvent::from($template->event);
        $errors = [];

        foreach (['subject', 'body'] as $field) {
            $unknown = TemplateRenderer::unknownVariables((string) ($data[$field] ?? ''), $event);
            if ($unknown !== []) {
                $errors[$field] = __('communication.templates.errors.unknown_variables', ['vars' => implode(', ', $unknown)]);
            }
        }

        $params = array_values(array_filter(array_map('trim', (array) ($data['whatsapp_params'] ?? []))));
        if ($template->channel === 'whatsapp' && ($bad = array_diff($params, $event->variables())) !== []) {
            $errors['whatsapp_params'] = __('communication.templates.errors.unknown_variables', ['vars' => implode(', ', $bad)]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $template->fill([
            'subject' => $template->channel === 'mail' ? trim((string) ($data['subject'] ?? '')) : null,
            'body' => trim((string) $data['body']),
            'whatsapp_template' => $template->channel === 'whatsapp' ? trim((string) ($data['whatsapp_template'] ?? '')) ?: null : null,
            'whatsapp_language' => $template->channel === 'whatsapp' ? trim((string) ($data['whatsapp_language'] ?? '')) ?: null : null,
            'whatsapp_params' => $template->channel === 'whatsapp' ? $params : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ])->save();

        activity('settings')->causedBy(auth()->user())->performedOn($template)->event('updated')
            ->withProperties(['group' => 'templates', 'event' => $template->event, 'channel' => $template->channel, 'locale' => $template->locale])
            ->log('templates.updated');

        return $template;
    }
}
