<?php

namespace Database\Factories;

use App\Models\ProfileType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProfileType> */
class ProfileTypeFactory extends Factory
{
    protected $model = ProfileType::class;

    public function definition(): array
    {
        $label = $this->faker->unique()->word();
        return [
            'code' => Str::slug($label, '_'),
            'label' => Str::title($label),
            'description' => $this->faker->sentence(),
            'institution_label' => 'Instansi',
            'major_label' => 'Jurusan',
            'level_label' => 'Tingkat',
            'show_institution' => true,
            'show_major' => true,
            'show_level' => true,
            'requires_institution' => false,
            'requires_major' => false,
            'requires_level' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
