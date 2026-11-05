<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso interno para el personal: campana del panel (database) + aviso instantáneo (broadcast).
 * El texto se traduce al idioma de cada destinatario y no lleva datos sensibles.
 */
class StaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|int>  $params
     */
    public function __construct(
        public string $type,
        public string $titleKey,
        public array $params = [],
        public ?string $bodyKey = null,
        public ?string $url = null,
        public string $color = 'info',
        public string $icon = 'heroicon-o-bell',
        public ?string $alertKey = null,
        public bool $forceMail = false,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $mail = $this->forceMail || (bool) ($prefs['mail'][self::category($this->type)] ?? false);

        return $mail ? ['database', 'broadcast', 'mail'] : ['database', 'broadcast'];
    }

    /** Categorías de las preferencias de correo del personal (Mi perfil). */
    public const CATEGORIES = ['leads', 'payments', 'agenda', 'production', 'inventory', 'reviews'];

    public static function category(string $type): string
    {
        return match (true) {
            str_starts_with($type, 'lead_'), str_starts_with($type, 'quote_') => 'leads',
            str_starts_with($type, 'payment_'), in_array($type, ['deposit_overdue', 'balance_overdue'], true) => 'payments',
            str_starts_with($type, 'appointment_'), $type === 'unconfirmed_appointment' => 'agenda',
            str_starts_with($type, 'work_order_') => 'production',
            $type === 'low_stock' => 'inventory',
            $type === 'review' => 'reviews',
            default => 'system',
        };
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject(__($this->titleKey, $this->params))->line(__($this->titleKey, $this->params));
        if ($this->bodyKey !== null) {
            $mail->line(__($this->bodyKey, $this->params));
        }

        return $this->url !== null ? $mail->action(__('notifications.open'), $this->url) : $mail;
    }

    public function databaseType(User $notifiable): string
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(User $notifiable): array
    {
        return [...$this->filament()->getDatabaseMessage(), 'alert_key' => $this->alertKey];
    }

    public function toBroadcast(User $notifiable): BroadcastMessage
    {
        return $this->filament()->getBroadcastMessage();
    }

    private function filament(): FilamentNotification
    {
        $notification = FilamentNotification::make()
            ->title(__($this->titleKey, $this->params))
            ->icon($this->icon)
            ->iconColor($this->color);

        if ($this->bodyKey !== null) {
            $notification->body(__($this->bodyKey, $this->params));
        }

        if ($this->url !== null) {
            $notification->actions([
                Action::make('open')->label(__('notifications.open'))->url($this->url)->markAsRead(),
            ]);
        }

        return $notification;
    }
}
