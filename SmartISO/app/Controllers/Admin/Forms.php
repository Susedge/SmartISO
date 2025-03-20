<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FormModel;

class Forms extends BaseController
{
    protected $formModel;
    
    public function __construct()
    {
        $this->formModel = new FormModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Form Management',
            'forms' => $this->formModel->findAll()
        ];
        
        return view('admin/forms/index', $data);
    }
    
    public function new()
    {
        $data = [
            'title' => 'Add New Form'
        ];
        
        return view('admin/forms/create', $data);
    }
    
    public function create()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code]',
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->formModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/forms')->with('message', 'Form added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem adding the form')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function edit($id = null)
    {
        $form = $this->formModel->find($id);
        
        if ($form) {
            $data = [
                'title' => 'Edit Form',
                'form' => $form
            ];
            
            return view('admin/forms/edit', $data);
        } else {
            return redirect()->to('/admin/forms')->with('error', 'Form not found');
        }
    }
    
    public function update($id = null)
    {
        $rules = [
            'code' => "required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code,id,$id]",
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->formModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/forms')->with('message', 'Form updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the form')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function delete($id = null)
    {
        $form = $this->formModel->find($id);
        
        if ($form) {
            $this->formModel->delete($id);
            return redirect()->to('/admin/forms')->with('message', 'Form deleted successfully');
        } else {
            return redirect()->to('/admin/forms')->with('error', 'Form not found');
        }
    }
}
