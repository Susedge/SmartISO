<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentModel;

class Auth extends BaseController
{
    public function register()
    {
        $data = [];
        
        // Get departments for dropdown
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
                    'active'        => 1, // Auto-activate for now (could use email verification later)
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
                    
                    // Set session data
                    $sessionData = [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'user_type' => $user['user_type'],
                        'department_id' => $user['department_id'],
                        'isLoggedIn' => true
                    ];
                    
                    session()->set($sessionData);
                    
                    // Update last login time
                    $userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
                    
                    // Redirect based on user type
                    if (in_array($user['user_type'], ['admin', 'superuser'])) {
                        return redirect()->to('/admin/dashboard');
                    }
                    
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
}