<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electricity',
                'default_type' => 'fixed',
                'is_default' => true,
            ],

            [
                'name' => 'Water',
                'default_type' => 'fixed',
                'is_default' => true,
            ],

            [
                'name' => 'Gas',
                'default_type' => 'fixed',
                'is_default' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::forceCreate([
                'name' => $category['name'],
                'default_type' => $category['default_type'],
                'is_default' => $category['is_default'],
            ]);
        }

    }
}
