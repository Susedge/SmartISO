<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OfficeModel;
use App\Models\FormModel;
use App\Models\FormSignatoryModel;
use App\Models\UserModel;
use App\Models\ConfigurationModel;

class Configurations extends BaseController
{
    protected $officeModel;
    protected $formModel;
    protected $formSignatoryModel;
    protected $userModel;
    protected $configurationModel;
    
    public function __construct()
    {
        $this->officeModel = new OfficeModel();
        $this->formModel = new FormModel();
        $this->formSignatoryModel = new FormSignatoryModel();
        $this->userModel = new UserModel();
        $this->configurationModel = new ConfigurationModel();
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
        $tableType = $this->request->getGet('type') ?? 'offices';
        
        // Update valid types to include system configurations
        if (!in_array($tableType, ['offices', 'forms', 'system'])) {
            $tableType = 'offices';
        }
        
        $data = [
            'title' => 'System Configurations',
            'tableType' => $tableType,
            'offices' => ($tableType == 'offices') ? $this->officeModel->findAll() : [],
            'forms' => ($tableType == 'forms') ? $this->formModel->findAll() : [],
            'configurations' => ($tableType == 'system') ? $this->configurationModel->findAll() : []
        ];
        
        return view('admin/configurations/index', $data);
    }
    
    public function new()
    {
        $tableType = $this->request->getGet('type') ?? 'offices';
        
        // Default to offices if invalid type is provided
        if (!in_array($tableType, ['offices', 'forms'])) {
            $tableType = 'offices';
        }
        
        $data = [
            'title' => 'Add New ' . ucfirst(rtrim($tableType, 's')),
            'tableType' => $tableType
        ];
        
        return view('admin/configurations/create', $data);
    }
    
    public function create()
    {
        $tableType = $this->request->getPost('table_type') ?? 'offices';
        
        if ($tableType == 'offices') {
            return $this->createOffice();
        } else if ($tableType == 'forms') {
            return $this->createForm();
        }
        
        return redirect()->to('/admin/configurations')->with('error', 'Invalid table type');
    }
    
    private function createOffice()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code]',
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->officeModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/configurations?type=offices')->with('message', 'Office added successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem adding the office')
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
        $tableType = $this->request->getGet('type') ?? 'offices';
        
        if ($tableType == 'offices') {
            $item = $this->officeModel->find($id);
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
        $tableType = $this->request->getPost('table_type') ?? 'offices';
        
        if ($tableType == 'offices') {
            return $this->updateOffice($id);
        } else if ($tableType == 'forms') {
            return $this->updateForm($id);
        }
        
        return redirect()->to('/admin/configurations')->with('error', 'Invalid table type');
    }
    
    private function updateOffice($id)
    {
        $rules = [
            'code' => "required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code,id,$id]",
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        
        if ($this->validate($rules)) {
            $this->officeModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            
            return redirect()->to('/admin/configurations?type=offices')->with('message', 'Office updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the office')
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
        $tableType = $this->request->getGet('type') ?? 'offices';
        
        if ($tableType == 'offices') {
            $item = $this->officeModel->find($id);
            if ($item) {
                try {
                    // Check if office has users assigned to it
                    $userModel = new \App\Models\UserModel();
                    $usersCount = $userModel->where('office_id', $id)->countAllResults();
                    
                    if ($usersCount > 0) {
                        return redirect()->to('/admin/configurations?type=offices')
                            ->with('error', 'Cannot delete office "' . $item['code'] . '" because it has ' . $usersCount . ' user(s) assigned to it. Please reassign the users to another office before deleting.');
                    }
                    
                    $this->officeModel->delete($id);
                    return redirect()->to('/admin/configurations?type=offices')->with('message', 'Office deleted successfully');
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    // Handle foreign key constraint errors
                    if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                        return redirect()->to('/admin/configurations?type=offices')
                            ->with('error', 'Cannot delete office "' . $item['code'] . '" because it is being used by other records in the system. Please remove all dependencies before deleting.');
                    }
                    // Re-throw other database exceptions
                    throw $e;
                }
            }
        } else if ($tableType == 'forms') {
            $item = $this->formModel->find($id);
            if ($item) {
                try {
                    $this->formModel->delete($id);
                    return redirect()->to('/admin/configurations?type=forms')->with('message', 'Form deleted successfully');
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    // Handle foreign key constraint errors for forms
                    if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                        return redirect()->to('/admin/configurations?type=forms')
                            ->with('error', 'Cannot delete form "' . $item['code'] . '" because it has submissions or other dependencies. Please remove all dependencies before deleting.');
                    }
                    // Re-throw other database exceptions
                    throw $e;
                }
            }
        }
        
        return redirect()->to('/admin/configurations?type=' . $tableType)->with('error', ucfirst(rtrim($tableType, 's')) . ' not found');
    }

    /**
 * Upload a template for a form
 */
public function uploadTemplate($formId = null)
{
    if (!$formId) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Invalid form ID');
    }
    
    $form = $this->formModel->find($formId);
    if (!$form) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Form not found');
    }
    
    // Validate file upload
    $validationRules = [
        'template' => [
            'label' => 'Template file',
            'rules' => 'uploaded[template]|max_size[template,5120]|mime_in[template,application/vnd.openxmlformats-officedocument.wordprocessingml.document]'
        ]
    ];
    
    if (!$this->validate($validationRules)) {
        return redirect()->back()
            ->with('error', $this->validator->getErrors()['template'] ?? 'Invalid template file')
            ->withInput();
    }
    
    $file = $this->request->getFile('template');
    if (!$file->isValid() || $file->hasMoved()) {
        return redirect()->back()->with('error', 'Invalid file upload');
    }
    
    // Create templates directory if it doesn't exist
    $templateDir = FCPATH . 'templates/docx/';
    if (!is_dir($templateDir)) {
        mkdir($templateDir, 0755, true);
    }
    
    // Move the file to the templates directory with the correct naming convention
    $fileName = $form['code'] . '_template.docx';
    
    try {
        $file->move($templateDir, $fileName, true); // Overwrite if exists
        return redirect()->back()->with('message', 'Template uploaded successfully');
    } catch (\Exception $e) {
        return redirect()->back()->with('error', 'Error saving template: ' . $e->getMessage());
    }
}

/**
 * Download a template for a form
 */
public function downloadTemplate($formId = null)
{
    if (!$formId) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Invalid form ID');
    }
    
    $form = $this->formModel->find($formId);
    if (!$form) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Form not found');
    }
    
    $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
    if (!file_exists($templatePath)) {
        return redirect()->back()->with('error', 'Template file not found');
    }
    
    return $this->response->download($templatePath, null);
}

/**
 * Delete a template for a form
 */
public function deleteTemplate($formId = null)
{
    if (!$formId) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Invalid form ID');
    }
    
    $form = $this->formModel->find($formId);
    if (!$form) {
        return redirect()->to('/admin/configurations?type=forms')
            ->with('error', 'Form not found');
    }
    
    $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
    if (file_exists($templatePath)) {
        unlink($templatePath);
        return redirect()->back()->with('message', 'Template deleted successfully');
    }
    
    return redirect()->back()->with('error', 'Template file not found');
}

/**
 * Update system configuration
 */
public function updateSystemConfig()
{
    $configKey = $this->request->getPost('config_key');
    $configValue = $this->request->getPost('config_value');
    
    if (!$configKey) {
        return redirect()->back()->with('error', 'Invalid configuration key');
    }
    
    // Get existing configuration
    $existingConfig = $this->configurationModel->where('config_key', $configKey)->first();
    
    if (!$existingConfig) {
        return redirect()->back()->with('error', 'Configuration not found');
    }
    
    // Handle boolean values specifically
    if ($existingConfig['config_type'] == 'boolean') {
        // For boolean checkboxes, if not checked, the value won't be posted
        // Check if checkbox was checked (value = 1) or not (no value posted)
        $configValue = $this->request->getPost('config_value') ? '1' : '0';
    }
    
    // Validate based on config type
    $rules = [];
    switch ($existingConfig['config_type']) {
        case 'integer':
            $rules['config_value'] = 'required|integer|greater_than[0]';
            break;
        case 'boolean':
            $rules['config_value'] = 'required|in_list[0,1]';
            break;
        default:
            if ($existingConfig['config_key'] == 'system_timezone') {
                $rules['config_value'] = 'required|in_list[Asia/Singapore,Asia/Shanghai,Asia/Manila,Asia/Kuala_Lumpur,Asia/Hong_Kong,Asia/Taipei]';
            } else {
                $rules['config_value'] = 'required|max_length[255]';
            }
    }
    
    // Create validation data with the processed config value
    $validationData = ['config_value' => $configValue];
    
    if (!$this->validate($rules, $validationData)) {
        return redirect()->back()
            ->with('error', 'Invalid configuration value')
            ->with('validation', $this->validator);
    }
    
    // Update configuration
    if ($this->configurationModel->update($existingConfig['id'], ['config_value' => $configValue])) {
        $configName = ucwords(str_replace('_', ' ', $configKey));
        return redirect()->back()->with('message', $configName . ' updated successfully');
    } else {
        return redirect()->back()->with('error', 'Failed to update configuration');
    }
}

}
