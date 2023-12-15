<?php

namespace Database\Seeders;

use App\Models\Posting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // User::factory(10)->create();
        $tags = Tag::factory(10)->create();
        // Posting::factory(25)->create();

        User::factory(20)->create()->each(function($user) use ($tags) {
            Posting::factory(rand(1, 4))->create([
                'user_id' => $user->id
                ])->each(function($posting) use ($tags) {
                    $posting->tags()->attach($tags->random(2));
                });
            });
    }
}
