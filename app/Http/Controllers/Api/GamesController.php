<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\SportsCenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GamesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'sports_center_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'status_id' => ['nullable', 'integer'],
        ]);

        $userId = (int) $request->user()->id;

        $q = Game::query()
            ->with([
                'status:id,code,description',
                'field:id,name,sports_center_id',
                'field.sportsCenter:id,name,city,state,owner_id',
            ])
            ->whereHas('field.sportsCenter', function ($qq) use ($userId) {
                $qq->where('owner_id', $userId);
            });

        if ($request->filled('sports_center_id')) {
            $scId = (int) $request->input('sports_center_id');
            $q->whereHas('field', fn($qq) => $qq->where('sports_center_id', $scId));
        }

        if ($request->filled('status_id')) {
            $q->where('status_id', (int) $request->input('status_id'));
        }

        if ($request->filled('date_from')) {
            $q->whereDate('game_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('game_date', '<=', $request->input('date_to'));
        }

        $q->orderBy('game_date')->orderBy('start_time');

        return $q->paginate(10);
    }

    public function bySportsCenter(Request $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'status_id' => ['nullable', 'integer'],
        ]);

        $q = Game::query()
            ->with([
                'status:id,code,description',
                'field:id,name,sports_center_id',
            ])
            ->whereHas('field', fn($qq) => $qq->where('sports_center_id', $sportsCenter->id));

        if ($request->filled('status_id')) {
            $q->where('status_id', (int) $request->input('status_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('game_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('game_date', '<=', $request->input('date_to'));
        }

        $q->orderBy('game_date')->orderBy('start_time');

        return $q->paginate(10);
    }

    public function updateStatus(Request $request, Game $game)
    {
        $request->validate([
            'status_id' => ['required', 'integer'],
        ]);

        $userId = (int) $request->user()->id;

        $isOwner = $game->field()
            ->whereHas('sportsCenter', fn($qq) => $qq->where('owner_id', $userId))
            ->exists();

        if (!$isOwner) {
            abort(403, 'Acesso negado.');
        }

        $game->update([
            'status_id' => (int) $request->input('status_id'),
        ]);

        return $game->load([
            'status:id,code,description',
            'field:id,name,sports_center_id',
            'field.sportsCenter:id,name,city,state,owner_id',
        ]);
    }

    private function assertOwner(Request $request, SportsCenter $sportsCenter): void
    {
        if ((int) $sportsCenter->owner_id !== (int) $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
