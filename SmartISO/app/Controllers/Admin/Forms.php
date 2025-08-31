<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FormModel;
use App\Models\OfficeModel;

class Forms extends BaseController
{
    protected $formModel;
    protected $officeModel;
    
    public function __construct()
    {
        $this->formModel = new FormModel();
    $this->officeModel = new OfficeModel();
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
        // Get active offices for selection
        $data['offices'] = $this->officeModel->getActiveOffices();

        return view('admin/forms/create', $data);
    }
    
    public function create()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code]',
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        // Require office selection and ensure it exists
        $rules['office_id'] = 'required|numeric|is_not_unique[offices.id]';

        if ($this->validate($rules)) {
            $this->formModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'office_id' => $this->request->getPost('office_id')
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
            // Provide active offices for selection
            $data['offices'] = $this->officeModel->getActiveOffices();

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
        // Require office selection on update as well
        $rules['office_id'] = 'required|numeric|is_not_unique[offices.id]';

        if ($this->validate($rules)) {
            $this->formModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'office_id' => $this->request->getPost('office_id')
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

    public function signForm($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/forms/my-submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check permissions based on user role
        if ($userType === 'requestor') {
            // Requestor can only sign their own forms
            if ($submission['submitted_by'] != $userId) {
                return redirect()->to('/forms/my-submissions')
                                ->with('error', 'You do not have permission to sign this form');
            }
            
            // Record requestor signature date
            $this->formSubmissionModel->update($id, [
                'requestor_signature_date' => date('Y-m-d H:i:s')
            ]);
            
            return redirect()->to('/forms/submission/' . $id)
                            ->with('message', 'Form signed successfully');
                            
        } elseif ($userType === 'approving_authority') {
            // Approver can only sign forms with status 'submitted'
            if ($submission['status'] !== 'submitted') {
                return redirect()->to('/forms/submissions')
                                ->with('error', 'This form cannot be signed at this time');
            }
            
            // Record approver signature and update status
            $this->formSubmissionModel->update($id, [
                'approver_id' => $userId,
                'approver_signature_date' => date('Y-m-d H:i:s'),
                'status' => 'approved'
            ]);
            
            return redirect()->to('/forms/submissions')
                            ->with('message', 'Form approved and signed successfully');
                            
        } elseif ($userType === 'service_staff') {
            // Service staff can only sign approved forms
            if ($submission['status'] !== 'approved') {
                return redirect()->to('/forms/submissions')
                                ->with('error', 'This form cannot be signed at this time');
            }
            
            // Record service staff signature and mark as completed
            $this->formSubmissionModel->update($id, [
                'service_staff_id' => $userId,
                'service_staff_signature_date' => date('Y-m-d H:i:s'),
                'completed' => 1,
                'completion_date' => date('Y-m-d H:i:s')
            ]);
            
            return redirect()->to('/forms/submissions')
                            ->with('message', 'Work completed and form signed successfully');
        }
        
        return redirect()->back()->with('error', 'Unauthorized action');
    }
}
