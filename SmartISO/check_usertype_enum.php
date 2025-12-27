<?php
// Check current user_type ENUM values
try {
    $mysqli = new mysqli('localhost', 'root', '', 'smartiso');

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error . "\n");
    }

    echo "=== Checking user_type ENUM Values ===\n\n";

    $result = $mysqli->query("SHOW COLUMNS FROM users WHERE Field='user_type'");

    if ($result && $row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . "\n";
        echo "Type: " . $row['Type'] . "\n";
        echo "Default: " . ($row['Default'] ?? 'NULL') . "\n\n";
        
        // Extract enum values
        preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
        if (isset($matches[1])) {
            $enum_values = explode("','", $matches[1]);
            echo "Current ENUM values:\n";
            foreach ($enum_values as $value) {
                echo "  - $value\n";
            }
            
            if (!in_array('tau_dco', $enum_values)) {
                echo "\n⚠️  'tau_dco' is NOT in the ENUM list!\n";
                echo "Need to add it via migration.\n";
            } else {
                echo "\n✅ 'tau_dco' is already in the ENUM list.\n";
            }
        }
    } else {
        echo "Could not retrieve user_type column information.\n";
        echo "Error: " . $mysqli->error . "\n";
    }

    $mysqli->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
