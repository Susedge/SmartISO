<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table = 'forms';
    protected $primaryKey = 'id';
    // Transitional: supporting department_id (new) and office_id (legacy)
    // Added header_image for form document header support
    protected $allowedFields = ['code', 'description', 'panel_name', 'office_id', 'department_id', 'header_image'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    // Disable soft deletes - table doesn't have deleted_at column
    protected $useSoftDeletes = false;

    /**
     * Get forms by office
     */
    public function getFormsByOffice($officeId = null)
    {
        if ($officeId === null) {
            return $this->findAll();
        }
        return $this->where('office_id', $officeId)->findAll();
    }

    /**
     * Get forms by department (preferred going forward)
     */
    public function getFormsByDepartment($departmentId = null)
    {
        if ($departmentId === null) {
            return $this->findAll();
        }
        return $this->where('department_id', $departmentId)->findAll();
    }

    /**
     * Get forms with office information
     */
    public function getFormsWithOffice()
    {
        return $this->db->table('forms f')
                       ->select('f.*, o.code as office_code, o.description as office_name')
                       ->join('offices o', 'o.id = f.office_id', 'left')
                       ->get()
                       ->getResultArray();
    }

    /**
     * Get forms with department info
     */
    public function getFormsWithDepartment()
    {
        return $this->db->table('forms f')
            ->select('f.*, d.code as department_code, d.description as department_name')
            ->join('departments d', 'd.id = f.department_id', 'left')
            ->get()
            ->getResultArray();
    }

    /**
     * Get forms by office with office information
     */
    public function getFormsByOfficeWithOffice($officeId = null)
    {
        $query = $this->db->table('forms f')
                         ->select('f.*, o.code as office_code, o.description as office_name')
                         ->join('offices o', 'o.id = f.office_id', 'left');

        if ($officeId !== null) {
            $query->where('f.office_id', $officeId);
        }

        return $query->get()->getResultArray();
    }

    public function getFormsByDepartmentWithDepartment($departmentId = null)
    {
        $query = $this->db->table('forms f')
            ->select('f.*, d.code as department_code, d.description as department_name')
            ->join('departments d', 'd.id = f.department_id', 'left');
        if ($departmentId !== null) {
            $query->where('f.department_id', $departmentId);
        }
        return $query->get()->getResultArray();
    }

    /**
     * Get all unique header images that have been uploaded
     * This is used for the header selection dropdown in the form editor
     */
    public function getAvailableHeaders()
    {
        $headerPath = FCPATH . 'uploads/form_headers/';
        $headers = [];
        
        if (is_dir($headerPath)) {
            $files = glob($headerPath . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            foreach ($files as $file) {
                $filename = basename($file);
                $headers[] = [
                    'filename' => $filename,
                    'url' => base_url('uploads/form_headers/' . $filename),
                    'name' => pathinfo($filename, PATHINFO_FILENAME)
                ];
            }
        }
        
        return $headers;
    }

    /**
     * Get the header image URL for a form
     */
    public function getHeaderImageUrl($formId)
    {
        $form = $this->find($formId);
        if (!$form || empty($form['header_image'])) {
            return null;
        }
        return base_url('uploads/form_headers/' . $form['header_image']);
    }
}
