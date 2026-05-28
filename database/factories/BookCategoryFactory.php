<?php

namespace Database\Factories;

use App\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<BookCategory> */
class BookCategoryFactory extends Factory
{
    protected $model = BookCategory::class;

    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));
        return [
            'code' => Str::slug($name, '_'),
            'slug' => Str::slug($name),
            'name' => $name,
            'description' => $this->faker->paragraph(),
            'icon_name' => 'menu_book',
            'image_path' => null,
            'image_is_external' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
