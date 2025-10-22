<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\OfficeModel; // legacy
use App\Models\DepartmentModel;

class Users extends BaseController
{
    protected $userModel;
    protected $officeModel; // legacy
    protected $departmentModel;
    
    public function __construct()
    {
    $this->userModel = new UserModel();
    $this->officeModel = new OfficeModel();
    $this->departmentModel = new DepartmentModel();
    }
    
    public function index()
    {
    // Fetch users with their department names
    $builder = $this->userModel->builder();
    $builder->select('users.*, d.description as department_name');
    $builder->join('departments d', 'd.id = users.department_id', 'left');
    
    // For department admins, only show users from their department
    if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
        $builder->where('users.department_id', session()->get('scoped_department_id'));
    }
    
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
            'departments' => $this->departmentModel->findAll(),
            'offices' => $this->officeModel->findAll()
        ];
        
        return view('admin/users/form', $data);
    }
    
    public function create()
    {
        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'username' => 'required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username]',
            'full_name' => 'required|min_length[3]|max_length[100]',
            'department_id' => 'permit_empty|integer',
            'user_type' => 'required|in_list[admin,requestor,approving_authority,service_staff,superuser,department_admin]',
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
        
        // Department admins can only create users in their own department
        $departmentId = $this->request->getPost('department_id') ?: null;
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($departmentId != session()->get('scoped_department_id')) {
                return redirect()->back()
                    ->with('error', 'You can only create users in your own department')
                    ->withInput();
            }
            
            // Department admins cannot create global admins, superusers, or other department admins
            $userType = $this->request->getPost('user_type');
            if (in_array($userType, ['admin', 'superuser', 'department_admin'])) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to create this type of user')
                    ->withInput();
            }
        }
        
        $data = [
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'department_id' => $departmentId,
            'office_id' => $this->request->getPost('office_id') ?: null,
            'user_type' => $this->request->getPost('user_type'),
            'active' => $this->request->getPost('active'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT)
        ];
        
        // Skip model validation and insert directly
        $this->userModel->skipValidation(true);
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
        
        // Department admins can only edit users in their own department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($user['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('admin/users')
                    ->with('error', 'You can only edit users in your own department');
            }
            
            // Department admins cannot edit global admins, superusers, or other department admins
            if (in_array($user['user_type'], ['admin', 'superuser', 'department_admin'])) {
                return redirect()->to('admin/users')
                    ->with('error', 'You do not have permission to edit this type of user');
            }
        }
        
        $data = [
            'title' => 'Edit User',
            'user' => $user,
            'departments' => $this->departmentModel->findAll(),
            'offices' => $this->officeModel->findAll()
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
        
        // Department admins can only edit users in their own department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($user['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('admin/users')
                    ->with('error', 'You can only edit users in your own department');
            }
            
            // Department admins cannot edit global admins, superusers, or other department admins
            if (in_array($user['user_type'], ['admin', 'superuser', 'department_admin'])) {
                return redirect()->to('admin/users')
                    ->with('error', 'You do not have permission to edit this type of user');
            }
        }
        
        $rules = [
            'email' => "required|valid_email|is_unique[users.email,id,$id]",
            'username' => "required|alpha_numeric_punct|min_length[3]|max_length[30]|is_unique[users.username,id,$id]",
            'full_name' => 'required|min_length[3]|max_length[100]',
            'department_id' => 'permit_empty|integer',
            'user_type' => 'required|in_list[admin,requestor,approving_authority,service_staff,superuser,department_admin]',
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
        
        // Department admins cannot change department or create privileged users
        $departmentId = $this->request->getPost('department_id') ?: null;
        $userType = $this->request->getPost('user_type');
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($departmentId != session()->get('scoped_department_id')) {
                return redirect()->back()
                    ->with('error', 'You cannot change the department of users')
                    ->withInput();
            }
            
            if (in_array($userType, ['admin', 'superuser', 'department_admin'])) {
                return redirect()->back()
                    ->with('error', 'You do not have permission to create this type of user')
                    ->withInput();
            }
        }
        
        $data = [
            'email' => $this->request->getPost('email'),
            'username' => $this->request->getPost('username'),
            'full_name' => $this->request->getPost('full_name'),
            'department_id' => $departmentId,
            'office_id' => $this->request->getPost('office_id') ?: null,
            'user_type' => $userType,
            'active' => $this->request->getPost('active')
        ];
        
        // Update password if provided
        if ($this->request->getPost('password')) {
            $data['password_hash'] = password_hash($this->request->getPost('password'), PASSWORD_DEFAULT);
        }
        
        // Skip model validation and update directly
        $this->userModel->skipValidation(true);
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
