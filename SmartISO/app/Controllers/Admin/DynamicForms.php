<?php

namespace App\Controllers\Admin;

use CodeIgniter\Model;
use App\Controllers\BaseController;
use App\Models\DbpanelModel;
use App\Models\FormModel;
use App\Models\OfficeModel;
use App\Models\DepartmentModel;
use App\Models\FormSubmissionModel;
use App\Models\FormSubmissionDataModel;

class DynamicForms extends BaseController
{
    protected $db;
    protected $dbpanelModel;
    protected $formModel;
    protected $departmentModel;
    protected $officeModel;
    protected $formSubmissionModel;
    protected $formSubmissionDataModel;

    /**
     * Rename a panel (update all rows with old_panel_name to new_panel_name)
     * Expects POST: old_panel_name, new_panel_name
     * Returns JSON: { success: true/false, message: string }
     */
    public function renamePanel()
    {
        $oldName = $this->request->getPost('old_panel_name');
        $newName = $this->request->getPost('new_panel_name');
        if (!$oldName || !$newName) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Both old and new panel names are required.'
            ]);
        }
        // Check if new name already exists
        $exists = $this->dbpanelModel->where('panel_name', $newName)->countAllResults();
        if ($exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Panel name already exists.'
            ]);
        }
        $result = $this->dbpanelModel->renamePanel($oldName, $newName);
        if ($result) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Panel renamed successfully.'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to rename panel.'
            ]);
        }
    }
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->dbpanelModel = new DbpanelModel();
        $this->formModel = new FormModel();
        $this->departmentModel = new DepartmentModel();
    $this->officeModel = new OfficeModel();
        $this->formSubmissionModel = new FormSubmissionModel();
        $this->formSubmissionDataModel = new FormSubmissionDataModel();
    }
    
    public function index()
    {
        $q = trim($this->request->getGet('q') ?? '');
        $panelFilter = trim($this->request->getGet('panel') ?? '');
        $officeFilter = $this->request->getGet('office');
        $departmentFilter = $this->request->getGet('department');

        // For department admins, only show offices from their department
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin' && session()->get('department_id');
        $userDepartmentId = session()->get('department_id');

        // Show all offices (previously filtered by active=1 which hid legacy records without the flag)
        $activeOffices = $this->officeModel->orderBy('description','ASC')->findAll();
        if (empty($activeOffices)) {
            // Fallback in case model has default scope filtering
            try {
                $activeOffices = $this->officeModel->builder()->orderBy('description','ASC')->get()->getResultArray();
            } catch (\Throwable $e) {
                // Log but continue with empty set
                log_message('warning','DynamicForms index office fallback failed: '.$e->getMessage());
            }
        }
        
        // Filter offices by department for department admins
        if ($isDepartmentAdmin) {
            $activeOffices = array_filter($activeOffices, function($office) use ($userDepartmentId) {
                return isset($office['department_id']) && $office['department_id'] == $userDepartmentId;
            });
        }
        
        $officeMap = [];
        foreach ($activeOffices as $o) { $officeMap[$o['id']] = $o['description']; }

        // Build base query
        $builder = $this->formModel->builder();
        $builder->select('*');
        
        // For department admins, filter forms by department_id
        if ($isDepartmentAdmin && $userDepartmentId) {
            $builder->where('department_id', $userDepartmentId);
        }
        
        if ($q !== '') {
            $builder->groupStart()
                ->like('code', $q)
                ->orLike('description', $q)
                ->groupEnd();
        }
        if ($panelFilter !== '') {
            $builder->where('panel_name', $panelFilter);
        }
        if (!empty($officeFilter)) {
            $builder->where('office_id', (int)$officeFilter);
        }
        if (!empty($departmentFilter)) {
            $builder->where('department_id', (int)$departmentFilter);
        }
        $builder->orderBy('code','ASC');
        $forms = $builder->get()->getResultArray();

        $data = [
            'title' => 'Forms',
            'forms' => $forms,
            'panels' => $this->dbpanelModel->getPanels(), // Get unique panel names
            'departments' => $this->departmentModel->findAll(),
            'offices' => $activeOffices,
            'officeMap' => $officeMap,
            'q' => $q,
            'panelFilter' => $panelFilter,
            'officeFilter' => $officeFilter,
            'departmentFilter' => $departmentFilter
        ];
        
        return view('admin/dynamicforms/index', $data);
    }
    
    public function panel()
    {
        $formId = $this->request->getGet('form_id');
        $panelName = $this->request->getGet('panel_name');
        
        if (!$formId || !$panelName) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID and Panel Name are required');
        }
        
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        // Department admin security check
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($form['department_id'] != $userDepartmentId) {
                return redirect()->to('/admin/dynamicforms')->with('error', 'You do not have permission to access this form');
            }
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        foreach ($panelFields as &$pf3) {
            $ft3 = $pf3['field_type'] ?? '';
            if (in_array($ft3, ['dropdown','radio','checkbox','checkboxes'])) {
                if (!empty($pf3['default_value'])) {
                    $decoded = json_decode($pf3['default_value'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                        $pf3['options'] = $decoded;
                    }
                }
            }
        }
        unset($pf3);
        // Decode JSON stored options (stored in default_value) for selectable field types so builder can show them
        foreach ($panelFields as &$pf) {
            $ft = $pf['field_type'] ?? '';
            if (in_array($ft, ['dropdown','radio','checkbox','checkboxes'])) {
                if (!empty($pf['default_value'])) {
                    $decoded = json_decode($pf['default_value'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                        $pf['options'] = $decoded;
                    }
                }
            }
        }
        unset($pf);
        
        $data = [
            'title' => 'Form: ' . $form['description'],
            'form' => $form,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields,
            'departments' => $this->departmentModel->findAll()
        ];
        
        return view('admin/dynamicforms/form', $data);
    }
    
    public function panelConfig()
    {
        // Get all departments and offices for the create panel modal
        $departmentModel = new \App\Models\DepartmentModel();
        $officeModel = new \App\Models\OfficeModel();
        
        $data = [
            'title' => 'Panels',
            'panels' => $this->dbpanelModel->getPanels(),
            'departments' => $departmentModel->orderBy('description', 'ASC')->findAll(),
            'offices' => $officeModel->orderBy('description', 'ASC')->findAll()
        ];
        
        return view('admin/dynamicforms/panel_config', $data);
    }

    /**
     * Show placeholders & DOCX variable guide for admins
     */
    public function guide()
    {
        $data = [
            'title' => 'DOCX Variables Guide'
        ];

        return view('admin/dynamicforms/guide', $data);
    }
    
    public function createPanel()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Invalid request method');
        }

        $panelName = $this->request->getPost('panel_name');
        $departmentId = $this->request->getPost('department_id') ?: null;
        $officeId = $this->request->getPost('office_id') ?: null;
        
        if (empty($panelName)) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name is required');
        }

        // Ensure uniqueness
        $exists = $this->dbpanelModel->where('panel_name', $panelName)->countAllResults();
        if ($exists) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name already exists');
        }

        // Create a placeholder field so the panel is created and editable in the builder
        $this->dbpanelModel->insert([
            'panel_name' => $panelName,
            'department_id' => $departmentId,
            'office_id' => $officeId,
            'field_name' => '_placeholder',
            'field_label' => 'Placeholder',
            'field_type' => 'input',
            'field_order' => 1,
            'required' => 0,
            'width' => 12
        ]);

    return redirect()->to('/admin/configurations?type=panels')->with('message', 'Panel created successfully');
    }
    
    public function updatePanelInfo()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        $panelName = $this->request->getPost('panel_name');
        $departmentId = $this->request->getPost('department_id') ?: null;
        $officeId = $this->request->getPost('office_id') ?: null;
        
        if (empty($panelName)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Panel name is required']);
        }

        // Check if panel exists
        $panelExists = $this->dbpanelModel->where('panel_name', $panelName)->first();
        if (!$panelExists) {
            return $this->response->setJSON(['success' => false, 'message' => 'Panel not found']);
        }

        // Department admin check - can only edit panels in their department
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            // Force department to be their own
            $departmentId = $userDepartmentId;
        }

        try {
            // Update all records for this panel
            $this->dbpanelModel->where('panel_name', $panelName)
                ->set([
                    'department_id' => $departmentId,
                    'office_id' => $officeId
                ])
                ->update();

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Panel updated successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Panel update error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Error updating panel: ' . $e->getMessage()
            ]);
        }
    }
    
    public function copyPanel()
    {
        $rules = [
            'source_panel_name' => 'required|max_length[100]',
            'new_panel_name' => 'required|max_length[100]|is_unique[dbpanel.panel_name]'
        ];
        
        if ($this->validate($rules)) {
            $sourcePanelName = $this->request->getPost('source_panel_name');
            $newPanelName = $this->request->getPost('new_panel_name');
            
            // Get all fields from the source panel
            $sourceFields = $this->dbpanelModel->getPanelFields($sourcePanelName);
            
            if (empty($sourceFields)) {
                return redirect()->to('/admin/configurations?type=panels')
                                ->with('error', 'Source panel not found or has no fields');
            }
            
            // Check department admin permissions - can only copy panels from their department
            $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
            $userDepartmentId = session()->get('department_id');
            
            if ($isDepartmentAdmin && $userDepartmentId) {
                $sourcePanelInfo = $this->dbpanelModel->where('panel_name', $sourcePanelName)->first();
                if ($sourcePanelInfo && $sourcePanelInfo['department_id'] && $sourcePanelInfo['department_id'] != $userDepartmentId) {
                    return redirect()->to('/admin/configurations?type=panels')
                        ->with('error', 'You do not have permission to copy this panel');
                }
            }
            
            // Copy each field to the new panel (preserve department_id and office_id from source)
            $departmentId = $sourceFields[0]['department_id'] ?? null;
            $officeId = $sourceFields[0]['office_id'] ?? null;
            
            foreach ($sourceFields as $field) {
                $newFieldData = [
                    'panel_name' => $newPanelName,
                    'field_name' => $field['field_name'],
                    'field_label' => $field['field_label'],
                    'field_type' => $field['field_type'],
                    'field_role' => $field['field_role'] ?? 'requestor',
                    'default_value' => $field['default_value'] ?? '',
                    'field_order' => $field['field_order'],
                    'width' => $field['width'] ?? 6,
                    'required' => $field['required'] ?? 0,
                    'bump_next_field' => $field['bump_next_field'] ?? 0,
                    'code_table' => $field['code_table'] ?? '',
                    'length' => $field['length'] ?? '',
                    'department_id' => $departmentId,
                    'office_id' => $officeId
                ];
                
                $this->dbpanelModel->save($newFieldData);
            }
            
            $fieldCount = count($sourceFields);
            return redirect()->to('/admin/configurations?type=panels')
                            ->with('message', "Panel '{$newPanelName}' created successfully with {$fieldCount} fields copied from '{$sourcePanelName}'");
        } else {
            $errors = $this->validator->getErrors();
            return redirect()->to('/admin/configurations?type=panels')
                            ->with('error', implode(', ', $errors));
        }
    }
    
    /**
     * Remove placeholder fields from a panel
     * This is called automatically when real fields are added
     */
    private function removePlaceholderFields($panelName)
    {
        $this->dbpanelModel->where('panel_name', $panelName)
                          ->where('field_name', '_placeholder')
                          ->delete();
    }

    public function editPanelInfo($panelName = null)
    {
        if (!$panelName) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name is required');
        }
        
        // Get panel info
        $panelInfo = $this->dbpanelModel->where('panel_name', $panelName)->first();
        if (!$panelInfo) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel not found');
        }
        
        // Check department admin permissions
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($panelInfo['department_id'] && $panelInfo['department_id'] != $userDepartmentId) {
                return redirect()->to('/admin/configurations?type=panels')
                    ->with('error', 'You do not have permission to edit this panel');
            }
        }
        
        // Get all departments and offices
        $departmentModel = new \App\Models\DepartmentModel();
        $officeModel = new \App\Models\OfficeModel();
        
        $data = [
            'title' => 'Edit Panel Assignment',
            'panel_name' => $panelName,
            'panel_info' => $panelInfo,
            'departments' => $departmentModel->orderBy('description', 'ASC')->findAll(),
            'allOffices' => $officeModel->orderBy('description', 'ASC')->findAll(),
            'isDepartmentAdmin' => $isDepartmentAdmin,
            'userDepartmentId' => $userDepartmentId
        ];
        
        return view('admin/dynamicforms/edit_panel_info', $data);
    }

    public function savePanelInfo()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Invalid request method');
        }

        $panelName = $this->request->getPost('panel_name');
        $departmentId = $this->request->getPost('department_id') ?: null;
        $officeId = $this->request->getPost('office_id') ?: null;
        
        if (empty($panelName)) {
            return redirect()->back()->with('error', 'Panel name is required')->withInput();
        }

        // Check if panel exists
        $panelExists = $this->dbpanelModel->where('panel_name', $panelName)->first();
        if (!$panelExists) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel not found');
        }

        // Department admin check - can only edit panels in their department
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            // Force department to be their own
            $departmentId = $userDepartmentId;
        }

        try {
            // Update all records for this panel
            $this->dbpanelModel->where('panel_name', $panelName)
                ->set([
                    'department_id' => $departmentId,
                    'office_id' => $officeId
                ])
                ->update();

            return redirect()->to('/admin/dynamicforms/edit-panel-info/' . urlencode($panelName))
                ->with('message', 'Panel assignment updated successfully');
        } catch (\Exception $e) {
            log_message('error', 'Panel update error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error updating panel: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function editPanel($panelName = null)
    {
        if (!$panelName) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name is required');
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        foreach ($panelFields as &$pf2) {
            $ft = $pf2['field_type'] ?? '';
            if (in_array($ft, ['dropdown','radio','checkbox','checkboxes'])) {
                if (!empty($pf2['default_value'])) {
                    $decoded = json_decode($pf2['default_value'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                        $pf2['options'] = $decoded;
                    }
                }
            }
        }
        unset($pf2);
        
        $data = [
            'title' => 'Edit Panel Fields: ' . $panelName,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields
        ];
        
        return view('admin/dynamicforms/edit_panel', $data);
    }

    public function formBuilder($panelName = null)
    {
        if (!$panelName) {
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name is required');
        }
        
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);
        
        // Filter out placeholder fields created during panel creation
        $panelFields = array_filter($panelFields, function($field) {
            return $field['field_name'] !== '_placeholder';
        });

        // Decode JSON stored options (default_value) for selectable field types so the builder
        // round-trips dropdown / radio / checkbox / checkboxes option sets correctly.
        // (This was previously missing here which caused options to disappear after save.)
        foreach ($panelFields as &$pfb) {
            $ft = $pfb['field_type'] ?? '';
            if (in_array($ft, ['dropdown','radio','checkbox','checkboxes'])) {
                $decoded = null;
                
                // Try to decode from options column first (legacy), then default_value
                if (!empty($pfb['options'])) {
                    if (is_string($pfb['options'])) {
                        $decoded = json_decode($pfb['options'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                            log_message('debug', "DynamicForms::formBuilder - Decoded options from 'options' column for field '{$pfb['field_name']}': " . json_encode($decoded));
                        } else {
                            $decoded = null;
                        }
                    } elseif (is_array($pfb['options'])) {
                        $decoded = $pfb['options'];
                        log_message('debug', "DynamicForms::formBuilder - Using array options for field '{$pfb['field_name']}'");
                    }
                }
                
                // If not found in options column, try default_value
                if (empty($decoded) && !empty($pfb['default_value'])) {
                    if (is_string($pfb['default_value'])) {
                        $decoded = json_decode($pfb['default_value'], true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                            log_message('debug', "DynamicForms::formBuilder - Decoded options from 'default_value' column for field '{$pfb['field_name']}': " . json_encode($decoded));
                        } else {
                            $decoded = null;
                            log_message('warning', "DynamicForms::formBuilder - Failed to decode default_value for field '{$pfb['field_name']}', value: " . substr($pfb['default_value'], 0, 100));
                        }
                    }
                }
                
                // Set options array for JavaScript
                if (!empty($decoded)) {
                    $pfb['options'] = $decoded;
                } else {
                    log_message('warning', "DynamicForms::formBuilder - No valid options found for selectable field '{$pfb['field_name']}' (type: {$ft})");
                    $pfb['options'] = [];
                }
            }
        }
        unset($pfb);
        
        $data = [
            'title' => 'Panel Builder: ' . $panelName,
            'panel_name' => $panelName,
            'panel_fields' => $panelFields
        ];
        
        return view('admin/dynamicforms/form_builder', $data);
    }
    
    public function addPanelField()
    {
        $rules = [
            'panel_name' => 'required|max_length[100]',
            'field_name' => 'required|max_length[100]',
            'field_label' => 'required|max_length[100]',
            'field_type' => 'required|in_list[input,dropdown,textarea,list,datepicker,radio,checkbox,checkboxes]',
            'field_role' => 'required|in_list[requestor,service_staff,both,readonly]'
        ];
        
        if ($this->validate($rules)) {

            log_message('debug', 'Panel field POST data: ' . json_encode($this->request->getPost()));

            $this->dbpanelModel->save([
                'panel_name' => $this->request->getPost('panel_name'),
                'field_name' => $this->request->getPost('field_name'),
                'field_label' => $this->request->getPost('field_label'),
                'field_type' => $this->request->getPost('field_type'),
                'bump_next_field' => (int)$this->request->getPost('bump_next_field'),
                'code_table' => $this->request->getPost('code_table'),
                'length' => $this->request->getPost('length'),
                'default_value' => $this->request->getPost('default_value') ?? '',
                'field_order' => $this->request->getPost('field_order') ?? 0,
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6,
                    'field_role' => $this->request->getPost('field_role') ?? 'requestor'
            ]);
            
            return redirect()->to('/admin/dynamicforms/edit-panel/' . $this->request->getPost('panel_name'))
                            ->with('message', 'Field added successfully');
        } else {
            return redirect()->back()
                            ->with('error', 'There was a problem adding the field')
                            ->withInput()
                            ->with('validation', $this->validator);
        }
    }
    
    public function deleteField($id = null)
    {
        
        if (!$field) {
            return redirect()->back()->with('error', 'Field not found');
        }
        
        $panelName = $field['panel_name'];
        $this->dbpanelModel->delete($id);
        
        return redirect()->to('/admin/dynamicforms/edit-panel/' . $panelName)
                        ->with('message', 'Field deleted successfully');
    }

    public function updatePanelField()
    {
        $rules = [
            'field_id' => 'required|numeric',
            'panel_name' => 'required|max_length[100]',
            'field_name' => 'required|max_length[100]',
            'field_label' => 'required|max_length[100]',
            'field_type' => 'required|in_list[input,dropdown,textarea,list,datepicker,radio,checkbox,checkboxes]',
            'field_role' => 'required|in_list[requestor,service_staff,both,readonly]'
        ];
        
        if ($this->validate($rules)) {
            $fieldId = $this->request->getPost('field_id');
            $panelName = $this->request->getPost('panel_name');

            log_message('debug', 'Panel field update POST data: ' . json_encode($this->request->getPost()));
            
            $this->dbpanelModel->update($fieldId, [
                'field_name' => $this->request->getPost('field_name'),
                'field_label' => $this->request->getPost('field_label'),
                'field_type' => $this->request->getPost('field_type'),
                'bump_next_field' => (int)$this->request->getPost('bump_next_field'),
                'code_table' => $this->request->getPost('code_table'),
                'length' => $this->request->getPost('length'),
                'default_value' => $this->request->getPost('default_value') ?? '',
                'field_order' => $this->request->getPost('field_order'),
                'required' => (int)$this->request->getPost('required'),
                'width' => $this->request->getPost('width') ?? 6,
                'field_role' => $this->request->getPost('field_role') ?? 'both'
            ]);
            
            return redirect()->to('/admin/dynamicforms/edit-panel/' . $panelName)
                            ->with('message', 'Field updated successfully');
        } else {
            return redirect()->back()
                            ->with('error', 'There was a problem updating the field')
                            ->withInput()
                            ->with('validation', $this->validator);
        }
    }

    public function submit()
    {
        $formId = $this->request->getPost('form_id');
        $panelName = $this->request->getPost('panel_name');

        log_message('debug', 'Admin DynamicForms::submit POST data: ' . json_encode($this->request->getPost()));

        if (!$formId || !$panelName) {
            return redirect()->back()->with('error', 'Form ID and Panel Name are required');
        }

        // Get all panel fields to validate
        $panelFields = $this->dbpanelModel->getPanelFields($panelName);

        if (empty($panelFields)) {
            return redirect()->back()->with('error', 'No fields configured for this panel');
        }

        // Create a new submission record
        $submissionId = $this->formSubmissionModel->insert([
            'form_id' => $formId,
            'panel_name' => $panelName,
            'submitted_by' => session()->get('user_id'),
            'status' => 'submitted'
        ]);

        // Optional auto-create schedule on submit (admin flow)
        try {
            $appConf = config('App');
            if (!empty($appConf->autoCreateScheduleOnSubmit) && class_exists('App\\Models\\ScheduleModel')) {
                $scheduleModel = new \App\Models\ScheduleModel();
                if (property_exists($scheduleModel, 'allowedFields') && in_array('submission_id', $scheduleModel->allowedFields)) {
                    $scheduledDate = date('Y-m-d');
                    $schedData = [
                        'submission_id' => $submissionId,
                        'scheduled_date' => $scheduledDate,
                        'scheduled_time' => '09:00:00',
                        'duration_minutes' => 60,
                        'assigned_staff_id' => null,
                        'location' => '',
                        'notes' => 'Auto-created schedule on submit (admin)',
                        'status' => 'pending'
                    ];
                    // Attempt to compute ETA based on a priority field if present in POST
                    $priority = $this->request->getPost('priority') ?? 'normal';
                    $etaDays = null; $estimatedDate = null;
                    if ($priority === 'low') {
                        $etaDays = 7;
                        $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +7 days'));
                    } elseif ($priority === 'medium') {
                        $etaDays = 5;
                        try {
                            $schCtrl = new \App\Controllers\Schedule();
                            $estimatedDate = $schCtrl->addBusinessDays($scheduledDate, 5);
                        } catch (\Throwable $e) {
                            $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +7 days'));
                        }
                    } elseif ($priority === 'high') {
                        $etaDays = 3;
                        try {
                            $schCtrl = new \App\Controllers\Schedule();
                            $estimatedDate = $schCtrl->addBusinessDays($scheduledDate, 3);
                        } catch (\Throwable $e) {
                            $estimatedDate = date('Y-m-d', strtotime($scheduledDate . ' +3 days'));
                        }
                    }
                    if ($etaDays && $estimatedDate) {
                        $schedData['eta_days'] = $etaDays;
                        $schedData['estimated_date'] = $estimatedDate;
                        $schedData['priority_level'] = $priority;
                    }
                    try { $scheduleModel->insert($schedData); } catch (\Throwable $e) { log_message('error', 'Auto-schedule on submit (admin) failed: ' . $e->getMessage()); }
                }
            }
        } catch (\Throwable $e) { log_message('error', 'Error auto-creating schedule on admin submit: ' . $e->getMessage()); }

        // Save each field value
        foreach ($panelFields as $field) {
            $fieldName = $field['field_name'];

            // Admin can edit all fields in this flow
            $raw = $this->request->getPost($fieldName);
            $otherText = $this->request->getPost($fieldName . '_other');

            // If it's an array (checkboxes), replace any 'Other' token with the provided other text
            if (is_array($raw)) {
                $normalized = [];
                foreach ($raw as $v) {
                    if (preg_match('/^others?$/i', (string)$v) && !empty($otherText)) {
                        $normalized[] = $otherText;
                    } else {
                        $normalized[] = $v;
                    }
                }
                // Persist as JSON so we can decode later when rendering/exporting
                $fieldValue = json_encode($normalized);
            } else {
                // Single value (input, radio, dropdown)
                $val = $raw ?? '';
                if (is_string($val) && preg_match('/^others?$/i', $val) && !empty($otherText)) {
                    $val = $otherText;
                }
                $fieldValue = $val;
            }

            $this->formSubmissionDataModel->insert([
                'submission_id' => $submissionId,
                'field_name' => $fieldName,
                'field_value' => $fieldValue
            ]);
        }

        return redirect()->to('/admin/dynamicforms/submissions')
                        ->with('message', 'Form submitted successfully');
    }

    public function submissions()
    {
        // Get filters
        $formId = $this->request->getGet('form_id');
        $status = $this->request->getGet('status');
        $priority = $this->request->getGet('priority');
        $search = $this->request->getGet('search');
        
        // IMPORTANT: Fix the admin check - handle null user_type
        $userType = session()->get('user_type');
        
        // Consider user with ID 1 as admin if user_type isn't set
        // Adjust this based on your application's admin user ID
        $isAdmin = (in_array($userType, ['admin', 'superuser', 'department_admin']) || session()->get('user_id') == 1);
        
        // Check if dept admin
        $isDepartmentAdmin = ($userType === 'department_admin' && session()->get('department_id'));
        $userDepartmentId = session()->get('department_id');
        
        // Debug information
        log_message('info', 'DynamicForms submissions() - User Type: ' . ($userType ?? 'null'));
        log_message('info', 'DynamicForms submissions() - User ID: ' . (session()->get('user_id') ?? 'null'));
        log_message('info', 'DynamicForms submissions() - Is Admin: ' . ($isAdmin ? 'true' : 'false'));
        log_message('info', 'DynamicForms submissions() - Is Dept Admin: ' . ($isDepartmentAdmin ? 'true' : 'false'));
        
        // For admin, show all submissions by NOT filtering by user ID
        $userId = $isAdmin ? null : session()->get('user_id');
        
        // Get all forms for filter dropdown
        $forms = $this->formModel->findAll();
        
        // Get priority configurations
        $priorityModel = new \App\Models\PriorityConfigurationModel();
        $priorities = $priorityModel->getPriorityOptions();
        
        // Get submissions - use this simplified approach to avoid issues
        if ($isAdmin) {
            // Build query with joins
            $query = $this->db->table('form_submissions fs')
                ->select('fs.*, f.code as form_code, f.description as form_description, f.department_id, u.full_name as submitted_by_name, sch.priority_level, sch.eta_days, sch.estimated_date')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->join('users u', 'u.id = fs.submitted_by', 'left')
                ->join('schedules sch', 'sch.submission_id = fs.id', 'left');
            
            // For department admins, filter by department
            if ($isDepartmentAdmin && $userDepartmentId) {
                $query->where('f.department_id', $userDepartmentId);
            }
            
            $submissions = $query->orderBy('fs.created_at', 'DESC')->get()->getResultArray();
        } else {
            // For regular users, only show their submissions
            $submissions = $this->db->table('form_submissions fs')
                ->select('fs.*, f.code as form_code, f.description as form_description, u.full_name as submitted_by_name, sch.priority_level, sch.eta_days, sch.estimated_date')
                ->join('forms f', 'f.id = fs.form_id', 'left')
                ->join('users u', 'u.id = fs.submitted_by', 'left')
                ->join('schedules sch', 'sch.submission_id = fs.id', 'left')
                ->where('fs.submitted_by', session()->get('user_id'))
                ->orderBy('fs.created_at', 'DESC')
                ->get()
                ->getResultArray();
        }
        
        // Handle missing data in results and load priority from submission data if not in schedule
        foreach ($submissions as &$row) {
            if (empty($row['form_code'])) $row['form_code'] = 'Unknown';
            if (empty($row['form_description'])) $row['form_description'] = 'Unknown Form';
            if (empty($row['submitted_by_name'])) $row['submitted_by_name'] = 'Unknown User';
            
            // If priority_level not in schedule, try to get it from form_submission_data
            if (empty($row['priority_level'])) {
                $priorityData = $this->db->table('form_submission_data')
                    ->select('field_value')
                    ->where('submission_id', $row['id'])
                    ->where('field_name', 'priority_level')
                    ->get()
                    ->getRowArray();
                
                if ($priorityData) {
                    $row['priority_level'] = $priorityData['field_value'];
                }
            }
        }
        unset($row);
        
        // Debug: Log how many submissions we found
        log_message('info', 'DynamicForms submissions() - Found ' . count($submissions) . ' submissions');
        
        // Apply filters if specified
        if ($formId || $status || $search) {
            $filteredSubmissions = [];
            foreach ($submissions as $submission) {
                $includeSubmission = true;
                
                if ($formId && $submission['form_id'] != $formId) {
                    $includeSubmission = false;
                }
                
                if ($status && $submission['status'] != $status) {
                    $includeSubmission = false;
                }
                
                if ($priority && $submission['priority'] != $priority) {
                    $includeSubmission = false;
                }
                
                if ($search && (
                    stripos($submission['form_code'] ?? '', $search) === false &&
                    stripos($submission['form_description'] ?? '', $search) === false &&
                    stripos($submission['submitted_by_name'] ?? '', $search) === false &&
                    stripos($submission['panel_name'] ?? '', $search) === false
                )) {
                    $includeSubmission = false;
                }
                
                if ($includeSubmission) {
                    $filteredSubmissions[] = $submission;
                }
            }
            $submissions = $filteredSubmissions;
        }
        
        $data = [
            'title' => 'Form Submissions',
            'submissions' => $submissions,
            'forms' => $forms,
            'priorities' => $priorities,
            'formId' => $formId,
            'status' => $status,
            'priority' => $priority,
            'search' => $search
        ];
        
        return view('admin/dynamicforms/submissions', $data);
    }
    
    // Method to view a specific submission
    public function viewSubmission($id = null)
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // UPDATED: Check if user has permission to view this submission
        // Consider user with ID 1 as admin if user_type isn't set
        $userType = session()->get('user_type');
        $isAdmin = (in_array($userType, ['admin', 'superuser']) || session()->get('user_id') == 1);
        
        // Allow view if this submission was shown on the user's calendar previously
        $calendarVisible = session()->get('calendar_visible_submissions') ?? [];
        if (in_array((int)$id, $calendarVisible, true)) {
            $isAdmin = true; // treat as allowed for the purpose of this view
        }

        if (!$isAdmin && $submission['submitted_by'] != session()->get('user_id')) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to view this submission');
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get panel fields to know the structure
        $panelFields = $this->dbpanelModel->getPanelFields($submission['panel_name']);
        
        // Get submission data
        $submissionData = $this->formSubmissionDataModel->getSubmissionDataAsArray($id);
        
        // Get submitter info
        $userModel = new \App\Models\UserModel();
        $submitter = $userModel->find($submission['submitted_by']);
        
        // Get approver info if submission is approved
        $approver = null;
        if (!empty($submission['approver_id'])) {
            $approver = $userModel->find($submission['approver_id']);
        }
        
        // Check if current user can approve submissions
        $canApprove = in_array(session()->get('user_type'), ['admin', 'superuser', 'approving_authority']) && 
                     $submission['status'] === 'submitted';
        
        // Check if current user has a signature
        $currentUser = $userModel->find(session()->get('user_id'));
        $hasSignature = !empty($currentUser['signature']);
        
        $data = [
            'title' => 'View Submission',
            'submission' => $submission,
            'form' => $form,
            'panel_fields' => $panelFields,
            'submission_data' => $submissionData,
            'submitter' => $submitter,
            'approver' => $approver,
            'canApprove' => $canApprove,
            'hasSignature' => $hasSignature,
            'currentUser' => $currentUser
        ];
        
        return view('admin/dynamicforms/view_submission', $data);
    }

    // New method to show approval form
    public function approvalForm($id = null)
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        // Check if user has permission to approve
        $userType = session()->get('user_type');
        
        if (!in_array($userType, ['admin', 'superuser', 'approving_authority'])) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to approve submissions');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if submission is already approved or rejected
        if ($submission['status'] !== 'submitted') {
            return redirect()->to('/admin/dynamicforms/view-submission/' . $id)
                            ->with('error', 'This submission has already been ' . $submission['status']);
        }
        
        // Get form details
        $form = $this->formModel->find($submission['form_id']);
        
        // Get submitter info
        $userModel = new \App\Models\UserModel();
        $submitter = $userModel->find($submission['submitted_by']);
        
        // Get current user info for signature
        $currentUser = $userModel->find(session()->get('user_id'));
        
        $data = [
            'title' => 'Approve Submission',
            'submission' => $submission,
            'form' => $form,
            'submitter' => $submitter,
            'currentUser' => $currentUser
        ];
        
        return view('admin/dynamicforms/approve', $data);
    }
    
    public function updateStatus()
    {
        // Check if user has admin permissions - fixed to use user_type instead of role
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser', 'department_admin']);
        if (!$isAdmin) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'You do not have permission to update submission status'
                ]);
            }
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to update submission status');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $status = $this->request->getPost('status');
        
        if (!$submissionId || !in_array($status, ['approved', 'rejected', 'submitted', 'pending', 'completed'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid submission ID or status'
                ]);
            }
            return redirect()->back()->with('error', 'Invalid submission ID or status');
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Submission not found'
                ]);
            }
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Department admins can only update submissions for their department
        if ($userType === 'department_admin') {
            $form = $this->formModel->find($submission['form_id']);
            if ($form && $form['department_id'] != session()->get('department_id')) {
                if ($this->request->isAJAX()) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'success' => false,
                        'message' => 'You can only update submissions for forms in your department'
                    ]);
                }
                return redirect()->back()->with('error', 'You can only update submissions for forms in your department');
            }
        }
        
        // Update the status
        $this->formSubmissionModel->update($submissionId, ['status' => $status]);
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Submission status updated to ' . ucfirst($status),
                'status' => $status
            ]);
        }
        
        return redirect()->to('/admin/dynamicforms/view-submission/' . $submissionId)
                        ->with('message', 'Submission status updated to ' . ucfirst($status));
    }

    // Method to export submission data as PDF or Excel
    public function exportSubmission($id = null, $format = 'pdf')
    {
        if (!$id) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission ID is required');
        }
        
        $submission = $this->formSubmissionModel->find($id);
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if user has permission to export this submission
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser']);
        $isDeptAdmin = session()->get('is_department_admin');
        $isServiceStaff = ($userType === 'service_staff');
        $isApprovingAuthority = ($userType === 'approving_authority');
        $canExport = $isAdmin || $isDeptAdmin || $isServiceStaff || $isApprovingAuthority || ($submission['submitted_by'] == session()->get('user_id'));
        
        if (!$canExport) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to export this submission');
        }

        // Only allow export for completed submissions
        if (($submission['status'] ?? '') !== 'completed') {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Export is only available for completed submissions');
        }
        
        $format = strtolower($format);
        // PdfGenerator::generateFormPdf() handles both PDF and Word formats
        // It will convert DOCX to PDF using iLovePDF when format=pdf
        if (in_array($format, ['pdf','word','docx'])) {
            return redirect()->to('/pdfgenerator/generateFormPdf/' . $id . '/' . $format);
        }
        return redirect()->back()->with('error', 'Invalid export format');
    }

    /**
     * Admin-side DOCX parsing endpoint for the panel builder.
     * Accepts a DOCX file and returns mapped content-control tags => values.
     * This mirrors Forms::uploadDocx but does not require a form code.
     */
    public function parseDocx()
    {
        $method = strtolower($this->request->getMethod());
        if ($method === 'options') {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Preflight OK',
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
        if ($method !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        // Basic permission: admin, superuser, and department_admin can import into builder
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser', 'department_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $file = $this->request->getFile('docx');
        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid upload']);
        }
        if (strtolower($file->getExtension()) !== 'docx') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Only DOCX files are supported']);
        }

        // Move to temp path
        $tempName = $file->getRandomName();
        $tempPath = WRITEPATH . 'temp';
        if (!is_dir($tempPath)) {
            @mkdir($tempPath, 0755, true);
        }
        $file->move($tempPath, $tempName);
        $fullPath = $tempPath . DIRECTORY_SEPARATOR . $tempName;

    $fieldValues = [];
    $rawFieldValues = []; // preserve original keys -> values for post-processing
        try {
            $zip = new \ZipArchive();
            if ($zip->open($fullPath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $doc = new \DOMDocument();
                    $doc->preserveWhiteSpace = false;
                    $doc->loadXML($xml);
                    $xpath = new \DOMXPath($doc);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $nodes = $xpath->query('//w:sdt');
                    foreach ($nodes as $sdt) {
                        $tagName = null;
                        $tagNode = $xpath->query('.//w:tag', $sdt)->item(0);
                        if ($tagNode && $tagNode->hasAttribute('w:val')) {
                            $tagName = trim($tagNode->getAttribute('w:val'));
                        }
                        if (!$tagName) {
                            $aliasNode = $xpath->query('.//w:alias', $sdt)->item(0);
                            if ($aliasNode && $aliasNode->hasAttribute('w:val')) {
                                $tagName = trim($aliasNode->getAttribute('w:val'));
                            }
                        }
                        if (!$tagName) continue;
                        
                        // Skip signature placeholders (P_ prefix) - these are for image injection only
                        if (preg_match('/^P_/i', $tagName)) {
                            continue;
                        }
                        
                        $contentNode = $xpath->query('.//w:sdtContent', $sdt)->item(0);
                        if ($contentNode) {
                            $textParts = [];
                            $textNodes = $xpath->query('.//w:t', $contentNode);
                            foreach ($textNodes as $tn) {
                                $textParts[] = $tn->textContent;
                            }
                            $value = trim(implode('', $textParts));
                            // Always preserve the original tag -> value mapping even if empty.
                            $rawFieldValues[$tagName] = $value;
                            $fieldValues[$tagName] = $value;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Admin parseDocx error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Failed to parse DOCX']);
        } finally {
            @unlink($fullPath);
        }

        return $this->response->setJSON([
            'success' => true,
            'mapped' => $fieldValues,
            'count' => count($fieldValues),
            'csrf_name' => csrf_token(),
            'csrf_hash' => csrf_hash()
        ]);
    }
    

    public function bulkAction()
    {
        // Check if user has admin permissions
        $userType = session()->get('user_type');
        $isAdmin = in_array($userType, ['admin', 'superuser']);
        if (!$isAdmin) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to perform bulk actions');
        }
        
        $action = $this->request->getPost('bulk_action');
        $submissionIds = $this->request->getPost('selected_submissions');
        
        if (!$action || !$submissionIds || !is_array($submissionIds)) {
            return redirect()->back()->with('error', 'No action selected or no submissions selected');
        }
        
        $count = 0;
        $status = '';
        
        if ($action == 'approve') {
            $status = 'approved';
        } else if ($action == 'reject') {
            $status = 'rejected';
        } else {
            return redirect()->back()->with('error', 'Invalid bulk action');
        }
        
        foreach ($submissionIds as $id) {
            $submission = $this->formSubmissionModel->find($id);
            if ($submission && $submission['status'] == 'submitted') {
                if ($action == 'approve') {
                    // Use model helper to approve so notifications and schedules are created
                    try {
                        $this->formSubmissionModel->approveSubmission($id, session()->get('user_id'), 'Bulk approved by admin');
                        $count++;
                    } catch (\Throwable $e) {
                        log_message('error', 'Bulk approve failed for submission ' . $id . ': ' . $e->getMessage());
                    }
                } else {
                    $this->formSubmissionModel->update($id, ['status' => $status]);
                    $count++;
                }
            }
        }
        
        return redirect()->back()->with('message', $count . ' submissions ' . $status);
    }

    public function approveSubmission()
    {
        // Check if user has permission to approve
        $userType = session()->get('user_type');
        $userId = session()->get('user_id');
        
        if (!in_array($userType, ['admin', 'superuser', 'approving_authority'])) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'You do not have permission to approve submissions');
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $action = $this->request->getPost('action');
        $comments = $this->request->getPost('comments');
        
        if (!$submissionId || !in_array($action, ['approve', 'reject'])) {
            return redirect()->back()->with('error', 'Invalid submission ID or action');
        }
        
        $submission = $this->formSubmissionModel->find($submissionId);
        if (!$submission) {
            return redirect()->to('/admin/dynamicforms/submissions')
                            ->with('error', 'Submission not found');
        }
        
        // Check if user has an uploaded signature for approval
        $userModel = new \App\Models\UserModel();
        $currentUser = $userModel->find($userId);
        
        if ($action === 'approve' && empty($currentUser['signature'])) {
            return redirect()->back()
                            ->with('error', 'You need to upload a signature before approving forms. Please update your profile.');
        }
        
        if ($action === 'approve') {
            $this->formSubmissionModel->approveSubmission($submissionId, $userId, $comments);
            $message = 'Submission approved successfully with your signature applied';
        } else {
            $this->formSubmissionModel->rejectSubmission($submissionId, $userId, $comments);
            $message = 'Submission rejected';
        }
        
        return redirect()->to('/admin/dynamicforms/view-submission/' . $submissionId)
                        ->with('message', $message);
    }

    public function saveFormBuilder()
    {
        // Check if request is AJAX
        if (!$this->request->isAJAX()) {
            log_message('warning', 'saveFormBuilder called without AJAX - isAJAX=' . ($this->request->isAJAX() ? 'true' : 'false'));
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Debug: log incoming header and raw body to help diagnose CSRF 403 issues
        try {
            $incomingCsrfHeader = $this->request->getHeaderLine('X-CSRF-TOKEN');
            log_message('debug', 'saveFormBuilder incoming X-CSRF-TOKEN header: ' . $incomingCsrfHeader);
            $rawInput = $this->request->getBody();
            // Truncate raw input to avoid huge logs
            log_message('debug', 'saveFormBuilder raw body (truncated 2000 chars): ' . substr($rawInput, 0, 2000));
        } catch (\Exception $e) {
            log_message('error', 'saveFormBuilder debug log error: ' . $e->getMessage());
        }

        try {
            // Accept either JSON body or form-encoded fallback with 'payload' param
            $input = [];
            try {
                $raw = $this->request->getBody();
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            } catch (\Exception $e) {
                log_message('debug', 'saveFormBuilder safe JSON parse failed: ' . $e->getMessage());
            }

            if (empty($input)) {
                $payload = $this->request->getPost('payload');
                if ($payload) {
                    $decoded = json_decode($payload, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) $input = $decoded;
                }
            }
            $panelName = $input['panel_name'] ?? '';
            $fields = $input['fields'] ?? [];

            if (empty($panelName)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Panel name is required',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            if (empty($fields)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'At least one field is required',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            // Start transaction
            $this->db->transStart();

            // Preserve existing panel's department_id and office_id before deleting
            $existingPanelField = $this->dbpanelModel->where('panel_name', $panelName)->first();
            $departmentId = $existingPanelField['department_id'] ?? null;
            $officeId = $existingPanelField['office_id'] ?? null;

            // Delete existing fields for this panel
            $this->dbpanelModel->where('panel_name', $panelName)->delete();

            // Insert new fields
            // Get allowed fields from model to avoid inserting unexpected columns
            $allowed = $this->dbpanelModel->allowedFields ?? [];

            foreach ($fields as $index => $field) {
                $fieldData = [
                    'panel_name' => $panelName,
                    'field_name' => $field['field_name'] ?? '',
                    'field_label' => $field['field_label'] ?? '',
                    'field_type' => $field['field_type'] ?? 'input',
                    'field_role' => $field['field_role'] ?? 'requestor',
                    'required' => isset($field['required']) ? (int)$field['required'] : 0,
                    'width' => isset($field['width']) ? (int)$field['width'] : 6,
                    'field_order' => isset($field['field_order']) ? (int)$field['field_order'] : ($index + 1),
                    'bump_next_field' => isset($field['bump_next_field']) ? (int)$field['bump_next_field'] : 0,
                    'code_table' => $field['code_table'] ?? '',
                    'length' => $field['length'] ?? '',
                    'department_id' => $departmentId,
                    'office_id' => $officeId
                ];

                // Add default_value only if the column is allowed in the model
                if (in_array('default_value', $allowed)) {
                    // If this field has options (dropdown, radio, checkbox), persist them as JSON
                    $ftype = $field['field_type'] ?? '';
                    if (!empty($field['options']) && is_array($field['options']) && in_array($ftype, ['dropdown', 'radio', 'checkbox', 'checkboxes'])) {
                        try {
                            // Normalize option objects/strings to simple array preserving label/sub_field if provided
                            $normOpts = [];
                            foreach ($field['options'] as $opt) {
                                if (is_array($opt)) {
                                    // Keep structure if it has label or sub_field for later editing
                                    $normOpts[] = $opt;
                                } else {
                                    $normOpts[] = (string)$opt;
                                }
                            }
                            $fieldData['default_value'] = json_encode($normOpts);
                        } catch (\Throwable $t) {
                            $fieldData['default_value'] = json_encode(array_values($field['options']));
                        }
                    } else {
                        $fieldData['default_value'] = $field['default_value'] ?? '';
                    }
                }

                // Validate required fields
                if (empty($fieldData['field_name']) || empty($fieldData['field_label'])) {
                    $this->db->transRollback();
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Field name and label are required for all fields',
                        'csrf_name' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                }

                // Debug: log the exact data being inserted to help diagnose type/option issues
                try {
                    log_message('debug', 'saveFormBuilder inserting field: ' . json_encode($fieldData));
                } catch (\Exception $e) {
                    // ignore logging failures
                }

                try {
                    $this->dbpanelModel->insert($fieldData);
                } catch (\Exception $ex) {
                    // Capture DB exception details and rollback transaction for clearer diagnostics
                    log_message('error', 'saveFormBuilder insert exception: ' . $ex->getMessage() . ' data: ' . json_encode($fieldData));
                    $this->db->transRollback();
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Database insert error',
                        'csrf_name' => csrf_token(),
                        'csrf_hash' => csrf_hash()
                    ]);
                }
            }

            // Complete transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                // Log DB error details for debugging
                $error = $this->db->error();
                $lastQuery = '';
                try {
                    $lastQuery = $this->db->getLastQuery();
                } catch (\Exception $e) {
                    // ignore
                }

                log_message('error', 'saveFormBuilder DB transaction failed: ' . json_encode($error) . ' last_query: ' . $lastQuery);
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database error occurred',
                    'csrf_name' => csrf_token(),
                    'csrf_hash' => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Form saved successfully',
                'redirect' => base_url('admin/configurations?type=panels'),
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Form builder save error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'An error occurred while saving',
                'csrf_name' => csrf_token(),
                'csrf_hash' => csrf_hash()
            ]);
        }
    }

    public function reorderFields()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        try {
            $input = [];
            try {
                $raw = $this->request->getBody();
                if ($raw) {
                    $decoded = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $input = $decoded;
                    }
                }
            } catch (\Exception $e) {
                log_message('debug', 'reorderFields safe JSON parse failed: ' . $e->getMessage());
            }

            $fieldIds = $input['field_ids'] ?? [];

            if (empty($fieldIds)) {
                return $this->response->setJSON(['success' => false, 'message' => 'No fields to reorder']);
            }

            // Start transaction
            $this->db->transStart();

            // Update field order
            foreach ($fieldIds as $index => $fieldId) {
                $this->dbpanelModel->update($fieldId, ['field_order' => $index + 1]);
            }

            // Complete transaction
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Database error occurred']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Field order updated']);

        } catch (\Exception $e) {
            log_message('error', 'Field reorder error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'An error occurred while reordering']);
        }
    }
    
    public function createForm()
    {
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'code' => 'required|max_length[50]|is_unique[forms.code]',
            'description' => 'required|max_length[255]',
            'panel_name' => 'permit_empty|max_length[255]',
            'office_id' => 'required|numeric|is_not_unique[offices.id]'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('/admin/dynamicforms')
                           ->withInput()
                           ->with('error', 'Validation failed: ' . implode(', ', $validation->getErrors()));
        }

        // Derive department from selected office to keep data consistent
        $officeId = (int)$this->request->getPost('office_id');
        $office   = $this->officeModel->find($officeId);
        if (!$office) {
            return redirect()->to('/admin/dynamicforms')->withInput()->with('error', 'Selected office not found');
        }
        $departmentId = $office['department_id'] ?? null;
        
        // Department admin security check
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($departmentId != $userDepartmentId) {
                return redirect()->to('/admin/dynamicforms')
                               ->with('error', 'You can only create forms for offices in your department');
            }
        }
        
        $data = [
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'panel_name' => $this->request->getPost('panel_name') ?: null,
            'office_id' => $officeId,
            'department_id' => $departmentId
        ];
        
        if ($this->formModel->insert($data)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form created successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to create form');
        }
    }
    
    public function updateForm()
    {
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $formId = $this->request->getPost('form_id');
        if (!$formId) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID is required');
        }
        
        // Department admin security check - verify they own this form
        $existingForm = $this->formModel->find($formId);
        if (!$existingForm) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($existingForm['department_id'] != $userDepartmentId) {
                return redirect()->to('/admin/dynamicforms')
                               ->with('error', 'You do not have permission to edit this form');
            }
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'code' => "required|max_length[50]|is_unique[forms.code,id,{$formId}]",
            'description' => 'required|max_length[255]',
            'panel_name' => 'permit_empty|max_length[255]',
            'office_id' => 'required|numeric|is_not_unique[offices.id]'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->to('/admin/dynamicforms')
                           ->withInput()
                           ->with('error', 'Validation failed: ' . implode(', ', $validation->getErrors()));
        }

        $officeId = (int)$this->request->getPost('office_id');
        $office   = $this->officeModel->find($officeId);
        if (!$office) {
            return redirect()->to('/admin/dynamicforms')->withInput()->with('error', 'Selected office not found');
        }
        $departmentId = $office['department_id'] ?? null; // keep in sync automatically
        
        // Department admin security check - verify new office is in their department
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($departmentId != $userDepartmentId) {
                return redirect()->to('/admin/dynamicforms')
                               ->with('error', 'You can only assign forms to offices in your department');
            }
        }
        
        $data = [
            'code' => $this->request->getPost('code'),
            'description' => $this->request->getPost('description'),
            'panel_name' => $this->request->getPost('panel_name') ?: null,
            'office_id' => $officeId,
            'department_id' => $departmentId
        ];
        
        if ($this->formModel->update($formId, $data)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form updated successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to update form');
        }
    }
    
    public function deleteForm()
    {
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Invalid request method');
        }
        
        $formId = $this->request->getPost('form_id');
        if (!$formId) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form ID is required');
        }
        
        // Department admin security check
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        $isGlobalAdmin = in_array(session('user_type'), ['admin', 'superuser']);
        
        if ($isDepartmentAdmin && $userDepartmentId) {
            if ($form['department_id'] != $userDepartmentId) {
                return redirect()->to('/admin/dynamicforms')
                               ->with('error', 'You do not have permission to delete this form');
            }
        } elseif (!$isGlobalAdmin) {
            return redirect()->to('/admin/dynamicforms')
                           ->with('error', 'Unauthorized access');
        }
        
        $form = $this->formModel->find($formId);
        if (!$form) {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Form not found');
        }
        
        // Check if there are any submissions for this form
        $submissionCount = $this->formSubmissionModel->where('form_id', $formId)->countAllResults();
        if ($submissionCount > 0) {
            return redirect()->to('/admin/dynamicforms')
                           ->with('error', 'Cannot delete form. There are ' . $submissionCount . ' submissions associated with this form.');
        }
        
        if ($this->formModel->delete($formId)) {
            return redirect()->to('/admin/dynamicforms')->with('success', 'Form "' . $form['code'] . '" deleted successfully');
        } else {
            return redirect()->to('/admin/dynamicforms')->with('error', 'Failed to delete form');
        }
    }
    
    public function deletePanel()
    {
        // Check if user is admin, superuser, or department admin
        $userType = session('user_type');
        $isDepartmentAdmin = $userType === 'department_admin';
        $userDepartmentId = session('department_id');
        
        if (!in_array($userType, ['admin', 'superuser']) && !$isDepartmentAdmin) {
            log_message('error', 'deletePanel - Unauthorized access attempt by user type: ' . $userType);
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Unauthorized access. Admin or Department Admin privileges required.');
        }
        
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            log_message('error', 'deletePanel - Invalid request method: ' . $this->request->getMethod());
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Invalid request method');
        }
        
        $panelName = $this->request->getPost('panel_name');
        
        log_message('info', '========== DELETE PANEL START ========== Panel: ' . $panelName);
        
        if (!$panelName) {
            log_message('error', 'deletePanel - Panel name is empty');
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Panel name is required');
        }
        
        // Check department admin permissions
        if ($isDepartmentAdmin && $userDepartmentId) {
            $panelInfo = $this->dbpanelModel->where('panel_name', $panelName)->first();
            if ($panelInfo && $panelInfo['department_id'] && $panelInfo['department_id'] != $userDepartmentId) {
                log_message('error', 'deletePanel - Permission denied for department admin');
                return redirect()->to('/admin/configurations?type=panels')
                    ->with('error', 'You do not have permission to delete this panel');
            }
        }
        
        // Start transaction for data integrity
        $this->db->transStart();
        
        log_message('info', 'deletePanel - Transaction started');
        
        // 1. Clean up all references in forms table - set panel_name to NULL
        $formsUsingPanel = $this->formModel->where('panel_name', $panelName)->findAll();
        log_message('info', 'deletePanel - Found ' . count($formsUsingPanel) . ' forms using this panel');
        
        if (!empty($formsUsingPanel)) {
            foreach ($formsUsingPanel as $form) {
                $this->formModel->update($form['id'], ['panel_name' => null]);
            }
            log_message('info', 'deletePanel - Cleaned up panel reference from ' . count($formsUsingPanel) . ' forms.');
        }
        
        // 2. Clean up all references in form_submissions table - set panel_name to NULL
        $formSubmissionModel = new \App\Models\FormSubmissionModel();
        $submissionsUsingPanel = $formSubmissionModel->where('panel_name', $panelName)->findAll();
        log_message('info', 'deletePanel - Found ' . count($submissionsUsingPanel) . ' submissions using this panel');
        
        if (!empty($submissionsUsingPanel)) {
            foreach ($submissionsUsingPanel as $submission) {
                $formSubmissionModel->update($submission['id'], ['panel_name' => null]);
            }
            log_message('info', 'deletePanel - Cleaned up panel reference from ' . count($submissionsUsingPanel) . ' form submissions.');
        }
        
        // 3. Delete all field definitions for this panel from dbpanel table
        $deleted = $this->dbpanelModel->where('panel_name', $panelName)->delete();
        log_message('info', 'deletePanel - Delete operation returned: ' . ($deleted ? 'TRUE' : 'FALSE'));
        
        // Complete transaction
        $this->db->transComplete();
        
        if ($this->db->transStatus() === false) {
            log_message('error', 'deletePanel - Transaction failed for panel "' . $panelName . '"');
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Failed to delete panel due to database error');
        }
        
        log_message('info', 'deletePanel - Panel deletion completed - Panel: "' . $panelName . '", Fields deleted: ' . $deleted . ', Forms updated: ' . count($formsUsingPanel) . ', Submissions updated: ' . count($submissionsUsingPanel));
        log_message('info', '========== DELETE PANEL END ==========');
        
        if ($deleted) {
            $message = 'Panel "' . $panelName . '" and all its references deleted successfully';
            $details = [];
            if (!empty($formsUsingPanel)) {
                $details[] = count($formsUsingPanel) . ' form(s) updated';
            }
            if (!empty($submissionsUsingPanel)) {
                $details[] = count($submissionsUsingPanel) . ' submission(s) updated';
            }
            if (!empty($details)) {
                $message .= ' (' . implode(', ', $details) . ')';
            }
            return redirect()->to('/admin/configurations?type=panels')->with('success', $message);
        } else {
            log_message('error', 'deletePanel - Delete returned false - panel may not exist');
            return redirect()->to('/admin/configurations?type=panels')->with('error', 'Failed to delete panel or panel not found');
        }
    }
    
    /**
     * Update priority for a form submission
     */
    public function updatePriority()
    {
        if ($this->request->getMethod() !== 'POST') {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }
        
        $submissionId = $this->request->getPost('submission_id');
        $priority = $this->request->getPost('priority');
        
        if (!$submissionId || !$priority) {
            return $this->response->setJSON(['success' => false, 'message' => 'Submission ID and priority are required']);
        }
        
        // Validate priority exists
        $priorityModel = new \App\Models\PriorityConfigurationModel();
        $validPriority = $priorityModel->where('priority_level', $priority)->first();
        
        if (!$validPriority) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid priority level']);
        }
        
        // Update the submission
        $submissionModel = new \App\Models\FormSubmissionModel();
        $result = $submissionModel->update($submissionId, ['priority' => $priority]);
        
        if ($result) {
            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Priority updated successfully',
                'priority_label' => $validPriority['priority_level'],
                'priority_color' => $validPriority['priority_color']
            ]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to update priority']);
        }
    }
}
