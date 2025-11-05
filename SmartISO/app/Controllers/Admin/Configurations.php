<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OfficeModel;
use App\Models\DepartmentModel;
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
    protected $departmentModel;
    
    public function __construct()
    {
        $this->officeModel = new OfficeModel();
    $this->formModel = new FormModel();
        $this->formSignatoryModel = new FormSignatoryModel();
        $this->userModel = new UserModel();
        $this->configurationModel = new ConfigurationModel();
    $this->departmentModel = new DepartmentModel();
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
        
        // Department admins can only manage signatories for forms in their department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($form['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('/admin/configurations?type=forms')
                    ->with('error', 'You can only manage signatories for forms in your department');
            }
        }
        
        $signatories = $this->formSignatoryModel->getFormSignatories($formId);
        // Include approving authorities, department admins, and superusers as potential approvers
        $availableApprovers = $this->userModel->whereIn('user_type', ['approving_authority', 'department_admin', 'superuser'])
                                             ->where('active', 1)
                                             ->findAll();
        if ($this->request->isAJAX() || $this->request->getGet('ajax')) {
            return $this->response->setJSON([
                'success' => true,
                'form' => [ 'id'=>$form['id'], 'code'=>$form['code'], 'description'=>$form['description'] ],
                'signatories' => $signatories,
                'availableApprovers' => array_map(function($u){ 
                    return [ 
                        'id'=>$u['id'], 
                        'full_name'=>$u['full_name'], 
                        'email'=>$u['email'],
                        'user_type'=>$u['user_type'] ?? ''
                    ]; 
                }, $availableApprovers)
            ]);
        }
        $data = [
            'title' => 'Form Signatories: ' . $form['code'] . ' - ' . $form['description'],
            'form' => $form,
            'signatories' => $signatories,
            'availableApprovers' => $availableApprovers
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
        
        $payload = [
            'form_id' => $formId,
            'user_id' => $userId,
            'order_position' => $position
        ];
        if ($this->formSignatoryModel->save($payload)) {
            if ($this->request->isAJAX()) {
                $list = $this->formSignatoryModel->getFormSignatories($formId);
                return $this->response->setJSON(['success'=>true,'message'=>'Signatory added successfully','signatories'=>$list]);
            }
            return redirect()->to("/admin/configurations/form-signatories/{$formId}")
                ->with('message', 'Signatory added successfully');
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'success'=>false,
                'message'=>'Failed to add signatory',
                'errors'=>$this->formSignatoryModel->errors()
            ]);
        }
        return redirect()->back()
            ->with('error', 'Failed to add signatory')
            ->with('validation', $this->formSignatoryModel->errors());
    }

    public function removeFormSignatory($id = null)
    {
        $signatory = $this->formSignatoryModel->find($id);
        
        if (!$signatory) {
            return redirect()->back()->with('error', 'Signatory not found');
        }
        
        $formId = $signatory['form_id'];
        
        if ($this->formSignatoryModel->delete($id)) {
            if ($this->request->isAJAX()) {
                $list = $this->formSignatoryModel->getFormSignatories($formId);
                return $this->response->setJSON(['success'=>true,'message'=>'Signatory removed successfully','signatories'=>$list]);
            }
            return redirect()->to("/admin/configurations/form-signatories/{$formId}")
                ->with('message', 'Signatory removed successfully');
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(500)->setJSON(['success'=>false,'message'=>'Failed to remove signatory']);
        }
        return redirect()->back()->with('error', 'Failed to remove signatory');
    }

    public function userFormSignatories($userId = null)
    {
        if ($userId === null) {
            return redirect()->to('/admin/users')
                ->with('error', 'Invalid user ID');
        }
        
        $user = $this->userModel->find($userId);
        if (!$user || !in_array($user['user_type'], ['approving_authority', 'department_admin', 'superuser'])) {
            return redirect()->to('/admin/users')
                ->with('error', 'User not found or not an approver');
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
        if (!in_array($tableType, ['departments','offices','forms','system','panels'])) {
            $tableType = 'departments';
        }
        
        // Department admins can only access forms and panels tabs
        if (session()->get('user_type') === 'department_admin') {
            $allowedTabs = ['forms', 'panels'];
            if (!in_array($tableType, $allowedTabs)) {
                // Redirect to forms tab instead of dynamicforms
                return redirect()->to('/admin/configurations?type=forms')
                               ->with('error', 'You do not have permission to access this section.');
            }
        }
        
        $search = trim($this->request->getGet('q') ?? '');

        // Use fresh model instances for query building to avoid mutating controller properties
        $departmentsQ = new DepartmentModel();
        $officesQ = new OfficeModel();
        $formsQ = new FormModel();
        $configQ = new ConfigurationModel();

        if ($search !== '') {
            if ($tableType === 'departments') {
                $departmentsQ = $departmentsQ->groupStart()->like('code', $search)->orLike('description', $search)->groupEnd();
            } elseif ($tableType === 'offices') {
                $officesQ = $officesQ->groupStart()->like('code', $search)->orLike('description', $search)->groupEnd();
            } elseif ($tableType === 'forms') {
                $formsQ = $formsQ->groupStart()->like('code', $search)->orLike('description', $search)->groupEnd();
            } elseif ($tableType === 'system') {
                // optional search across configuration key/description
                $configQ = $configQ->groupStart()->like('config_key', $search)->orLike('config_description', $search)->groupEnd();
            }
        }

        // For department admins, filter forms by department
        if (session()->get('user_type') === 'department_admin' && session()->get('department_id')) {
            $formsQ = $formsQ->where('department_id', session()->get('department_id'));
        }
        
        // Fetch lists (order consistently)
        $departments = $departmentsQ->orderBy('code','ASC')->findAll();
        $offices = ($tableType === 'offices' || $tableType === 'departments') ? $officesQ->orderBy('code','ASC')->findAll() : [];
        $forms = ($tableType === 'forms' || $tableType === 'offices') ? $formsQ->orderBy('code','ASC')->findAll() : [];
        $configurations = ($tableType === 'system') ? $configQ->orderBy('config_key','ASC')->findAll() : [];
    // panels list for Panels tab
    $panels = (new \App\Models\DbpanelModel())->getPanels();
    
    // For department admins, filter panels by their scoped department
    if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
        $scopedDeptId = session()->get('scoped_department_id');
        $panels = array_filter($panels, function($p) use ($scopedDeptId) {
            return !empty($p['department_id']) && (string)$p['department_id'] === (string)$scopedDeptId;
        });
        // Re-index array after filtering
        $panels = array_values($panels);
    }
    
    // Always provide complete lists for assignment modals (independent of selected tab)
    $allOfficesList = $this->officeModel->orderBy('code','ASC')->findAll();
    $allFormsList = $this->formModel->orderBy('code','ASC')->findAll();

        // Build mapping of offices per department. Prefer the old pivot when present,
        // otherwise fall back to the canonical offices.department_id column.
        $departmentOffices = [];
        if ($tableType === 'departments') {
            $db = \Config\Database::connect();
            $allOffices = $this->officeModel->orderBy('code','ASC')->findAll();
            $officeById = [];
            foreach ($allOffices as $o) { $officeById[(int)$o['id']] = $o; }
            if ($db->tableExists('department_office')) {
                $rows = $db->table('department_office')->select('department_id, office_id')->get()->getResultArray();
                foreach ($rows as $r) {
                    $did = isset($r['department_id']) ? (int)$r['department_id'] : null;
                    $oid = isset($r['office_id']) ? (int)$r['office_id'] : null;
                    if ($did && $oid && isset($officeById[$oid])) {
                        $departmentOffices[$did][] = $officeById[$oid];
                    }
                }
            } else {
                // fallback: use offices.department_id
                foreach ($allOffices as $o) {
                    if (!empty($o['department_id'])) {
                        $departmentOffices[(int)$o['department_id']][] = $o;
                    }
                }
            }
        }
        // When rendering the Offices tab, attach a human-readable department description
        // derived from the department_office pivot (many-to-many). Fall back to the
        // offices.department_id single-column if no pivot rows exist for an office.
        if ($tableType === 'offices' && !empty($offices)) {
            $db = \Config\Database::connect();
            // Build department lookup
            $allDepts = $this->departmentModel->findAll();
            $deptById = [];
            foreach ($allDepts as $d) { $deptById[(int)$d['id']] = $d; }
            // Collect pivot rows by office if the pivot table still exists
            $officeDeptMap = [];
            if ($db->tableExists('department_office')) {
                $rows = $db->table('department_office')->select('office_id, department_id')->get()->getResultArray();
                foreach ($rows as $r) {
                    $oid = isset($r['office_id']) ? (int)$r['office_id'] : null;
                    $did = isset($r['department_id']) ? (int)$r['department_id'] : null;
                    if ($oid && $did && isset($deptById[$did])) {
                        $officeDeptMap[$oid][] = $deptById[$did]['description'] ?? $deptById[$did]['code'] ?? null;
                    }
                }
            }
            // Attach comma-separated department description (or null)
            foreach ($offices as $k => $o) {
                $names = $officeDeptMap[$o['id']] ?? [];
                if (empty($names) && !empty($o['department_id']) && isset($deptById[(int)$o['department_id']])) {
                    $names[] = $deptById[(int)$o['department_id']]['description'] ?? $deptById[(int)$o['department_id']]['code'] ?? null;
                }
                $offices[$k]['department_descriptions'] = array_values(array_filter($names));
                $offices[$k]['department_description'] = empty($offices[$k]['department_descriptions']) ? null : implode(', ', $offices[$k]['department_descriptions']);
            }
        }

        $data = [
            'title' => 'System Configurations',
            'tableType' => $tableType,
            'departments' => $departments,
            'offices' => $offices,
            'forms' => $forms,
            'configurations' => $configurations,
            'search' => $search,
            'departmentOffices' => $departmentOffices,
            // Provide reference lists for modals (always safe empty when not needed)
            'allOffices' => $allOfficesList,
            'allForms' => $allFormsList,
            'panels' => $panels,
        ];
        return view('admin/configurations/index', $data);
    }
    
    public function new()
    {
        // Render create form page (replacing prior modal approach)
        $tableType = $this->request->getGet('type') ?? 'departments';
        if (!in_array($tableType, ['departments','offices','forms'])) { $tableType = 'departments'; }

        $data = [
            'title' => 'Add ' . ucfirst(rtrim($tableType, 's')),
            'tableType' => $tableType,
            'mode' => 'create',
            'item' => [
                'id' => null,
                'code' => '',
                'description' => ''
            ],
            'departments' => $this->departmentModel->findAll(),
            'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
            'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
        ];
        return view('admin/configurations/create', $data);
    }
    
    public function create()
    {
        $tableType = $this->request->getPost('table_type') ?? 'departments';
        
        // Block department admins from creating departments/offices (but allow forms)
        if (session()->get('user_type') === 'department_admin') {
            if ($tableType !== 'forms') {
                return redirect()->to('/admin/configurations?type=forms')
                               ->with('error', 'You do not have permission to create departments or offices.');
            }
        }
        
        if ($tableType === 'departments') { return $this->createDepartment(); }
        if ($tableType === 'offices') { return $this->createOffice(); }
        if ($tableType === 'forms') { return $this->createForm(); }
        return redirect()->to('/admin/configurations')->with('error','Invalid table type');
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
            $insertId = $this->departmentModel->getInsertID();
            if ($this->request->isAJAX()) {
                $created = $this->departmentModel->find($insertId);
                return $this->response->setJSON(['success'=>true,'message'=>'Department added successfully','data'=>$created]);
            }
            return redirect()->to('/admin/configurations?type=departments')->with('message','Department added successfully');
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'success'=>false,
                'message'=>'Validation failed',
                'errors'=>$this->validator->getErrors()
            ]);
        }
        return redirect()->back()->with('error','There was a problem adding the department')->withInput()->with('validation',$this->validator);
    }
    
    private function createOffice()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[offices.code]',
            'description' => 'required|min_length[3]|max_length[255]',
            'department_id' => 'permit_empty|integer'
        ];
        if ($this->validate($rules)) {
            $this->officeModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'department_id' => $this->request->getPost('department_id') ?: null,
                'active' => 1
            ]);
            $insertId = $this->officeModel->getInsertID();
            if ($this->request->isAJAX()) {
                $created = $this->officeModel->find($insertId);
                return $this->response->setJSON(['success'=>true,'message'=>'Office added successfully','data'=>$created]);
            }
            return redirect()->to('/admin/configurations?type=offices')->with('message', 'Office added successfully');
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'success'=>false,
                'message'=>'Validation failed',
                'errors'=>$this->validator->getErrors()
            ]);
        }
        return redirect()->back()
            ->with('error', 'There was a problem adding the office')
            ->withInput()
            ->with('validation', $this->validator);
    }
    
    private function createForm()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code]',
            'description' => 'required|min_length[3]|max_length[255]',
            'office_id' => 'permit_empty|integer',
            'department_id' => 'permit_empty|integer'
        ];
        if ($this->validate($rules)) { 
            // Optionally accept office selection and derive department
            $officeId = $this->request->getPost('office_id') ?: null;
            // Allow saving department-only when no office selected
            $departmentId = $this->request->getPost('department_id') ?: null;
            if (!empty($officeId)) {
                $office = (new \App\Models\OfficeModel())->find((int)$officeId);
                if ($office) {
                    // office selection takes precedence and inherits its department
                    $departmentId = $office['department_id'] ?? null;
                }
            }
            
            // Department admin validation - can only create forms for their department
            if (session()->get('user_type') === 'department_admin' && session()->get('department_id')) {
                $userDepartmentId = session()->get('department_id');
                if ($departmentId != $userDepartmentId) {
                    if ($this->request->isAJAX()) {
                        return $this->response->setStatusCode(403)->setJSON([
                            'success' => false,
                            'message' => 'You can only create forms for your department.'
                        ]);
                    }
                    return redirect()->back()
                        ->with('error', 'You can only create forms for your department.')
                        ->withInput();
                }
            }

            $this->formModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'office_id' => $officeId,
                'department_id' => $departmentId
            ]);
            $insertId = $this->formModel->getInsertID();
            if ($this->request->isAJAX()) {
                $created = $this->formModel->find($insertId);
                return $this->response->setJSON(['success'=>true,'message'=>'Form added successfully','data'=>$created]);
            }
            return redirect()->to('/admin/configurations?type=forms')->with('message', 'Form added successfully');
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'success'=>false,
                'message'=>'Validation failed',
                'errors'=>$this->validator->getErrors()
            ]);
        }
        return redirect()->back()
            ->with('error', 'There was a problem adding the form')
            ->withInput()
            ->with('validation', $this->validator);
    }
    
    public function edit($id = null)
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
        if (!in_array($tableType, ['departments','offices','forms','system'])) { $tableType = 'departments'; }
        if ($id === null) {
            return redirect()->to('/admin/configurations?type='.$tableType)->with('error','Missing ID');
        }
        if ($tableType === 'system') {
            $cfg = $this->configurationModel->find($id);
            if(!$cfg){ return redirect()->to('/admin/configurations?type=system')->with('error','Configuration not found'); }
            $data = [
                'title' => 'Edit System Setting',
                'tableType' => 'system',
                'config' => $cfg,
            ];
            return view('admin/configurations/edit_system', $data);
        }
        if ($tableType === 'departments') {
            $item = $this->departmentModel->find($id);
            if (!$item) return redirect()->to('/admin/configurations?type=departments')->with('error','Department not found');
            // Offices assigned via pivot
            $db = \Config\Database::connect();
            $officesForDepartment = [];
            if ($db->tableExists('department_office')) {
                $rows = $db->table('department_office')->select('office_id')->where('department_id',$id)->get()->getResultArray();
                $officeIds = array_map(fn($r)=>(int)$r['office_id'], $rows);
                if (!empty($officeIds)) { $officesForDepartment = $this->officeModel->whereIn('id',$officeIds)->findAll(); }
            } else {
                // Fallback: use offices.department_id
                $officesForDepartment = $this->officeModel->where('department_id', $id)->findAll();
            }
            $data = [
                'title' => 'Edit Department',
                'tableType' => 'departments',
                'mode' => 'edit',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
                'officesForDepartment' => $officesForDepartment,
                'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        if ($tableType === 'offices') {
            $item = $this->officeModel->find($id);
            if (!$item) return redirect()->to('/admin/configurations?type=offices')->with('error','Office not found');
            $data = [
                'title' => 'Edit Office',
                'tableType' => 'offices',
                'mode' => 'edit',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
                'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        if ($tableType === 'forms') {
            $item = $this->formModel->find($id);
            if (!$item) return redirect()->to('/admin/configurations?type=forms')->with('error','Form not found');
            $data = [
                'title' => 'Edit Form',
                'tableType' => 'forms',
                'mode' => 'edit',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                // panels list so the edit view can render the panel assignment select
                'panels' => (new \App\Models\DbpanelModel())->getPanels(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
                'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        return redirect()->to('/admin/configurations');
    }
    
    public function update($id = null)
    {
        $tableType = $this->request->getPost('table_type') ?? 'departments';
        if ($tableType === 'departments') { return $this->updateDepartment($id); }
        if ($tableType === 'offices') { return $this->updateOffice($id); }
        if ($tableType === 'forms') { return $this->updateForm($id); }
        return redirect()->to('/admin/configurations')->with('error','Invalid table type');
    }

    /**
     * AJAX endpoint to save department metadata or assignments.
     * Accepts POST and returns JSON: { success: bool, message: string, errors?: array }
     */
    public function ajaxSaveDepartment($id = null)
    {
        // Block department admins from editing departments
        if (session()->get('user_type') === 'department_admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false, 
                'message' => 'You do not have permission to edit departments.'
            ]);
        }
        
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // If assignment-only, update pivot table department_office (or fall back to offices.department_id)
        $assignedRaw = $this->request->getPost('assign_offices');
    if ($assignedRaw !== null) {
            $assigned = $assignedRaw ?? [];
            if (!is_array($assigned)) { $assigned = [$assigned]; }
            $assignedIds = array_map('intval', $assigned);

            $db = \Config\Database::connect();
            if ($db->tableExists('department_office')) {
                // Use legacy pivot
                if (empty($assignedIds)) {
                    $db->table('department_office')->where('department_id', $id)->delete();
                } else {
                    $db->table('department_office')->where('department_id', $id)->whereNotIn('office_id', $assignedIds)->delete();
                }
                foreach ($assignedIds as $oid) {
                    try {
                        $db->query('INSERT IGNORE INTO department_office (department_id, office_id) VALUES (?, ?)', [$id, $oid]);
                    } catch (\Exception $e) {
                        // ignore individual insert errors
                    }
                }
            } else {
                // Fall back to single-column canonical mapping on offices.department_id
                $officeModel = new \App\Models\OfficeModel();
                if (empty($assignedIds)) {
                    // clear any offices currently assigned to this dept
                    $officeModel->where('department_id', $id)->set(['department_id' => null])->update();
                } else {
                    // Unassign offices previously linked but not in assignedIds
                    $officeModel->where('department_id', $id)->whereNotIn('id', $assignedIds)->set(['department_id' => null])->update();
                    // Assign selected offices to this department
                    $officeModel->whereIn('id', $assignedIds)->set(['department_id' => $id])->update();
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Office assignments updated',
                'csrf' => [ 'name' => csrf_token(), 'hash' => csrf_hash() ]
            ]);
        }

        // Otherwise metadata update
        // Dynamic unique rule: only enforce when code changes
        $existingDept = $this->departmentModel->find($id);
        $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if ($existingDept && strcasecmp($existingDept['code'], $incomingCode) !== 0) {
            $codeRule .= '|is_unique[departments.code]';
        }
        $rules = [
            'code' => $codeRule,
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors(),
                'csrf' => [ 'name' => csrf_token(), 'hash' => csrf_hash() ]
            ]);
        }

        $existingDescription = $existingDept['description'] ?? '';
        $incomingDescription = (string)$this->request->getPost('description');
        $payload = [];
        if (strcasecmp($existingDept['code'], $incomingCode) !== 0) { $payload['code'] = $incomingCode; }
        if ($incomingDescription !== $existingDescription) { $payload['description'] = $incomingDescription; }
        if (empty($payload)) {
            return $this->response->setJSON([
                'success'=>true,
                'message'=>'No changes',
                'data'=>$existingDept,
                'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]
            ]);
        }
        $this->departmentModel->where('id',$id)->set($payload)->update();
        $updated = $this->departmentModel->find($id);
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Department updated',
            'data' => $updated,
            'csrf' => [ 'name' => csrf_token(), 'hash' => csrf_hash() ]
        ]);
    }

    /**
     * AJAX endpoint to save office metadata or form assignments.
     */
    public function ajaxSaveOffice($id = null)
    {
        // Block department admins from editing offices
        if (session()->get('user_type') === 'department_admin') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false, 
                'message' => 'You do not have permission to edit offices.'
            ]);
        }
        
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        // Forms assignment
        $assignedRaw = $this->request->getPost('assign_forms');
        if ($assignedRaw !== null) {
            $assigned = $assignedRaw ?? [];
            if (!is_array($assigned)) { $assigned = [$assigned]; }
            $assignedIds = array_map('intval', $assigned);
            $this->formModel->where('office_id', $id)->whereNotIn('id', $assignedIds)->set(['office_id' => null])->update();
            if (!empty($assignedIds)) { $this->formModel->whereIn('id', $assignedIds)->set(['office_id' => $id])->update(); }
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Form assignments updated',
                'csrf' => [ 'name' => csrf_token(), 'hash' => csrf_hash() ]
            ]);
        }

        // Metadata update
        $existing = $this->officeModel->find($id);
        if(!$existing){ return $this->response->setJSON(['success'=>false,'message'=>'Office not found','csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]); }
        $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if (strcasecmp($existing['code'], $incomingCode)!==0) { $codeRule .= '|is_unique[offices.code]'; }
        $rules = [
            'code' => $codeRule,
            'description' => 'required|min_length[3]|max_length[255]',
            'department_id' => 'permit_empty|integer'
        ];
        if(!$this->validate($rules)){
            return $this->response->setJSON(['success'=>false,'message'=>'Validation failed','errors'=>$this->validator->getErrors(),'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
        }
        $existingDescription = $existing['description'] ?? '';
        $incomingDescription = (string)$this->request->getPost('description');
        $incomingDept = $this->request->getPost('department_id') ?: null;
        $payload = [];
        if (strcasecmp($existing['code'], $incomingCode)!==0) { $payload['code'] = $incomingCode; }
        if ($incomingDescription !== $existingDescription) { $payload['description'] = $incomingDescription; }
        if (($existing['department_id'] ?? null) != $incomingDept) { $payload['department_id'] = $incomingDept; }
        if (empty($payload)) {
            return $this->response->setJSON(['success'=>true,'message'=>'No changes','data'=>$existing,'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
        }
        $this->officeModel->where('id',$id)->set($payload)->update();
        $updated = $this->officeModel->find($id);
        return $this->response->setJSON(['success'=>true,'message'=>'Office updated','data'=>$updated,'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
    }

    public function ajaxSaveForm($id = null)
    {
        if(!$this->request->isAJAX()){
            return $this->response->setStatusCode(400)->setJSON(['success'=>false,'message'=>'Invalid request']);
        }
        $existing = $this->formModel->find($id);
        if(!$existing){ return $this->response->setJSON(['success'=>false,'message'=>'Form not found','csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]); }
        
        // Department admin validation - can only edit forms in their department
        if (session()->get('user_type') === 'department_admin' && session()->get('department_id')) {
            $userDepartmentId = session()->get('department_id');
            if ($existing['department_id'] != $userDepartmentId) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'You can only edit forms in your department.',
                    'csrf' => ['name' => csrf_token(), 'hash' => csrf_hash()]
                ]);
            }
        }
    $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if(strcasecmp($existing['code'],$incomingCode)!==0){ $codeRule .= '|is_unique[forms.code]'; }
    $rules = [ 'code'=>$codeRule, 'description'=>'required|min_length[3]|max_length[255]', 'panel_name'=>'permit_empty|max_length[255]', 'office_id' => 'permit_empty|integer', 'department_id' => 'permit_empty|integer' ];
        if(!$this->validate($rules)){
            return $this->response->setJSON(['success'=>false,'message'=>'Validation failed','errors'=>$this->validator->getErrors(),'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
        }
        $existingDescription = $existing['description'] ?? '';
        $incomingDescription = (string)$this->request->getPost('description');
        $payload = [];
        if (strcasecmp($existing['code'],$incomingCode)!==0) { $payload['code']=$incomingCode; }
        if ($incomingDescription !== $existingDescription) { $payload['description']=$incomingDescription; }
        // Panel assignment handling: include panel_name even if only that changed
        $incomingPanel = $this->request->getPost('panel_name') ?: null;
        if (($existing['panel_name'] ?? null) !== $incomingPanel) { $payload['panel_name'] = $incomingPanel; }

        // Handle office/department assignment: office selection takes precedence
        $officeId = $this->request->getPost('office_id') ?: null;
        $incomingDept = $this->request->getPost('department_id') ?: null;
        if ($officeId !== null && $officeId !== '') {
            $office = (new \App\Models\OfficeModel())->find((int)$officeId);
            $deptId = $office['department_id'] ?? null;
            if (($existing['office_id'] ?? null) != $officeId) { $payload['office_id'] = $officeId; }
            if (($existing['department_id'] ?? null) != $deptId) { $payload['department_id'] = $deptId; }
        } else {
            // No office selected — allow department-only save
            if (($existing['department_id'] ?? null) != ($incomingDept !== null ? $incomingDept : null)) {
                $payload['department_id'] = $incomingDept ?: null;
            }
            if (!empty($incomingDept) && ($existing['office_id'] ?? null) !== null && ($existing['department_id'] ?? null) != ($incomingDept ?: null)) {
                // If changing department-only, clear office association to avoid mismatch
                $payload['office_id'] = null;
            }
        }

        if (empty($payload)) {
            $templatePath = FCPATH.'templates/docx/'.$existing['code'].'_template.docx';
            return $this->response->setJSON(['success'=>true,'message'=>'No changes','data'=>$existing,'has_template'=>file_exists($templatePath)?1:0,'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
        }
        $this->formModel->where('id',$id)->set($payload)->update();
        $updated = $this->formModel->find($id);
        $templatePath = FCPATH.'templates/docx/'.$incomingCode.'_template.docx';
        return $this->response->setJSON(['success'=>true,'message'=>'Form updated','data'=>$updated,'has_template'=>file_exists($templatePath)?1:0,'csrf'=>['name'=>csrf_token(),'hash'=>csrf_hash()]]);
    }

    private function updateDepartment($id)
    {
        // Fetch existing to determine if code changed
        $existing = $this->departmentModel->find($id);
        if(!$existing){
            return redirect()->to('/admin/configurations?type=departments')->with('error','Department not found');
        }
        $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if (strcasecmp($existing['code'], $incomingCode) !== 0) {
            $codeRule .= '|is_unique[departments.code]';
        }
        $rules = [
            'code' => $codeRule,
            'description' => 'required|min_length[3]|max_length[255]'
        ];
        // If this request is only assigning offices (the assign_offices[] control),
        // process assignments without validating code/description. This allows the
        // separate assignments form to submit without needing those fields.
            $assignedRaw = $this->request->getPost('assign_offices');
        if ($assignedRaw !== null) {
            $assigned = $assignedRaw ?? [];
            if (!is_array($assigned)) { $assigned = [$assigned]; }
            $assignedIds = array_map('intval', $assigned);

            $db = \Config\Database::connect();
            if ($db->tableExists('department_office')) {
                if (empty($assignedIds)) {
                    $db->table('department_office')->where('department_id', $id)->delete();
                } else {
                    $db->table('department_office')->where('department_id', $id)->whereNotIn('office_id', $assignedIds)->delete();
                }
                foreach ($assignedIds as $oid) {
                    try { $db->query('INSERT IGNORE INTO department_office (department_id, office_id) VALUES (?, ?)', [$id, $oid]); } catch (\Exception $e) {}
                }
                $officesForDepartment = (function() use ($db, $id) {
                    $rows = $db->table('department_office')->select('office_id')->where('department_id', $id)->get()->getResultArray();
                    $ids = array_map(function($r){ return isset($r['office_id']) ? (int)$r['office_id'] : null; }, $rows);
                    $ids = array_filter($ids);
                    if (empty($ids)) { return []; }
                    return (new \App\Models\OfficeModel())->whereIn('id', $ids)->findAll();
                })();
            } else {
                // Update offices.department_id instead
                $officeModel = new \App\Models\OfficeModel();
                if (empty($assignedIds)) {
                    $officeModel->where('department_id', $id)->set(['department_id' => null])->update();
                    $officesForDepartment = [];
                } else {
                    $officeModel->where('department_id', $id)->whereNotIn('id', $assignedIds)->set(['department_id' => null])->update();
                    $officeModel->whereIn('id', $assignedIds)->set(['department_id' => $id])->update();
                    $officesForDepartment = $officeModel->where('department_id', $id)->findAll();
                }
            }

            // Set a flash message and re-render the edit page (no redirect)
            session()->setFlashdata('message', 'Office assignments updated successfully');
            $item = $this->departmentModel->find($id);
            $data = [
                'title' => 'Edit Department',
                'tableType' => 'departments',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
                'officesForDepartment' => $officesForDepartment,
                'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }

        // Otherwise, perform a normal department update (code/description validation required)
        if ($this->validate($rules)) {
            $this->departmentModel->update($id,[
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ]);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success'=>true,'message'=>'Department updated successfully']);
            }
            session()->setFlashdata('message', 'Department updated successfully');
            $item = $this->departmentModel->find($id);
            $data = [
                'title' => 'Edit Department',
                'tableType' => 'departments',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
                'officesForDepartment' => $this->officeModel->where('department_id', $id)->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>'Validation failed','errors'=>$this->validator->getErrors()]);
        }
        return redirect()->back()->with('error','There was a problem updating the department')->withInput()->with('validation',$this->validator);
    }
    
    private function updateOffice($id)
    {
        $existing = $this->officeModel->find($id);
        if(!$existing){
            return redirect()->to('/admin/configurations?type=offices')->with('error','Office not found');
        }
        $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if (strcasecmp($existing['code'], $incomingCode) !== 0) {
            $codeRule .= '|is_unique[offices.code]';
        }
        $rules = [
            'code' => $codeRule,
            'description' => 'required|min_length[3]|max_length[255]',
            'department_id' => 'permit_empty|integer'
        ];
        // Handle form assignment only submissions (assign_forms[] checkboxes)
        $assignFormsRaw = $this->request->getPost('assign_forms');
        if ($assignFormsRaw !== null) {
            $assigned = $assignFormsRaw ?? [];
            if (!is_array($assigned)) { $assigned = [$assigned]; }
            $assignedIds = array_map('intval', $assigned);
            // Unassign forms currently pointing to this office but not selected
            $this->formModel->where('office_id', $id)->whereNotIn('id', $assignedIds)->set(['office_id'=>null])->update();
            // Assign selected
            if (!empty($assignedIds)) {
                $this->formModel->whereIn('id', $assignedIds)->set(['office_id'=>$id])->update();
            }
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success'=>true,'message'=>'Form assignments updated']);
            }
            return redirect()->to('/admin/configurations?type=offices')->with('message','Form assignments updated');
        }
        if ($this->validate($rules)) {
            $this->officeModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'department_id' => $this->request->getPost('department_id') ?: null
            ]);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success'=>true,'message'=>'Office updated successfully']);
            }
            session()->setFlashdata('message', 'Office updated successfully');
            $item = $this->officeModel->find($id);
            $data = [
                'title' => 'Edit Office',
                'tableType' => 'offices',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
                'allForms' => $this->formModel->orderBy('description','ASC')->findAll(),
                'allOffices' => $this->officeModel->orderBy('description','ASC')->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>'Validation failed','errors'=>$this->validator->getErrors()]);
        }
        return redirect()->back()
            ->with('error', 'There was a problem updating the office')
            ->withInput()
            ->with('validation', $this->validator);
    }
    
    private function updateForm($id)
    {
        $existing = $this->formModel->find($id);
        if(!$existing){
            return redirect()->to('/admin/configurations?type=forms')->with('error','Form not found');
        }
        $incomingCode = trim((string)$this->request->getPost('code'));
        $codeRule = 'required|alpha_numeric|min_length[2]|max_length[20]';
        if (strcasecmp($existing['code'], $incomingCode) !== 0) {
            $codeRule .= '|is_unique[forms.code]';
        }
        $rules = [
            'code' => $codeRule,
            'description' => 'required|min_length[3]|max_length[255]',
            'office_id' => 'permit_empty|integer',
            'department_id' => 'permit_empty|integer'
        ];
        if ($this->validate($rules)) {
            $updatePayload = [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description')
            ];
            // Accept optional panel_name assignment
            $panelName = $this->request->getPost('panel_name') ?: null;
            $updatePayload['panel_name'] = $panelName;
            
            // Accept optional office assignment and/or department assignment.
            // Office selection takes precedence: it will inherit its office's department.
            $officeId = $this->request->getPost('office_id') ?: null;
            $incomingDept = $this->request->getPost('department_id') ?: null;
            
            if ($officeId !== null && $officeId !== '') {
                $office = (new \App\Models\OfficeModel())->find((int)$officeId);
                $deptId = $office['department_id'] ?? null;
                $updatePayload['office_id'] = $officeId;
                $updatePayload['department_id'] = $deptId;
            } else {
                // No office selected — allow department-only save
                $updatePayload['office_id'] = null;
                $updatePayload['department_id'] = $incomingDept;
            }
            
            // Check if there are actual changes
            $hasChanges = false;
            foreach ($updatePayload as $key => $value) {
                $existingValue = $existing[$key] ?? null;
                // Convert empty string to null for comparison
                $existingValue = ($existingValue === '') ? null : $existingValue;
                $value = ($value === '') ? null : $value;
                
                if ($existingValue != $value) {
                    $hasChanges = true;
                    break;
                }
            }
            
            if (!$hasChanges) {
                if ($this->request->isAJAX()) {
                    return $this->response->setJSON(['success'=>true,'message'=>'No changes']);
                }
                session()->setFlashdata('message', 'No changes');
                $item = $this->formModel->find($id);
                $data = [
                    'title' => 'Edit Form',
                    'tableType' => 'forms',
                    'item' => $item,
                    'departments' => $this->departmentModel->findAll(),
                ];
                return view('admin/configurations/edit', $data);
            }
            
            $this->formModel->update($id, $updatePayload);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success'=>true,'message'=>'Form updated successfully']);
            }
            session()->setFlashdata('message', 'Form updated successfully');
            $item = $this->formModel->find($id);
            $data = [
                'title' => 'Edit Form',
                'tableType' => 'forms',
                'item' => $item,
                'departments' => $this->departmentModel->findAll(),
            ];
            return view('admin/configurations/edit', $data);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON(['success'=>false,'message'=>'Validation failed','errors'=>$this->validator->getErrors()]);
        }
        return redirect()->back()
            ->with('error', 'There was a problem updating the form')
            ->withInput()
            ->with('validation', $this->validator);
    }
    
    public function delete($id = null)
    {
        $tableType = $this->request->getGet('type') ?? 'departments';
    // Treat explicit ajax=1 query param as AJAX as well (fetch requests may not always preserve headers)
    $isAjax = $this->request->isAJAX() || $this->request->getGet('ajax');
        if ($tableType === 'departments') {
            $item = $this->departmentModel->find($id);
            if ($item) {
                // Prevent delete if offices reference this department
                $officeCount = $this->officeModel->where('department_id',$id)->countAllResults();
                if ($officeCount > 0) {
                    $msg = 'This department cannot be deleted because '.$officeCount.' office'.($officeCount==1?' is':'s are').' still linked. Reassign or remove those offices first.';
                    if($isAjax){ return $this->response->setJSON(['success'=>false,'message'=>$msg]); }
                    return redirect()->to('/admin/configurations?type=departments')->with('error',$msg);
                }
                $this->departmentModel->delete($id);
                if($isAjax){ return $this->response->setJSON(['success'=>true,'message'=>'Department deleted successfully']); }
                return redirect()->to('/admin/configurations?type=departments')->with('message','Department deleted successfully');
            }
        } elseif ($tableType === 'offices') {
            $item = $this->officeModel->find($id);
            if ($item) {
                try {
                        // Auto-unassign any forms currently pointing to this office before delete
                        $formsCount = $this->formModel->where('office_id',$id)->countAllResults();
                        if ($formsCount > 0) {
                            $this->formModel->where('office_id',$id)->set(['office_id'=>null])->update();
                        }
                    // Remove any pivot entries (department_office) referencing this office (safety if FKs added)
                    try {
                        $db = \Config\Database::connect();
                        if ($db->tableExists('department_office')) {
                            $db->table('department_office')->where('office_id',$id)->delete();
                        }
                    } catch(\Exception $e) { /* ignore */ }
                    $userModel = new \App\Models\UserModel();
                    $usersCount = $userModel->where('office_id',$id)->countAllResults();
                    if ($usersCount > 0) {
                        $umsg = 'This office cannot be deleted because '.$usersCount.' user'.($usersCount==1?' is':'s are').' assigned. Reassign those user(s) to another office first.';
                        if($isAjax){ return $this->response->setJSON(['success'=>false,'message'=>$umsg]); }
                        return redirect()->to('/admin/configurations?type=offices')->with('error',$umsg);
                    }
                    $this->officeModel->delete($id);
                        $msg = 'Office deleted successfully';
                        if($formsCount>0){ $msg .= ' ('.$formsCount.' form'.($formsCount>1?'s':'').' were unassigned)'; }
                    if($isAjax){ return $this->response->setJSON(['success'=>true,'message'=>$msg]); }
                    return redirect()->to('/admin/configurations?type=offices')->with('message',$msg);
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    if (strpos($e->getMessage(),'foreign key constraint fails') !== false) {
                        $cmsg = 'Unable to delete this office due to related records (database constraint). Ensure no related references remain.';
                        if($isAjax){ return $this->response->setJSON(['success'=>false,'message'=>$cmsg]); }
                        return redirect()->to('/admin/configurations?type=offices')->with('error',$cmsg);
                    }
                    throw $e;
                }
            }
        } elseif ($tableType === 'forms') {
            $item = $this->formModel->find($id);
            if ($item) {
                try {
                    $this->formModel->delete($id);
                    if($isAjax){ return $this->response->setJSON(['success'=>true,'message'=>'Form deleted successfully']); }
                    return redirect()->to('/admin/configurations?type=forms')->with('message','Form deleted successfully');
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    if (strpos($e->getMessage(),'foreign key constraint fails') !== false) {
                        $fmsg = 'This form cannot be deleted because submissions exist. Archive or remove those submissions first.';
                        if($isAjax){ return $this->response->setJSON(['success'=>false,'message'=>$fmsg]); }
                        return redirect()->to('/admin/configurations?type=forms')->with('error',$fmsg);
                    }
                    throw $e;
                }
            }
        }
        if($isAjax){ return $this->response->setJSON(['success'=>false,'message'=>ucfirst(rtrim($tableType,'s')).' not found']); }
        return redirect()->to('/admin/configurations?type='.$tableType)->with('error',ucfirst(rtrim($tableType,'s')).' not found');
    }

    /**
 * Upload a template for a form
 */
public function uploadTemplate($formId = null)
{
    // Allow formId to be provided either via route param or POST (hidden input)
    if (!$formId) {
        $formId = $this->request->getPost('form_id') ?? null;
    }

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
            // Attempt to create a PDF version of the uploaded DOCX for faster downloads
            try {
                $docxPath = $templateDir . $fileName;
                $pdfDir = FCPATH . 'templates/pdf/';
                if (!is_dir($pdfDir)) {
                    mkdir($pdfDir, 0755, true);
                }
                $pdfPath = $pdfDir . $form['code'] . '.pdf';

                // Load and convert using PhpWord if available
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
                    if (class_exists('\\Dompdf\\Dompdf')) {
                        \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
                    }
                    $pdfWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
                    $pdfWriter->save($pdfPath);
                } catch (\Exception $e) {
                    // Log but do not fail the upload
                    log_message('warning', 'Failed to pre-generate PDF for form ' . $form['code'] . ': ' . $e->getMessage());
                }
            } catch (\Exception $e) {
                log_message('warning', 'Error during PDF pre-generation: ' . $e->getMessage());
            }

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'message' => 'Template uploaded successfully']);
            }
            return redirect()->back()->with('message', 'Template uploaded successfully');
        } catch (\Exception $e) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON(['success'=>false,'message'=>'Error saving template: '.$e->getMessage()]);
            }
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
            // Allow zero for specific config keys (e.g., session_timeout = 0 disables timeout)
            if ($existingConfig['config_key'] === 'session_timeout') {
                // Allow 0 and positive integers
                $rules['config_value'] = 'required|integer|greater_than_equal_to[0]';
            } else {
                $rules['config_value'] = 'required|integer|greater_than[0]';
            }
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
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'success'=>false,
                'message'=>'Invalid configuration value',
                'errors'=>$this->validator->getErrors()
            ]);
        }
        return redirect()->back()
            ->with('error', 'Invalid configuration value')
            ->with('validation', $this->validator);
    }
    
    // Update configuration
    if ($this->configurationModel->update($existingConfig['id'], ['config_value' => $configValue])) {
        $configName = ucwords(str_replace('_', ' ', $configKey));
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success'=>true,'message'=>$configName.' updated successfully']);
        }
        return redirect()->back()->with('message', $configName . ' updated successfully');
    }
    if ($this->request->isAJAX()) {
        return $this->response->setStatusCode(500)->setJSON(['success'=>false,'message'=>'Failed to update configuration']);
    }
    return redirect()->back()->with('error', 'Failed to update configuration');
    }

    /**
     * Return JSON for a single item (department, office, form, configuration)
     */
    public function item($id = null)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success'=>false,'message'=>'Invalid request']);
        }
        $tableType = $this->request->getGet('type') ?? 'departments';
        if (!in_array($tableType, ['departments','offices','forms','system'])) {
            return $this->response->setStatusCode(400)->setJSON(['success'=>false,'message'=>'Invalid type']);
        }
        if ($tableType === 'system') {
            $config = $this->configurationModel->find($id);
            if (!$config) { return $this->response->setStatusCode(404)->setJSON(['success'=>false,'message'=>'Config not found']); }
            return $this->response->setJSON(['success'=>true,'data'=>$config]);
        }
        if ($tableType === 'departments') {
            $item = $this->departmentModel->find($id);
            if (!$item) { return $this->response->setStatusCode(404)->setJSON(['success'=>false,'message'=>'Department not found']); }
            // Collect assigned office ids via pivot (if available) otherwise via offices.department_id
            $db = \Config\Database::connect();
            $assigned = [];
            if ($db->tableExists('department_office')) {
                $rows = $db->table('department_office')->select('office_id')->where('department_id',$id)->get()->getResultArray();
                $assigned = array_map(function($r){return (int)$r['office_id'];}, $rows);
            } else {
                $assigned = array_map(function($o){ return (int)$o['id']; }, $this->officeModel->where('department_id', $id)->findAll());
            }
            $item['assigned_office_ids'] = $assigned;
            return $this->response->setJSON(['success'=>true,'data'=>$item]);
        }
        if ($tableType === 'offices') {
            $item = $this->officeModel->find($id);
            if (!$item) { return $this->response->setStatusCode(404)->setJSON(['success'=>false,'message'=>'Office not found']); }
            // Assigned forms (forms.office_id == this id)
            $forms = $this->formModel->where('office_id',$id)->findAll();
            $item['assigned_form_ids'] = array_map(function($f){return (int)$f['id'];}, $forms);
            return $this->response->setJSON(['success'=>true,'data'=>$item]);
        }
        if ($tableType === 'forms') {
            $item = $this->formModel->find($id);
            if (!$item) { return $this->response->setStatusCode(404)->setJSON(['success'=>false,'message'=>'Form not found']); }
            // Template existence flag
            $templatePath = FCPATH . 'templates/docx/' . $item['code'] . '_template.docx';
            $item['has_template'] = file_exists($templatePath) ? 1 : 0;
            return $this->response->setJSON(['success'=>true,'data'=>$item]);
        }
        return $this->response->setStatusCode(400)->setJSON(['success'=>false,'message'=>'Unhandled']);
    }

    /**
     * Export the entire database as a SQL file and stream for download.
     * Uses a PHP-based exporter for portability (does not rely on mysqldump).
     */
    public function exportDatabase()
    {
        // Double-check session user type (route already filters admin/superuser)
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return redirect()->to('/admin/configurations?type=system')->with('error', 'Unauthorized');
        }

        $db = \Config\Database::connect();

        $filename = 'db_backup_' . date('Ymd_His') . '.sql';

        // Stream headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Output header comment
        echo "-- Database Backup\n-- Generated: " . date('c') . "\n\n";

        // Get all tables
        $tables = [];
        $result = $db->query('SHOW TABLES');
        foreach ($result->getResultArray() as $row) {
            $tables[] = array_values($row)[0];
        }

        foreach ($tables as $table) {
            echo "--\n-- Structure for table `{$table}`\n--\n\n";
            echo "DROP TABLE IF EXISTS `{$table}`;\n";

            $createRes = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createRes->getRowArray();
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
            echo $createSql . ";\n\n";

            // Data
            $rows = $db->query("SELECT * FROM `{$table}`");
            $rowCount = $rows->getNumRows();
            if ($rowCount > 0) {
                echo "--\n-- Dumping data for table `{$table}`\n--\n\n";
                foreach ($rows->getResultArray() as $r) {
                    $cols = array_map(function($c){ return "`" . str_replace('`', '``', $c) . "`"; }, array_keys($r));
                    $vals = array_map(function($v) {
                        if ($v === null) return 'NULL';
                        return "'" . addslashes($v) . "'";
                    }, array_values($r));
                    echo "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                echo "\n";
            }

            if (function_exists('ob_flush')) { @ob_flush(); }
            if (function_exists('flush')) { @flush(); }
        }

        exit;
    }
    
    public function getDepartments()
    {
        $isDepartmentAdmin = session()->get('user_type') === 'department_admin';
        $userDepartmentId = session()->get('department_id');
        
        $departments = $this->departmentModel->orderBy('code', 'ASC')->findAll();
        
        return $this->response->setJSON([
            'success' => true,
            'departments' => $departments,
            'is_department_admin' => $isDepartmentAdmin,
            'user_department_id' => $userDepartmentId
        ]);
    }
    
    public function getOffices()
    {
        $offices = $this->officeModel->orderBy('code', 'ASC')->findAll();
        return $this->response->setJSON([
            'success' => true,
            'offices' => $offices
        ]);
    }

    /**
     * Toggle automatic database backup setting (AJAX)
     * Creates the config key if it doesn't exist.
     */
    public function toggleAutoBackup()
    {
        // Only admin/superuser may toggle this
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Unauthorized']);
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Invalid request']);
        }

        $val = $this->request->getPost('value');
        // Accept explicit values '1','0','true','false' or toggle when omitted
        $existing = $this->configurationModel->where('config_key', 'auto_backup_enabled')->first();

        if ($val === null) {
            // toggle
            $newVal = $existing ? (!((bool)$existing['config_value'])) : true;
        } else {
            $newVal = in_array((string)$val, ['1','true','yes'], true) ? true : false;
        }

        try {
            $this->configurationModel->setConfig('auto_backup_enabled', $newVal, 'Enable automatic database backups', 'boolean');
            return $this->response->setJSON(['success' => true, 'value' => (int)$newVal, 'message' => 'Auto backup ' . ($newVal ? 'enabled' : 'disabled')]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Failed to update setting: ' . $e->getMessage()]);
        }
    }
}

