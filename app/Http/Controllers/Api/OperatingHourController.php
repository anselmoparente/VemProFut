<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperatingHoursUpsertRequest;
use App\Models\OperatingHour;
use App\Models\SportsCenter;
use Illuminate\Http\Request;

class OperatingHourController extends Controller
{
    public function index(Request $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        return $sportsCenter->operatingHours()
            ->orderBy('day_of_week')
            ->get();
    }

    public function upsertBatch(OperatingHoursUpsertRequest $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        $items = $request->validated()['items'];

        $byDay = [];
        foreach ($items as $row) {
            $byDay[(int) $row['day_of_week']] = $row;
        }

        foreach ($byDay as $day => $row) {
            OperatingHour::updateOrCreate(
                [
                    'sports_center_id' => $sportsCenter->id,
                    'day_of_week' => $day,
                ],
                [
                    'open_time' => $row['open_time'],
                    'close_time' => $row['close_time'],
                ]
            );
        }

        return $sportsCenter->operatingHours()
            ->orderBy('day_of_week')
            ->get();
    }

    private function assertOwner(Request $request, SportsCenter $sportsCenter): void
    {
        if ((int) $sportsCenter->owner_id !== (int) $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
