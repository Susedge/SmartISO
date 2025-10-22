<?php
// Debug login - add this temporarily to Auth controller's login method

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class AuthDebug extends Controller
{
    public function testLogin()
    {
        $userModel = new UserModel();
        
        echo "<h1>Login Debug Test</h1>";
        echo "<hr>";
        
        // Test 1: Check if user exists
        echo "<h2>Test 1: Finding user</h2>";
        $identity = 'dept_admin_it';
        
        $user = $userModel->where('username', $identity)->first();
        
        if ($user) {
            echo "✅ User found<br>";
            echo "ID: {$user['id']}<br>";
            echo "Username: {$user['username']}<br>";
            echo "Email: {$user['email']}<br>";
            echo "Active: " . ($user['active'] ? 'Yes' : 'No') . "<br>";
            echo "User Type: {$user['user_type']}<br>";
            echo "Department ID: {$user['department_id']}<br>";
            
            // Test 2: Check password
            echo "<hr>";
            echo "<h2>Test 2: Password verification</h2>";
            $password = 'password123';
            
            if (password_verify($password, $user['password_hash'])) {
                echo "✅ Password verified successfully<br>";
                
                // Test 3: Check active status
                echo "<hr>";
                echo "<h2>Test 3: Active check</h2>";
                if ($user['active'] == 0) {
                    echo "❌ Account not active<br>";
                } else {
                    echo "✅ Account is active<br>";
                    
                    // Test 4: Try to set session
                    echo "<hr>";
                    echo "<h2>Test 4: Session test</h2>";
                    
                    $sessionData = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'user_type' => $user['user_type'],
                        'department_id' => $user['department_id'] ?? null,
                        'isLoggedIn' => true,
                        'last_activity' => time()
                    ];
                    
                    session()->set($sessionData);
                    
                    echo "✅ Session data set<br>";
                    echo "<pre>";
                    print_r($sessionData);
                    echo "</pre>";
                    
                    // Test 5: Check if session persists
                    echo "<hr>";
                    echo "<h2>Test 5: Reading session back</h2>";
                    $readBack = session()->get();
                    echo "<pre>";
                    print_r($readBack);
                    echo "</pre>";
                    
                    echo "<hr>";
                    echo "<h2>🎉 All tests passed! Try accessing: <a href='" . base_url('dashboard') . "'>Dashboard</a></h2>";
                }
            } else {
                echo "❌ Password verification failed<br>";
            }
        } else {
            echo "❌ User not found<br>";
        }
    }
}
