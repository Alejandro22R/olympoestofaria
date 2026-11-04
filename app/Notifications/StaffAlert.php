<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
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
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'broadcast'];
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
        return $this->filament()->getDatabaseMessage();
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
