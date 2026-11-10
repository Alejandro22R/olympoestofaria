<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Tours\Actions\CompleteTour;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * El navegador avisa que la persona terminó o cerró un tutorial.
 */
class CompleteTourController
{
    public function __invoke(Request $request, string $tour, CompleteTour $complete): JsonResponse
    {
        $complete->execute($request->user(), $tour);

        return response()->json(['ok' => true]);
    }
}
