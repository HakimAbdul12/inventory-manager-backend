<?php
$c = App\Models\Category::where('slug', 'cars')->first();
if ($c) {
    $fields = $c->fields;
    $keys = array_column($fields, 'key');
    
    $newFields = [
        ['key' => 'series', 'label' => 'Series', 'type' => 'string', 'required' => false, 'description' => 'Vehicle series or edition'],
        ['key' => 'vehicleType', 'label' => 'Vehicle Type', 'type' => 'string', 'required' => false, 'description' => 'E.g., Passenger Car, Motorcycle, Truck'],
        ['key' => 'curbWeight', 'label' => 'Curb Weight (lbs)', 'type' => 'number', 'required' => false, 'description' => 'Curb weight in pounds'],
        ['key' => 'brakes', 'label' => 'Brake System', 'type' => 'string', 'required' => false, 'description' => 'Type of brake system'],
        ['key' => 'batteryType', 'label' => 'Battery Type', 'type' => 'string', 'required' => false, 'description' => 'Type of EV battery'],
        ['key' => 'batteryCapacity', 'label' => 'Battery Capacity (kWh)', 'type' => 'number', 'required' => false, 'description' => 'Battery capacity in kWh'],
    ];

    foreach ($newFields as $f) {
        if (!in_array($f['key'], $keys)) {
            $fields[] = $f;
        }
    }

    $c->fields = $fields;
    $c->save();
    echo "Done adding new fields!\n";
} else {
    echo "Cars category not found.\n";
}
