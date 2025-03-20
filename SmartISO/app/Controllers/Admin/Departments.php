<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepartmentModel;

class Departments extends BaseController
{
    protected $departmentModel;
    
    public function __construct()
    {
        $this->departmentModel = new DepartmentModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Department Management',
            'departments' => $this->departmentModel->findAll()
        ];
        
        return view('admin/departments/index', $data);
    }
    
    public function new()
    {
        $data = [
            'title' => 'Add New Department'
        ];
        
        return view('admin/departments/create', $data);
    }
    
    public function create()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[departments.code]',
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->departmentModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/departments')->with('message', 'Department added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem adding the department')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function edit($id = null)
    {
        $department = $this->departmentModel->find($id);
        
        if ($department) {
            $data = [
                'title' => 'Edit Department',
                'department' => $department
            ];
            
            return view('admin/departments/edit', $data);
        } else {
            return redirect()->to('/admin/departments')->with('error', 'Department not found');
        }
    }
    
    public function update($id = null)
    {
        $rules = [
            'code' => "required|alpha_numeric|min_length[2]|max_length[20]|is_unique[departments.code,id,$id]",
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->departmentModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/departments')->with('message', 'Department updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the department')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function delete($id = null)
    {
        $department = $this->departmentModel->find($id);
        
        if ($department) {
            $this->departmentModel->delete($id);
            return redirect()->to('/admin/departments')->with('message', 'Department deleted successfully');
        } else {
            return redirect()->to('/admin/departments')->with('error', 'Department not found');
        }
    }
}
