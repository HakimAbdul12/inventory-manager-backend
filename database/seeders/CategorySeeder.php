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
        $carsFields = [
            [
                'key' => 'make',
                'label' => 'Make',
                'type' => 'string',
                'required' => true,
                'description' => 'Vehicle manufacturer',
                'placeholder' => 'e.g., Toyota, Honda, BMW',
                'other_names' => ['manufacturer', 'brand'],
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
                'other_names' => ['cpo', 'certified'],
            ],
            [
                'key' => 'mileage',
                'label' => 'Mileage',
                'type' => 'number',
                'required' => false,
                'min' => 0,
                'description' => 'Total miles driven',
                'placeholder' => 'e.g., 35000',
                'other_names' => ['odometer', 'miles'],
            ],
            [
                'key' => 'color',
                'label' => 'Exterior Color',
                'type' => 'string',
                'required' => false,
                'placeholder' => 'e.g., Black, Silver, White',
                'description' => 'Vehicle exterior color',
                'other_names' => ['exterior_color', 'colour', 'paint'],
            ],
            [
                'key' => 'interiorColor',
                'label' => 'Interior Color',
                'type' => 'string',
                'required' => false,
                'placeholder' => 'e.g., Black Leather, Beige Cloth',
                'description' => 'Vehicle interior color and material',
                'other_names' => ['interior', 'trim'],
            ],
            [
                'key' => 'bodyType',
                'label' => 'Body Type',
                'type' => 'select',
                'required' => false,
                'options' => ['Sedan', 'SUV', 'Truck', 'Coupe', 'Convertible', 'Hatchback', 'Van', 'Wagon'],
                'description' => 'Style of the vehicle body',
                'other_names' => ['body_style', 'class'],
            ],
            [
                'key' => 'doors',
                'label' => 'Doors',
                'type' => 'number',
                'required' => false,
                'description' => 'Number of doors',
                'placeholder' => 'e.g., 4',
                'other_names' => ['door_count'],
            ],
            [
                'key' => 'seats',
                'label' => 'Seats',
                'type' => 'number',
                'required' => false,
                'description' => 'Number of seats',
                'placeholder' => 'e.g., 5',
                'other_names' => ['seating_capacity', 'passengers'],
            ],
            [
                'key' => 'fuelType',
                'label' => 'Fuel Type',
                'type' => 'select',
                'required' => false,
                'options' => ['Gasoline', 'Diesel', 'Electric', 'Hybrid', 'Plug-in Hybrid'],
                'description' => 'Type of fuel the vehicle uses',
                'other_names' => ['fuel'],
            ],
            [
                'key' => 'fuelEconomy',
                'label' => 'Fuel Economy',
                'type' => 'string',
                'required' => false,
                'description' => 'MPG or EPA rating',
                'placeholder' => 'e.g., 25 City / 32 Hwy',
                'other_names' => ['mpg', 'consumption'],
            ],
            [
                'key' => 'transmission',
                'label' => 'Transmission',
                'type' => 'select',
                'required' => false,
                'options' => ['Automatic', 'Manual', 'CVT', 'Dual-Clutch'],
                'description' => 'Type of transmission',
                'other_names' => ['trans', 'gearbox'],
            ],
            [
                'key' => 'drivetrain',
                'label' => 'Drivetrain',
                'type' => 'select',
                'required' => false,
                'options' => ['FWD', 'RWD', 'AWD', '4WD'],
                'description' => 'Vehicle drivetrain type',
                'other_names' => ['drive_type', 'driven_wheels'],
            ],
            [
                'key' => 'engine',
                'label' => 'Engine',
                'type' => 'string',
                'required' => false,
                'placeholder' => 'e.g., 2.5L 4-Cylinder',
                'description' => 'Engine specifications',
                'other_names' => ['motor', 'displacement'],
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
                'other_names' => ['cost', 'asking_price', 'msrp'],
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
                'other_names' => ['vehicle_identification_number', 'serial_number'],
            ],
            [
                'key' => 'description',
                'label' => 'Description',
                'type' => 'text',
                'required' => false,
                'generated' => true,
                'description' => 'AI-generated detailed description of the vehicle',
                'other_names' => ['notes', 'comments', 'details'],
            ],
            [
                'key' => 'highlights',
                'label' => 'Key Highlights',
                'type' => 'array',
                'required' => false,
                'generated' => true,
                'description' => 'Key selling points of the vehicle',
                'other_names' => ['features', 'selling_points'],
            ],
            [
                'key' => 'additionalFeatures',
                'label' => 'Additional Features',
                'type' => 'array',
                'required' => false,
                'generated' => true,
                'description' => 'List of notable features and options',
                'other_names' => ['options', 'equipment'],
            ],
        ];

        $this->updateCategoryWithFields('cars', 'Cars', $carsFields, [
            'description' => 'Automobile inventory listings for cars, trucks, and SUVs',
            'icon' => 'car',
            'prompt_template' => 'car_inventory',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function updateCategoryWithFields(string $slug, string $name, array $newFields, array $metadata)
    {
        $category = Category::firstOrNew(['slug' => $slug]);

        // Update metadata
        $category->name = $name;
        foreach ($metadata as $key => $value) {
            $category->{$key} = $value;
        }

        // Merge fields
        $existingFields = collect($category->fields ?? [])->keyBy('key');
        $mergedFields = collect();

        // 1. Add/Update defined fields
        foreach ($newFields as $field) {
            // If field exists, we update it but preserve keys not in the definition if necessary?
            // For now, we allow the seeder to be the source of truth for these standard fields.
            // But we can ensure we don't lose any other custom properties if added later?
            // Actually, for standard fields, we probably want to enforce the schema.

            // However, if we simply overwrite, we might lose user customizations if they edited these fields via UI.
            // But usually, seeder is for "system" state.
            // Let's assume we want to update the definition with our new one.
            $mergedFields->put($field['key'], $field);
        }

        // 2. Preserve user-added fields that are NOT in the newFields set
        $existingFields->each(function ($field, $key) use ($mergedFields) {
            if (!$mergedFields->has($key)) {
                $mergedFields->put($key, $field);
            }
        });

        $category->fields = $mergedFields->values()->toArray();
        $category->save();
    }
}
