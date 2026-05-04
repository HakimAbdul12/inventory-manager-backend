<?php
$c = App\Models\Category::where('slug', 'cars')->first();
if ($c) {
    $fields = $c->fields;
    foreach ($fields as &$field) {
        if ($field['key'] === 'bodyType' && isset($field['options'])) {
            if (!in_array('Motorcycle', $field['options'])) {
                $field['options'][] = 'Motorcycle';
            }
            if (!in_array('Other', $field['options'])) {
                $field['options'][] = 'Other';
            }
        }
    }
    $c->fields = $fields;
    $c->save();
    echo "Updated bodyType options!\n";
} else {
    echo "Cars category not found.\n";
}
