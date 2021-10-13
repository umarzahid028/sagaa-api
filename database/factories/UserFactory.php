<?php

namespace Database\Factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $profession = [
            'Marketing Manager',
            'Investment Banker',
            'Paramedic',
            'Patrol Officer',
            'Mathematician',
            'Dental Hygienist',
            'Surveyor',
            'Sports Coach',
            'Designer',
            'Actuary',
            'Teacher Assistant',
            'Statistician',
            'Anthropologist',
            'Respiratory Therapist'
        ];
        $gender = $this->faker->randomElement(['Male', 'Female']);
        $vaccinated = $this->faker->randomElement(['Yes', 'No']);

        return [
            'name' => $this->faker->name($gender),
            'location' => $this->faker->address,
            'latitude' => $this->faker->latitude($min = 25.942122, $max = 27.798244),
            'longitude' => $this->faker->longitude($min = -80.26992, $max = -82.798462),
            'gender' => $gender,
            'profession' => $profession[rand(0,13)],
            'date_of_birth' =>  $this->faker->date($format = 'Y-m-d', $max = 'now'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber,
            'instagram' => 'https://instagram.com/'.str_replace(' ', '_', strtolower($this->faker->name($gender))),
            'otp' =>  $otp = rand(1000000, 9999999),
            'otp_type' => 1,
            'otp_validity' => Carbon::now(),
            'otp_is_used' => 1,
            'vaccinated' => $vaccinated,
            'profile' => 'profile_1629390028.png',
            'email_verified_at' => now(),
            //'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
