<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\OfficeModel; // legacy
use App\Models\DepartmentModel;

class Auth extends BaseController
{
    public function register()
    {
        $data = [];
        
    // Get departments for dropdown (offices become departments)
    $departmentModel = new DepartmentModel();
    $data['departments'] = $departmentModel->findAll();
        
        if (strtoupper($this->request->getMethod()) === 'POST')
        {
            // Validate the form
            $rules = [
                'email'         => 'required|valid_email|is_unique[users.email]',
                'username'      => 'required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username]',
                'password'      => 'required|min_length[8]',
                'full_name'     => 'required|min_length[3]|max_length[100]',
                'department_id' => 'required|integer',
            ];
            
            if ($this->validate($rules)) {
                $userModel = new UserModel();
                
                // Save the user
                $userData = [
                    'email'         => $this->request->getPost('email'),
                    'username'      => $this->request->getPost('username'),
                    'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                    'full_name'     => $this->request->getPost('full_name'),
                    'department_id' => $this->request->getPost('department_id'),
                    'user_type'     => 'user', // Default user type
                    'active'        => 0, // Require admin activation (VAPT remediation)
                ];
                
                $userModel->insert($userData);
                
                // Redirect to login page with success message
                return redirect()->to('/auth/login')->with('message', 'Registration successful! You can now log in.');
            } else {
                // Display validation errors
                $data['validation'] = $this->validator;
            }
        }
        
        return view('auth/register', $data);
    }
    
    public function login()
    {
        $data = [
            'title' => 'Login - SmartISO'
        ];
        
        if (strtoupper($this->request->getMethod()) === 'POST')
        {
        // Validate the form
            $rules = [
                'login_identity' => 'required',
                'password' => 'required',
            ];
            
            if ($this->validate($rules)) {
                $userModel = new UserModel();
                $identity = $this->request->getPost('login_identity');
                $password = $this->request->getPost('password');
                
                // Check if identity is email or username
                $user = filter_var($identity, FILTER_VALIDATE_EMAIL) 
                    ? $userModel->where('email', $identity)->first()
                    : $userModel->where('username', $identity)->first();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['active'] == 0) {
                        return redirect()->back()->with('error', 'Account is not active. Please contact administrator.');
                    }
                    
                    // Regenerate session ID to prevent fixation
                    session()->regenerate(true);
                    // Set session data
                    $sessionData = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'user_type' => $user['user_type'],
                        'department_id' => $user['department_id'] ?? null,
                        'isLoggedIn' => true,
                        'last_activity' => time() // Set initial last activity time
                    ];
                    
                    session()->set($sessionData);
                    
                    // Update last login time
                    $userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
                    
                    // Redirect to main dashboard for all user types
                    return redirect()->to('/dashboard');
                } else {
                    return redirect()->back()->with('error', 'Invalid username/email or password');
                }
            } else {
                $data['validation'] = $this->validator;
            }
        }
        
        return view('auth/login', $data);
    }
    
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')->with('message', 'You have been logged out successfully');
    }
    
    /**
     * Extend user session
     */
    public function extendSession()
    {
        $sess = session() ?? \Config\Services::session();
        $resp = $this->response ?? \Config\Services::response();

        if (!$sess->get('isLoggedIn')) {
            return $resp->setJSON(['success' => false, 'message' => 'Not logged in']);
        }

        // Update last activity time
        $sess->set('last_activity', time());

        // Safely get CSRF tokens if helper available
        $csrfName = function_exists('csrf_token') ? csrf_token() : '';
        $csrfHash = function_exists('csrf_hash') ? csrf_hash() : '';

        return $resp->setJSON([
            'success' => true,
            'message' => 'Session extended',
            'csrf_name' => $csrfName,
            'csrf_hash' => $csrfHash
        ]);
    }
}