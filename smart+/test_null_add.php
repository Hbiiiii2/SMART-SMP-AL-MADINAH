<?php
try {
    $i = 0;
    $firstItem = null;
    $result = $firstItem + $i;
    echo "Result: " . $result . "\n";
} catch (\TypeError $e) {
    echo "TypeError: " . $e->getMessage() . "\n";
}
