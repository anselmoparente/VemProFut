<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameStatus;

class GameStatusesController extends Controller
{
    public function index()
    {
        return GameStatus::query()
            ->orderBy('id')
            ->get(['id', 'code', 'description']);
    }
}
