<?php

namespace App\Domain\Settings\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Se emite al guardar ajustes para que las pantallas abiertas usen el valor nuevo.
 * Solo viaja el grupo y los nombres de las claves, nunca los valores.
 */
class SettingsUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<string>  $keys
     */
    public function __construct(public string $group, public array $keys) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('workshop')];
    }

    public function broadcastAs(): string
    {
        return 'settings.updated';
    }
}
