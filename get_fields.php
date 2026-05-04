<?php
$c = App\Models\Category::where('slug', 'cars')->first();
if ($c) {
    echo json_encode(array_column($c->fields, 'key'), JSON_PRETTY_PRINT);
} else {
    echo "Cars category not found.\n";
}
