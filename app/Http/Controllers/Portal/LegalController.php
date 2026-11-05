<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Settings\GeneralSettings;
use App\Domain\Settings\PrivacySettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Textos legales del portal (Ajustes > Privacidad y LGPD), en Markdown por idioma.
 * El HTML del texto se descarta: solo Markdown, sin enlaces inseguros.
 */
class LegalController
{
    public function privacy(PrivacySettings $privacy, GeneralSettings $general): View
    {
        return $this->render('privacy_policy', __('privacy.legal.privacy_title'), $privacy, $general);
    }

    public function terms(PrivacySettings $privacy, GeneralSettings $general): View
    {
        return $this->render('terms', __('privacy.legal.terms_title'), $privacy, $general);
    }

    /**
     * @param  'privacy_policy'|'terms'  $document
     */
    private function render(string $document, string $title, PrivacySettings $privacy, GeneralSettings $general): View
    {
        $contact = $privacy->contact_email ?: ($general->email ?: ($general->whatsapp ?: $general->business_name));
        $markdown = strtr($privacy->text($document), ['{oficina}' => $general->business_name, '{contato}' => (string) $contact]);

        return view('portal.legal', [
            'title' => $title,
            'updatedAt' => $privacy->legal_updated_at,
            'html' => new HtmlString(Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false])),
        ]);
    }
}
