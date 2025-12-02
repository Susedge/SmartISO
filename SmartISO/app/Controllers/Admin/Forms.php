<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\FormModel;
use App\Models\OfficeModel; // legacy
use App\Models\DepartmentModel;

class Forms extends BaseController
{
    protected $formModel;
    protected $officeModel; // legacy selection
    protected $departmentModel;
    
    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->officeModel = new OfficeModel();
        $this->departmentModel = new DepartmentModel();
    }
    
    public function index()
    {
        // For department admins, only show forms from their department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            $forms = $this->formModel->where('department_id', session()->get('scoped_department_id'))->findAll();
        } else {
            $forms = $this->formModel->findAll();
        }
        
        $data = [
            'title' => 'Form Management',
            'forms' => $forms
        ];
        
        return view('admin/forms/index', $data);
    }
    
    public function gallery()
    {
        // For department admins, only show forms from their department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            $forms = $this->formModel->where('department_id', session()->get('scoped_department_id'))->findAll();
        } else {
            $forms = $this->formModel->findAll();
        }
        
        // Check which forms have templates
        foreach ($forms as &$form) {
            $templatePath = FCPATH . 'templates/docx/' . $form['code'] . '_template.docx';
            $form['has_template'] = file_exists($templatePath);
        }
        
        $data = [
            'title' => 'Forms Gallery',
            'forms' => $forms
        ];
        
        return view('admin/forms/gallery', $data);
    }
    
    public function new()
    {
        $data = [
            'title' => 'Add New Form'
        ];
        // Departments now primary (existing offices migrated to departments)
        $data['departments'] = $this->departmentModel->findAll();
        // Get available headers for selection
        $data['available_headers'] = $this->formModel->getAvailableHeaders();

        return view('admin/forms/create', $data);
    }
    
    public function create()
    {
        $rules = [
            'code' => 'required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code]',
            'description' => 'required|min_length[3]|max_length[255]',
            'department_id' => 'required|numeric|is_not_unique[departments.id]'
        ];

        if ($this->validate($rules)) {
            // Ensure we also set an office_id when possible by deriving from department
            $departmentId = (int)$this->request->getPost('department_id');
            
            // Department admins can only create forms in their own department
            if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
                if ($departmentId != session()->get('scoped_department_id')) {
                    return redirect()->back()
                        ->with('error', 'You can only create forms in your own department')
                        ->withInput();
                }
            }
            
            $office = $this->officeModel->where('department_id', $departmentId)->orderBy('id','ASC')->first();
            $officeId = $office['id'] ?? null;

            // Handle header image
            $headerImage = $this->handleHeaderImage();

            $this->formModel->save([
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'department_id' => $departmentId,
                'office_id' => $officeId,
                'header_image' => $headerImage
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
        
        if (!$form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found');
        }
        
        // Department admins can only edit forms in their own department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($form['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('/admin/forms')
                    ->with('error', 'You can only edit forms in your own department');
            }
        }
        
        $data = [
            'title' => 'Edit Form',
            'form' => $form
        ];
        // Provide departments for selection
        $data['departments'] = $this->departmentModel->findAll();
        // Get available headers for selection
        $data['available_headers'] = $this->formModel->getAvailableHeaders();

        return view('admin/forms/edit', $data);
    }
    
    public function update($id = null)
    {
        $form = $this->formModel->find($id);
        
        if (!$form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found');
        }
        
        // Department admins can only edit forms in their own department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($form['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('/admin/forms')
                    ->with('error', 'You can only edit forms in your own department');
            }
        }
        
        $rules = [
            'code' => "required|alpha_numeric|min_length[2]|max_length[20]|is_unique[forms.code,id,$id]",
            'description' => 'required|min_length[3]|max_length[255]',
            'department_id' => 'required|numeric|is_not_unique[departments.id]'
        ];

        if ($this->validate($rules)) {
            // Keep office in sync by deriving from department if possible
            $departmentId = (int)$this->request->getPost('department_id');
            
            // Department admins cannot change the department
            if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
                if ($departmentId != session()->get('scoped_department_id')) {
                    return redirect()->back()
                        ->with('error', 'You cannot change the department of forms')
                        ->withInput();
                }
            }
            
            $office = $this->officeModel->where('department_id', $departmentId)->orderBy('id','ASC')->first();
            $officeId = $office['id'] ?? null;

            // Handle header image
            $headerImage = $this->handleHeaderImage($form['header_image']);

            $this->formModel->update($id, [
                'code' => $this->request->getPost('code'),
                'description' => $this->request->getPost('description'),
                'department_id' => $departmentId,
                'office_id' => $officeId,
                'header_image' => $headerImage
            ]);

            return redirect()->to('/admin/forms')->with('message', 'Form updated successfully');
        } else {
            return redirect()->back()
                ->with('error', 'There was a problem updating the form')
                ->withInput()
                ->with('validation', $this->validator);
        }
    }

    /**
     * Handle header image upload or selection
     * Returns the filename of the header image to store in DB
     */
    protected function handleHeaderImage($currentHeader = null)
    {
        // Check if user wants to remove the header
        if ($this->request->getPost('remove_header') === '1') {
            return null;
        }

        // Check if a new file was uploaded
        $file = $this->request->getFile('header_upload');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                return $currentHeader;
            }
            
            // Validate file size (max 2MB)
            if ($file->getSize() > 2 * 1024 * 1024) {
                return $currentHeader;
            }
            
            // Create upload directory if it doesn't exist
            $uploadPath = FCPATH . 'uploads/form_headers/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Generate unique filename
            $newName = uniqid('header_') . '.' . $file->getExtension();
            $file->move($uploadPath, $newName);
            
            return $newName;
        }

        // Check if an existing header was selected
        $selectedHeader = $this->request->getPost('header_select');
        if (!empty($selectedHeader) && $selectedHeader !== 'none') {
            return $selectedHeader;
        }

        // Keep existing header if no changes
        return $currentHeader;
    }
    
    public function delete($id = null)
    {
        $form = $this->formModel->find($id);
        
        if (!$form) {
            return redirect()->to('/admin/forms')->with('error', 'Form not found');
        }
        
        // Department admins can only delete forms in their own department
        if (session()->get('is_department_admin') && session()->get('scoped_department_id')) {
            if ($form['department_id'] != session()->get('scoped_department_id')) {
                return redirect()->to('/admin/forms')
                    ->with('error', 'You can only delete forms in your own department');
            }
        }
        
        $this->formModel->delete($id);
        return redirect()->to('/admin/forms')->with('message', 'Form deleted successfully');
    }

    /**
     * Upload a new header image via AJAX
     */
    public function uploadHeader()
    {
        $file = $this->request->getFile('header_file');
        
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid file upload'
            ]);
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'
            ]);
        }
        
        // Validate file size (max 2MB)
        if ($file->getSize() > 2 * 1024 * 1024) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'File too large. Maximum size is 2MB.'
            ]);
        }
        
        // Create upload directory if it doesn't exist
        $uploadPath = FCPATH . 'uploads/form_headers/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Generate unique filename
        $newName = uniqid('header_') . '.' . $file->getExtension();
        $file->move($uploadPath, $newName);
        
        return $this->response->setJSON([
            'success' => true,
            'filename' => $newName,
            'url' => base_url('uploads/form_headers/' . $newName),
            'message' => 'Header uploaded successfully'
        ]);
    }

    /**
     * Delete a header image
     */
    public function deleteHeader()
    {
        $filename = $this->request->getPost('filename');
        
        if (empty($filename)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No filename provided'
            ]);
        }

        // Security: only allow deleting files in the headers directory
        $filePath = FCPATH . 'uploads/form_headers/' . basename($filename);
        
        if (file_exists($filePath)) {
            // Check if this header is being used by any form
            $formsUsingHeader = $this->formModel->where('header_image', $filename)->countAllResults();
            if ($formsUsingHeader > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "This header is currently used by {$formsUsingHeader} form(s). Remove it from those forms first."
                ]);
            }
            
            unlink($filePath);
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Header deleted successfully'
            ]);
        }
        
        return $this->response->setJSON([
            'success' => false,
            'message' => 'File not found'
        ]);
    }

    /**
     * Get list of available headers for AJAX
     */
    public function getHeaders()
    {
        $headers = $this->formModel->getAvailableHeaders();
        return $this->response->setJSON([
            'success' => true,
            'headers' => $headers
        ]);
    }
}
