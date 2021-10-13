<?php

namespace Database\Factories;

use App\Models\UserCuisine;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserCuisineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserCuisine::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'cuisine_id'=> rand(1,27),

        ];
    }
}
