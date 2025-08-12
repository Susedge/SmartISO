<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OfficeModel;

class Office extends BaseController
{
    protected $officeModel;

    public function __construct()
    {
        $this->officeModel = new OfficeModel();
    }

    public function index()
    {
        // Check if user is admin
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $data['title'] = 'Office Management';
        $data['offices'] = $this->officeModel->findAll();
        
        return view('admin/office/index', $data);
    }

    public function create()
    {
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $data['title'] = 'Add New Office';
        
        return view('admin/office/create', $data);
    }

    public function store()
    {
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $validation = $this->validate([
            'code'        => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code]',
            'description' => 'required|min_length[3]|max_length[255]',
            'active'      => 'permit_empty|in_list[0,1]'
        ]);

        if (!$validation) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'code'        => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'active'      => $this->request->getPost('active') ? 1 : 0
        ];

        if ($this->officeModel->insert($data)) {
            return redirect()->to('/admin/office')
                           ->with('success', 'Office created successfully');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to create office');
    }

    public function edit($id)
    {
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $office = $this->officeModel->find($id);
        if (!$office) {
            return redirect()->back()->with('error', 'Office not found');
        }

        $data['title'] = 'Edit Office';
        $data['office'] = $office;
        
        return view('admin/office/edit', $data);
    }

    public function update($id)
    {
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $office = $this->officeModel->find($id);
        if (!$office) {
            return redirect()->back()->with('error', 'Office not found');
        }

        $validation = $this->validate([
            'code'        => "required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code,id,{$id}]",
            'description' => 'required|min_length[3]|max_length[255]',
            'active'      => 'permit_empty|in_list[0,1]'
        ]);

        if (!$validation) {
            return redirect()->back()
                           ->withInput()
                           ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'code'        => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'active'      => $this->request->getPost('active') ? 1 : 0
        ];

        if ($this->officeModel->update($id, $data)) {
            return redirect()->to('/admin/office')
                           ->with('success', 'Office updated successfully');
        }

        return redirect()->back()
                       ->withInput()
                       ->with('error', 'Failed to update office');
    }

    public function delete($id)
    {
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }

        $office = $this->officeModel->find($id);
        if (!$office) {
            return redirect()->back()->with('error', 'Office not found');
        }

        // Check if office is used by any users
        $userModel = model('UserModel');
        $usersCount = $userModel->where('office_id', $id)->countAllResults();
        
        if ($usersCount > 0) {
            return redirect()->back()
                           ->with('error', "Cannot delete office '{$office['code']}' because it has {$usersCount} user(s) assigned to it. Please reassign the users to another office before deleting.");
        }

        try {
            if ($this->officeModel->delete($id)) {
                return redirect()->to('/admin/office')
                               ->with('success', 'Office deleted successfully');
            }
        } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
            // Handle foreign key constraint errors
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                return redirect()->back()
                               ->with('error', "Cannot delete office '{$office['code']}' because it is being used by other records in the system. Please remove all dependencies before deleting.");
            }
            // Re-throw other database exceptions
            throw $e;
        }

        return redirect()->back()
                       ->with('error', 'Failed to delete office');
    }

    public function getActiveOffices()
    {
        $offices = $this->officeModel->getActiveOffices();
        
        return $this->response->setJSON([
            'success' => true,
            'offices' => $offices
        ]);
    }
}
