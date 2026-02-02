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
        Category::updateOrCreate(
            ['slug' => 'cars'],
            [
                'name' => 'Cars',
                'description' => 'Automobile inventory listings for cars, trucks, and SUVs',
                'icon' => 'car',
                'prompt_template' => 'car_inventory',
                'is_active' => true,
                'sort_order' => 1,
                'fields' => [
                    [
                        'key' => 'make',
                        'label' => 'Make',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Vehicle manufacturer',
                        'placeholder' => 'e.g., Toyota, Honda, BMW',
                    ],
                    [
                        'key' => 'model',
                        'label' => 'Model',
                        'type' => 'string',
                        'required' => true,
                        'description' => 'Vehicle model name',
                        'placeholder' => 'e.g., Camry, Accord, 3 Series',
                    ],
                    [
                        'key' => 'year',
                        'label' => 'Year',
                        'type' => 'number',
                        'required' => true,
                        'min' => 1900,
                        'max' => 2030,
                        'description' => 'Manufacturing year',
                        'placeholder' => 'e.g., 2021',
                    ],
                    [
                        'key' => 'condition',
                        'label' => 'Condition',
                        'type' => 'select',
                        'required' => true,
                        'options' => ['New', 'Used', 'Certified Pre-Owned'],
                        'description' => 'Vehicle condition',
                    ],
                    [
                        'key' => 'isCertifiedPreOwned',
                        'label' => 'Certified Pre-Owned',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                        'description' => 'Whether the vehicle is certified pre-owned',
                    ],
                    [
                        'key' => 'mileage',
                        'label' => 'Mileage',
                        'type' => 'number',
                        'required' => false,
                        'min' => 0,
                        'description' => 'Total miles driven',
                        'placeholder' => 'e.g., 35000',
                    ],
                    [
                        'key' => 'color',
                        'label' => 'Exterior Color',
                        'type' => 'string',
                        'required' => false,
                        'placeholder' => 'e.g., Black, Silver, White',
                        'description' => 'Vehicle exterior color',
                    ],
                    [
                        'key' => 'interiorColor',
                        'label' => 'Interior Color',
                        'type' => 'string',
                        'required' => false,
                        'placeholder' => 'e.g., Black Leather, Beige Cloth',
                        'description' => 'Vehicle interior color and material',
                    ],
                    [
                        'key' => 'fuelType',
                        'label' => 'Fuel Type',
                        'type' => 'select',
                        'required' => false,
                        'options' => ['Gasoline', 'Diesel', 'Electric', 'Hybrid', 'Plug-in Hybrid'],
                        'description' => 'Type of fuel the vehicle uses',
                    ],
                    [
                        'key' => 'transmission',
                        'label' => 'Transmission',
                        'type' => 'select',
                        'required' => false,
                        'options' => ['Automatic', 'Manual', 'CVT', 'Dual-Clutch'],
                        'description' => 'Type of transmission',
                    ],
                    [
                        'key' => 'drivetrain',
                        'label' => 'Drivetrain',
                        'type' => 'select',
                        'required' => false,
                        'options' => ['FWD', 'RWD', 'AWD', '4WD'],
                        'description' => 'Vehicle drivetrain type',
                    ],
                    [
                        'key' => 'engine',
                        'label' => 'Engine',
                        'type' => 'string',
                        'required' => false,
                        'placeholder' => 'e.g., 2.5L 4-Cylinder',
                        'description' => 'Engine specifications',
                    ],
                    [
                        'key' => 'price',
                        'label' => 'Price',
                        'type' => 'number',
                        'required' => false,
                        'min' => 0,
                        'description' => 'Listing price in USD',
                        'placeholder' => 'e.g., 25000',
                        'generated' => true,
                    ],
                    [
                        'key' => 'location',
                        'label' => 'Location',
                        'type' => 'string',
                        'required' => false,
                        'placeholder' => 'City, State',
                        'description' => 'Where the vehicle is located',
                    ],
                    [
                        'key' => 'vin',
                        'label' => 'VIN',
                        'type' => 'string',
                        'required' => false,
                        'placeholder' => '17-character VIN',
                        'description' => 'Vehicle Identification Number',
                    ],
                    [
                        'key' => 'description',
                        'label' => 'Description',
                        'type' => 'text',
                        'required' => false,
                        'generated' => true,
                        'description' => 'AI-generated detailed description of the vehicle',
                    ],
                    [
                        'key' => 'highlights',
                        'label' => 'Key Highlights',
                        'type' => 'array',
                        'required' => false,
                        'generated' => true,
                        'description' => 'Key selling points of the vehicle',
                    ],
                    [
                        'key' => 'additionalFeatures',
                        'label' => 'Additional Features',
                        'type' => 'array',
                        'required' => false,
                        'generated' => true,
                        'description' => 'List of notable features and options',
                    ],
                ],
            ]
        );

        // Future categories can be added here
        // Example:
        // Category::updateOrCreate(
        //     ['slug' => 'real-estate'],
        //     [
        //         'name' => 'Real Estate',
        //         'description' => 'Property listings',
        //         ...
        //     ]
        // );
    }
}
