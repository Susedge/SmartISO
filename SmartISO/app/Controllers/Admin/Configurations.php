<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepartmentModel;
use App\Models\FormModel;
use App\Models\FormSignatoryModel;
use App\Models\UserModel;

class Configurations extends BaseController
{
    protected $departmentModel;
    protected $formModel;
    protected $formSignatoryModel;
    protected $userModel;
    
    public function __construct()
    {
        $this->departmentModel = new DepartmentModel();
        $this->formModel = new FormModel();
        
        // Add new models
        $this->formSignatoryModel = new FormSignatoryModel();
        $this->userModel = new UserModel();
    }

    public function formSignatories($formId = null)
    {
        if ($formId === null) {
            return redirect()->to('/admin/configurations?type=forms')
                ->with('error', 'Invalid form ID');
        }
        
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/configurations?type=forms')
                ->with('error', 'Form not found');
        }
        
        $data = [
            'title' => 'Form Signatories: ' . $form['code'] . ' - ' . $form['description'],
            'form' => $form,
            'signatories' => $this->formSignatoryModel->getFormSignatories($formId),
            'availableApprovers' => $this->userModel->where('user_type', 'approving_authority')
                                                   ->where('active', 1)
                                                   ->findAll()
        ];
        
        return view('admin/configurations/form_signatories', $data);
    }

    public function addFormSignatory()
    {
        $formId = $this->request->getPost('form_id');
        $userId = $this->request->getPost('user_id');
        $position = $this->request->getPost('order_position') ?? 0;
        
        // Check if this combination already exists
        $existing = $this->formSignatoryModel
            ->where('form_id', $formId)
            ->where('user_id', $userId)
            ->first();
            
        if ($existing) {
            return redirect()->back()
                ->with('error', 'This user is already a signatory for this form');
        }
        
        if ($this->formSignatoryModel->save([
            'form_id' => $formId,
            'user_id' => $userId,
            'order_position' => $position
        ])) {
            return redirect()->to("/admin/configurations/form-signatories/{$formId}")
                ->with('message', 'Signatory added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'Failed to add signatory')
                ->with('validation', $this->formSignatoryModel->errors());
        }
    }

    public function removeFormSignatory($id = null)
    {
        $signatory = $this->formSignatoryModel->find($id);
        
        if (!$signatory) {
            return redirect()->back()->with('error', 'Signatory not found');
        }
        
        $formId = $signatory['form_id'];
        
        if ($this->formSignatoryModel->delete($id)) {
            return redirect()->to("/admin/configurations/form-signatories/{$formId}")
                ->with('message', 'Signatory removed successfully');
        } else {
            return redirect()->back()->with('error', 'Failed to remove signatory');
        }
    }

    public function userFormSignatories($userId = null)
    {
        if ($userId === null) {
            return redirect()->to('/admin/users')
                ->with('error', 'Invalid user ID');
        }
        
        $user = $this->userModel->find($userId);
        if (!$user || $user['user_type'] !== 'approving_authority') {
            return redirect()->to('/admin/users')
                ->with('error', 'User not found or not an approving authority');
        }
        
        $data = [
            'title' => 'Forms Assigned to: ' . $user['full_name'],
            'user' => $user,
            'assignedForms' => $this->formSignatoryModel->getUserForms($userId),
            'availableForms' => $this->formModel->findAll()
        ];
        
        return view('admin/configurations/user_form_signatories', $data);
    }    
    public function index()
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
        
        // Default to departments if invalid type is provided
        if (!in_array($tableType, ['departments', 'forms'])) {
            $tableType = 'departments';
        }
        
        $data = [
            'title' => 'System Configurations',
            'tableType' => $tableType,
            'departments' => ($tableType == 'departments') ? $this->departmentModel->findAll() : [],
            'forms' => ($tableType == 'forms') ? $this->formModel->findAll() : []
        ];
        
        return view('admin/configurations/index', $data);
    }
    
    public function new()
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
        
        // Default to departments if invalid type is provided
        if (!in_array($tableType, ['departments', 'forms'])) {
            $tableType = 'departments';
        }
        
        $data = [
            'title' => 'Add New ' . ucfirst(rtrim($tableType, 's')),
            'tableType' => $tableType
        ];
        
        return view('admin/configurations/create', $data);
    }
    
    public function create()
    {
        $tableType = $this->request->getPost('table_type') ?? 'departments';
        
        if ($tableType == 'departments') {
            return $this->createDepartment();
        } else if ($tableType == 'forms') {
            return $this->createForm();
        }
        
        return redirect()->to('/admin/configurations')->with('error', 'Invalid table type');
    }
    
    private function createDepartment()
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
            
            return redirect()->to('/admin/configurations?type=departments')->with('message', 'Department added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem adding the department')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    private function createForm()
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
            
            return redirect()->to('/admin/configurations?type=forms')->with('message', 'Form added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem adding the form')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function edit($id = null)
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
        
        if ($tableType == 'departments') {
            $item = $this->departmentModel->find($id);
        } else if ($tableType == 'forms') {
            $item = $this->formModel->find($id);
        } else {
            return redirect()->to('/admin/configurations')->with('error', 'Invalid table type');
        }
        
        if ($item) {
            $data = [
                'title' => 'Edit ' . ucfirst(rtrim($tableType, 's')),
                'tableType' => $tableType,
                'item' => $item
            ];
            
            return view('admin/configurations/edit', $data);
        } else {
            return redirect()->to('/admin/configurations?type=' . $tableType)->with('error', ucfirst(rtrim($tableType, 's')) . ' not found');
        }
    }
    
    public function update($id = null)
    {
        $tableType = $this->request->getPost('table_type') ?? 'departments';
        
        if ($tableType == 'departments') {
            return $this->updateDepartment($id);
        } else if ($tableType == 'forms') {
            return $this->updateForm($id);
        }
        
        return redirect()->to('/admin/configurations')->with('error', 'Invalid table type');
    }
    
    private function updateDepartment($id)
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
            
            return redirect()->to('/admin/configurations?type=departments')->with('message', 'Department updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the department')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    private function updateForm($id)
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
            
            return redirect()->to('/admin/configurations?type=forms')->with('message', 'Form updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the form')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }
    
    public function delete($id = null)
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
        
        if ($tableType == 'departments') {
            $item = $this->departmentModel->find($id);
            if ($item) {
                $this->departmentModel->delete($id);
                return redirect()->to('/admin/configurations?type=departments')->with('message', 'Department deleted successfully');
            }
        } else if ($tableType == 'forms') {
            $item = $this->formModel->find($id);
            if ($item) {
                $this->formModel->delete($id);
                return redirect()->to('/admin/configurations?type=forms')->with('message', 'Form deleted successfully');
            }
        }
        
        return redirect()->to('/admin/configurations?type=' . $tableType)->with('error', ucfirst(rtrim($tableType, 's')) . ' not found');
    }
}
