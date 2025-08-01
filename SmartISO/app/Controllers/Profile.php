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
        
        $data = [
            'title' => 'My Profile',
            'user' => $user
        ];
        
        return view('profile/index', $data);
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
