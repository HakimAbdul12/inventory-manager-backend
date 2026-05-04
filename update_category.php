<?php
$c = App\Models\Category::where('slug', 'cars')->first();
if ($c) {
    $fields = $c->fields;
    $keys = array_column($fields, 'key');
    if(!in_array('trim', $keys)) {
        $fields[] = ['key' => 'trim', 'label' => 'Trim', 'type' => 'string', 'required' => false, 'description' => 'Vehicle trim package', 'placeholder' => 'e.g., XSE, EX-L, Touring'];
    }
    if(!in_array('cabType', $keys)) {
        $fields[] = ['key' => 'cabType', 'label' => 'Cab Type', 'type' => 'string', 'required' => false, 'description' => 'Truck cab type', 'placeholder' => 'e.g., Crew Cab'];
    }
    if(!in_array('gvwr', $keys)) {
        $fields[] = ['key' => 'gvwr', 'label' => 'GVWR', 'type' => 'string', 'required' => false, 'description' => 'Gross Vehicle Weight Rating'];
    }
    $c->fields = $fields;
    $c->save();
    echo "Done!\n";
} else {
    echo "Cars category not found.\n";
}
