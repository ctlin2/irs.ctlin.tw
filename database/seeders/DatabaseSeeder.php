<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Harishdurga\LaravelQuiz\Models\QuestionType;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Model::unguard();
        $seeders = array ('Database\Seeders\QuestionTypeSeeder', 'Database\Seeders\InsertSeeder');

        foreach ($seeders as $seeder)
        {
            $this->call($seeder);
        }

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
