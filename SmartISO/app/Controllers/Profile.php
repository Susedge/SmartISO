<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Files\File;

class Profile extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    public function index()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'User not found');
        }
        
        // Get departments and offices for dropdowns
        $departmentModel = new \App\Models\DepartmentModel();
        $officeModel = new \App\Models\OfficeModel();
        
        $data = [
            'title' => 'My Profile',
            'user' => $user,
            'departments' => $departmentModel->findAll(),
            'offices' => $officeModel->findAll()
        ];
        
        return view('profile/index', $data);
    }
    
    public function update()
    {
        // Debug logging
        log_message('info', 'Profile update called');
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));
        
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            log_message('error', 'User not found: ' . $userId);
            return redirect()->to('/login')->with('error', 'User not found');
        }
        
        log_message('info', 'User found: ' . $user['username']);
        
        $rules = [
            'full_name' => 'required|min_length[3]|max_length[100]',
            'email' => [
                'label' => 'Email',
                'rules' => "required|valid_email|is_unique[users.email,id,{$userId}]"
            ],
            'department_id' => 'permit_empty|integer',
            'office_id' => 'permit_empty|integer'
        ];
        
        if (!$this->validate($rules)) {
            $errors = $this->validator->getErrors();
            log_message('error', 'Validation failed: ' . json_encode($errors));
            return redirect()->back()
                ->with('error', 'Validation failed. Please check your input.')
                ->withInput()
                ->with('validation', $this->validator);
        }
        
        $updateData = [
            'full_name' => $this->request->getPost('full_name'),
            'email' => $this->request->getPost('email'),
            'department_id' => $this->request->getPost('department_id') ?: null,
            'office_id' => $this->request->getPost('office_id') ?: null
        ];
        
        log_message('info', 'Update data: ' . json_encode($updateData));
        
        try {
            if ($this->userModel->update($userId, $updateData)) {
                // Update session data
                session()->set('full_name', $updateData['full_name']);
                
                log_message('info', 'Profile updated successfully');
                return redirect()->to('/profile')->with('message', 'Profile updated successfully');
            }
            
            log_message('error', 'Update returned false');
            return redirect()->back()->with('error', 'Failed to update profile')->withInput();
        } catch (\Exception $e) {
            log_message('error', 'Profile update error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }
    
    public function changePassword()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'User not found');
        }
        
        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'Please check your input')
                ->with('validation', $this->validator);
        }
        
        // Verify current password
        if (!password_verify($this->request->getPost('current_password'), $user['password_hash'])) {
            return redirect()->back()->with('error', 'Current password is incorrect');
        }
        
        // Update password
        $updateData = [
            'password_hash' => password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT)
        ];
        
        if ($this->userModel->update($userId, $updateData)) {
            return redirect()->to('/profile')->with('message', 'Password changed successfully');
        }
        
        return redirect()->back()->with('error', 'Failed to change password');
    }
    
    public function uploadSignature()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'User not found');
        }
        
        $validationRules = [
            'signature' => [
                'label' => 'Signature',
                'rules' => 'uploaded[signature]|is_image[signature]|mime_in[signature,image/png,image/jpeg,image/jpg]|max_size[signature,1024]',
            ],
        ];
        
        if ($this->validate($validationRules)) {
            $file = $this->request->getFile('signature');
            
            if ($file->isValid() && !$file->hasMoved()) {
                // Generate random file name
                $newName = $file->getRandomName();
                
                // Move file to uploads directory
                $file->move(ROOTPATH . 'public/uploads/signatures', $newName);
                
                // Update user record with signature path
                $this->userModel->update($userId, [
                    'signature' => 'uploads/signatures/' . $newName
                ]);
                
                return redirect()->to('/profile')->with('message', 'Signature uploaded successfully');
            }
            
            return redirect()->to('/profile')->with('error', 'Error uploading signature');
        }
        
        return redirect()->to('/profile')->with('error', $this->validator->listErrors());
    }
    
    public function uploadProfileImage()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->to('/login')->with('error', 'User not found');
        }
        
        $validationRules = [
            'profile_image' => [
                'label' => 'Profile Image',
                'rules' => 'uploaded[profile_image]|is_image[profile_image]|mime_in[profile_image,image/png,image/jpeg,image/jpg]|max_size[profile_image,2048]',
            ],
        ];
        
        if ($this->validate($validationRules)) {
            $file = $this->request->getFile('profile_image');
            
            if ($file->isValid() && !$file->hasMoved()) {
                // Delete old profile image if exists
                if (!empty($user['profile_image']) && file_exists(ROOTPATH . 'public/' . $user['profile_image'])) {
                    unlink(ROOTPATH . 'public/' . $user['profile_image']);
                }
                
                // Generate random file name
                $newName = $file->getRandomName();
                
                // Move file to uploads directory
                $file->move(ROOTPATH . 'public/uploads/profile_images', $newName);
                
                // Update user record with profile image path
                $this->userModel->update($userId, [
                    'profile_image' => 'uploads/profile_images/' . $newName
                ]);
                
                return redirect()->to('/profile')->with('message', 'Profile image uploaded successfully');
            }
            
            return redirect()->to('/profile')->with('error', 'Error uploading profile image');
        }
        
        return redirect()->to('/profile')->with('error', $this->validator->listErrors());
    }
}
