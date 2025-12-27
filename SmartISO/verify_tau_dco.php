<?php
// Verify tau_dco user account
$mysqli = new mysqli('localhost', 'root', '', 'smartiso');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

echo "=== TAU-DCO User Account Verification ===\n\n";

$result = $mysqli->query("SELECT id, username, email, full_name, user_type, active, department_id, office_id, created_at FROM users WHERE user_type='tau_dco'");

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "✅ TAU-DCO User Found!\n\n";
        echo "ID:          " . $row['id'] . "\n";
        echo "Username:    " . $row['username'] . "\n";
        echo "Email:       " . $row['email'] . "\n";
        echo "Full Name:   " . $row['full_name'] . "\n";
        echo "User Type:   " . $row['user_type'] . "\n";
        echo "Status:      " . ($row['active'] ? 'Active ✅' : 'Inactive ❌') . "\n";
        echo "Department:  " . ($row['department_id'] ?? 'NULL (University-wide)') . "\n";
        echo "Office:      " . ($row['office_id'] ?? 'NULL (University-wide)') . "\n";
        echo "Created:     " . $row['created_at'] . "\n\n";
    }
    
    echo "=== Login Credentials ===\n";
    echo "Username: tau_dco_user\n";
    echo "Password: password123\n\n";
    echo "⚠️  IMPORTANT: Change the default password after first login!\n";
    
} else {
    echo "❌ No TAU-DCO user found in the database.\n";
    echo "Run: php spark db:seed TauDcoSeeder\n";
}

$mysqli->close();
