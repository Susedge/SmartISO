<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\OfficeModel;

class Users extends BaseController
{
    protected $userModel;
    protected $officeModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->officeModel = new OfficeModel();
    }
    
    public function index()
    {
        // Fetch users with their office names
        $builder = $this->userModel->builder();
        $builder->select('users.*, offices.description as office_name');
        $builder->join('offices', 'offices.id = users.office_id', 'left');
        $users = $builder->get()->getResultArray();
        
        $data = [
            'title' => 'User Management',
            'users' => $users
        ];
        
        return view('admin/users/index', $data);
    }
    
    public function new()
    {
        $data = [
            'title' => 'Create New User',
            'offices' => $this->officeModel->where('active', 1)->findAll()
        ];
        
        return view('admin/users/form', $data);
    }
    
    public function create()
    {
        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username]',
            'full_name' => 'required|min_length[3]|max_length[100]',
            'office_id' => 'permit_empty|integer',
            'user_type' => 'required|in_list[admin,requestor,approving_authority,service_staff,superuser]',
            'password' => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]'
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'There was a problem with your submission')
                ->withInput()
                ->with('validation', $this->validator);
        }
        
        // If current user is not a superuser, prevent creating superuser accounts
        if (session()->get('user_type') !== 'superuser' && $this->request->getPost('user_type') === 'superuser') {
            return redirect()->back()
                ->with('error', 'You do not have permission to create superuser accounts')
                ->withInput();
        }
        
        $data = [
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'office_id' => $this->request->getPost('office_id') ?: null,
            'user_type' => $this->request->getPost('user_type'),
            'active' => $this->request->getPost('active'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT)
        ];
        
        if ($this->userModel->insert($data)) {
            return redirect()->to('admin/users')
                ->with('message', 'User created successfully');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to create user')
                ->withInput();
        }
    }
    
    public function edit($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')
                ->with('error', 'User not found');
        }
        
        // Check if non-superuser is trying to edit a superuser
        if (session()->get('user_type') !== 'superuser' && $user['user_type'] === 'superuser') {
            return redirect()->to('admin/users')
                ->with('error', 'You do not have permission to edit superuser accounts');
        }
        
        $data = [
            'title' => 'Edit User',
            'user' => $user,
            'offices' => $this->officeModel->where('active', 1)->findAll()
        ];
        
        return view('admin/users/form', $data);
    }
    
    public function update($id = null)
    {
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')
                ->with('error', 'User not found');
        }
        
        // Check if non-superuser is trying to update a superuser
        if (session()->get('user_type') !== 'superuser' && $user['user_type'] === 'superuser') {
            return redirect()->to('admin/users')
                ->with('error', 'You do not have permission to update superuser accounts');
        }
        
        $rules = [
            'email' => "required|valid_email|is_unique[users.email,id,$id]",
            'username' => "required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username,id,$id]",
            'full_name' => 'required|min_length[3]|max_length[100]',
            'office_id' => 'permit_empty|integer',
            'user_type' => 'required|in_list[admin,requestor,approving_authority,service_staff,superuser]',
        ];
        
        // Add password validation only if password field is filled
        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[8]';
            $rules['password_confirm'] = 'matches[password]';
        }
        
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', 'There was a problem with your submission')
                ->withInput()
                ->with('validation', $this->validator);
        }
        
        // If current user is not a superuser, prevent setting user type to superuser
        if (session()->get('user_type') !== 'superuser' && $this->request->getPost('user_type') === 'superuser') {
            return redirect()->back()
                ->with('error', 'You do not have permission to create superuser accounts')
                ->withInput();
        }
        
        $data = [
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'office_id' => $this->request->getPost('office_id') ?: null,
            'user_type' => $this->request->getPost('user_type'),
            'active' => $this->request->getPost('active')
        ];
        
        // Update password if provided
        if ($this->request->getPost('password')) {
            $data['password_hash'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }
        
        if ($this->userModel->update($id, $data)) {
            return redirect()->to('admin/users')
                ->with('message', 'User updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to update user')
                ->withInput();
        }
    }
    
    public function delete($id = null)
    {
        // Only superusers can delete accounts
        if (session()->get('user_type') !== 'superuser') {
            return redirect()->to('admin/users')
                ->with('error', 'You do not have permission to delete users');
        }
        
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return redirect()->to('admin/users')
                ->with('error', 'User not found');
        }
        
        // Prevent deleting own account
        if ($user['id'] == session()->get('user_id')) {
            return redirect()->to('admin/users')
                ->with('error', 'You cannot delete your own account');
        }
        
        if ($this->userModel->delete($id)) {
            return redirect()->to('admin/users')
                ->with('message', 'User deleted successfully');
        } else {
            return redirect()->to('admin/users')
                ->with('error', 'Failed to delete user');
        }
    }
}
