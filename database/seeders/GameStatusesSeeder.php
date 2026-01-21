<?php

namespace Database\Seeders;

use App\Models\GameStatus;
use Illuminate\Database\Seeder;

class GameStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        GameStatus::create([
            'id' => 1,
            'code' => 'open',
            'description' => 'Aberto para novos jogadores',
        ]);

        GameStatus::create([
            'id' => 2,
            'code' => 'full',
            'description' => 'Partida com vagas preenchidas',
        ]);

        GameStatus::create([
            'id' => 3,
            'code' => 'finished',
            'description' => 'Partida finalizada',
        ]);

        GameStatus::create([
            'id' => 4,
            'code' => 'canceled',
            'description' => 'Partida cancelada',
        ]);
    }
}
