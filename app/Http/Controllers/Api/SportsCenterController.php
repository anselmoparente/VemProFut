<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SportsCenterStoreRequest;
use App\Http\Requests\SportsCenterUpdateRequest;
use App\Models\SportsCenter;
use Illuminate\Http\Request;

class SportsCenterController extends Controller
{
    public function index(Request $request)
    {
        return SportsCenter::query()
            ->where('owner_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function store(SportsCenterStoreRequest $request)
    {
        $sportsCenter = SportsCenter::create([
            'owner_id' => $request->user()->id,
            ...$request->validated(),
        ]);

        return response()->json($sportsCenter, 201);
    }

    public function show(Request $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        return $sportsCenter;
    }

    public function update(SportsCenterUpdateRequest $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        $sportsCenter->update($request->validated());

        return $sportsCenter;
    }

    public function destroy(Request $request, SportsCenter $sportsCenter)
    {
        $this->assertOwner($request, $sportsCenter);

        $sportsCenter->delete();

        return response()->json(['message' => 'Sports center removido.']);
    }

    private function assertOwner(Request $request, SportsCenter $sportsCenter): void
    {
        if ((int) $sportsCenter->owner_id !== (int) $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
