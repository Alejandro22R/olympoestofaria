<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Users\Actions\SaveStaffUser;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\ActionErrors;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('users.actions.invite'))
                ->using(fn (array $data) => ActionErrors::map(fn () => app(SaveStaffUser::class)->execute($data, actor: auth()->user())))
                ->successNotificationTitle(__('users.notifications.invited')),
        ];
    }
}
