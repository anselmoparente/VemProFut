<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Field;
use App\Models\Game;
use App\Models\SportsCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) $request->user()->id;

        $sportsCentersCount = SportsCenter::query()
            ->where('owner_id', $userId)
            ->count();

        $fieldsCount = Field::query()
            ->whereHas('sportsCenter', fn($q) => $q->where('owner_id', $userId))
            ->count();

        $today = Carbon::today()->toDateString();
        $to = Carbon::today()->addDays(7)->toDateString();

        $upcomingGamesQuery = Game::query()
            ->with([
                'status:id,code,description',
                'field:id,name,sports_center_id',
                'field.sportsCenter:id,name,city,state,owner_id',
            ])
            ->whereHas('field.sportsCenter', fn($q) => $q->where('owner_id', $userId))
            ->whereDate('game_date', '>=', $today)
            ->whereDate('game_date', '<=', $to)
            ->orderBy('game_date')
            ->orderBy('start_time');

        $upcomingGamesCount = (clone $upcomingGamesQuery)->count();
        $upcomingGames = $upcomingGamesQuery->limit(5)->get();

        return response()->json([
            'sports_centers_count' => $sportsCentersCount,
            'fields_count' => $fieldsCount,
            'upcoming_games_count' => $upcomingGamesCount,
            'upcoming_games' => $upcomingGames,
        ]);
    }
}
