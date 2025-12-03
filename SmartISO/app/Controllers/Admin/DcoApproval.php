<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FormModel;
use App\Models\UserModel;

class DcoApproval extends BaseController
{
    protected $formModel;
    protected $userModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->userModel = new UserModel();
    }

    /**
     * Check if current user is TAU-DCO
     */
    private function checkDcoAccess()
    {
        $userType = session()->get('user_type');
        if (!in_array($userType, ['tau_dco', 'admin', 'superuser'])) {
            return redirect()->to('/dashboard')->with('error', 'Access denied. TAU-DCO access required.');
        }
        return null;
    }

    /**
     * List all forms for DCO approval
     */
    public function index()
    {
        $redirect = $this->checkDcoAccess();
        if ($redirect) return $redirect;

        $forms = $this->formModel->getFormsWithDepartment();
        
        $data = [
            'title' => 'TAU-DCO Form Approval',
            'forms' => $forms
        ];

        return view('admin/dco_approval/index', $data);
    }

    /**
     * Edit form footer details (revision, effectivity)
     */
    public function edit($formId)
    {
        $redirect = $this->checkDcoAccess();
        if ($redirect) return $redirect;

        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dco-approval')->with('error', 'Form not found.');
        }

        // Get approver info if form is DCO approved
        $approver = null;
        if (!empty($form['dco_approved_by'])) {
            $approver = $this->userModel->find($form['dco_approved_by']);
        }

        $data = [
            'title' => 'Edit Form - DCO Approval',
            'form' => $form,
            'approver' => $approver
        ];

        return view('admin/dco_approval/edit', $data);
    }

    /**
     * Update form footer details
     */
    public function update($formId)
    {
        $redirect = $this->checkDcoAccess();
        if ($redirect) return $redirect;

        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dco-approval')->with('error', 'Form not found.');
        }

        $revisionNo = $this->request->getPost('revision_no');
        $effectivityDate = $this->request->getPost('effectivity_date');

        $updateData = [
            'revision_no' => $revisionNo ?: '00',
            'effectivity_date' => $effectivityDate ?: null,
        ];

        if ($this->formModel->update($formId, $updateData)) {
            return redirect()->to('/admin/dco-approval/edit/' . $formId)
                           ->with('success', 'Form footer details updated successfully.');
        }

        return redirect()->back()->with('error', 'Failed to update form details.');
    }

    /**
     * Approve form (set as TAU-DCO approved)
     */
    public function approve($formId)
    {
        $redirect = $this->checkDcoAccess();
        if ($redirect) return $redirect;

        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dco-approval')->with('error', 'Form not found.');
        }

        $updateData = [
            'dco_approved' => 1,
            'dco_approved_by' => session()->get('user_id'),
            'dco_approved_at' => date('Y-m-d H:i:s'),
        ];

        if ($this->formModel->update($formId, $updateData)) {
            return redirect()->to('/admin/dco-approval')
                           ->with('success', 'Form has been approved by TAU-DCO.');
        }

        return redirect()->back()->with('error', 'Failed to approve form.');
    }

    /**
     * Revoke DCO approval
     */
    public function revoke($formId)
    {
        $redirect = $this->checkDcoAccess();
        if ($redirect) return $redirect;

        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dco-approval')->with('error', 'Form not found.');
        }

        $updateData = [
            'dco_approved' => 0,
            'dco_approved_by' => null,
            'dco_approved_at' => null,
        ];

        if ($this->formModel->update($formId, $updateData)) {
            return redirect()->to('/admin/dco-approval')
                           ->with('success', 'DCO approval has been revoked.');
        }

        return redirect()->back()->with('error', 'Failed to revoke approval.');
    }
}
