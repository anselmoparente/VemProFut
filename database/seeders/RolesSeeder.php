<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'id' => 1,
            'name' => 'player',
            'description' => 'Usuário que pode jogar e organizar partidas',
        ]);

        Role::create([
            'id' => 2,
            'name' => 'field_owner',
            'description' => 'Usuário responsável por cadastrar e gerenciar centros esportivos e campos',
        ]);
    }
}
