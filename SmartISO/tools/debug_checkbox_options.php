<?php
/**
 * Debug tool to check what's stored in database for checkbox/dropdown fields
 */

// Database connection
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

// Query checkbox/dropdown fields
$query = "
    SELECT id, panel_name, field_name, field_label, field_type, default_value, field_order 
    FROM dbpanel 
    WHERE field_type IN ('checkbox', 'checkboxes', 'dropdown', 'radio') 
    ORDER BY panel_name, field_order
";

$result = $mysqli->query($query);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

echo "=== CHECKBOX/DROPDOWN FIELDS IN DATABASE ===\n\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "Panel: {$row['panel_name']}\n";
    echo "Field: {$row['field_name']} ({$row['field_label']})\n";
    echo "Type: {$row['field_type']}\n";
    echo "Default Value (raw): {$row['default_value']}\n";
    
    // Try to decode JSON
    $decoded = json_decode($row['default_value'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        echo "Decoded Options (" . count($decoded) . " items):\n";
        foreach ($decoded as $idx => $opt) {
            if (is_array($opt)) {
                $label = isset($opt['label']) ? $opt['label'] : '';
                $subField = isset($opt['sub_field']) ? $opt['sub_field'] : '';
                echo "  [$idx] label: '$label', sub_field: '$subField'\n";
            } else {
                echo "  [$idx] " . var_export($opt, true) . "\n";
            }
        }
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
    echo "\n" . str_repeat("-", 80) . "\n\n";
}

echo "Total fields found: $count\n";

$mysqli->close();
