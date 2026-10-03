<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\LifeStage;
use App\Enums\MemberStatus;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(Gender::cases());
        $first = $gender === Gender::Male ? fake()->firstNameMale() : fake()->firstNameFemale();

        return [
            'full_name' => $first.' '.fake()->lastName(),
            'nickname' => $first,
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-45 years', '-16 years')->format('Y-m-d'),
            'whatsapp' => '628'.fake()->unique()->numerify('##########'),
            'email' => fake()->unique()->safeEmail(),
            'area' => fake()->randomElement(['Bekasi Barat', 'Bekasi Timur', 'Bekasi Selatan', 'Bekasi Utara', 'Cikarang', 'Tambun', 'Jatiasih', 'Pondok Gede']),
            'occupation' => fake()->jobTitle(),
            'life_stage' => fake()->randomElement(LifeStage::cases()),
            'member_status' => MemberStatus::Member,
            'join_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'first_visit_date' => fake()->dateTimeBetween('-4 years', '-2 months')->format('Y-m-d'),
        ];
    }

    public function newcomer(): static
    {
        return $this->state(fn () => [
            'member_status' => MemberStatus::Newcomer,
            'join_date' => null,
            'first_visit_date' => fake()->dateTimeBetween('-3 weeks', 'now')->format('Y-m-d'),
        ]);
    }
}
