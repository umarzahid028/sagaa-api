<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCuisine;
use App\Models\UserInterest;
use Database\Factories\UserInterestFactory;
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
         User::factory(500)->create()->each(function ($user){

             UserInterest::factory(rand(3,4))->create([
                'user_id'=> $user->id,

            ]);
             UserCuisine::factory(rand(1,4))->create([
                 'user_id'=> $user->id,

             ]);
         });


    }
}
