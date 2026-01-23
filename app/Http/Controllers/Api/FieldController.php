<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FieldStoreRequest;
use App\Http\Requests\FieldUpdateRequest;
use App\Models\Field;
use App\Models\SportsCenter;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    public function index(Request $request, SportsCenter $sportsCenter)
    {
        $this->assertSportsCenterOwner($request, $sportsCenter);

        return $sportsCenter->fields()
            ->orderByDesc('id')
            ->paginate(10);
    }

    public function store(FieldStoreRequest $request, SportsCenter $sportsCenter)
    {
        $this->assertSportsCenterOwner($request, $sportsCenter);

        $field = $sportsCenter->fields()->create($request->validated());

        return response()->json($field, 201);
    }

    public function update(FieldUpdateRequest $request, Field $field)
    {
        $this->assertFieldOwner($request, $field);

        $field->update($request->validated());

        return $field;
    }

    public function destroy(Request $request, Field $field)
    {
        $this->assertFieldOwner($request, $field);

        $field->delete();

        return response()->json(['message' => 'Campo removido.']);
    }

    private function assertSportsCenterOwner(Request $request, SportsCenter $sportsCenter): void
    {
        if ((int) $sportsCenter->owner_id !== (int) $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }

    private function assertFieldOwner(Request $request, Field $field): void
    {
        $sportsCenterId = (int) $field->sports_center_id;

        $sportsCenter = SportsCenter::query()->select(['id', 'owner_id'])->findOrFail($sportsCenterId);

        if ((int) $sportsCenter->owner_id !== (int) $request->user()->id) {
            abort(403, 'Acesso negado.');
        }
    }
}
