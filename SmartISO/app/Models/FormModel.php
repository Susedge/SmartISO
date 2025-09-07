<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table = 'forms';
    protected $primaryKey = 'id';
    // Transitional: supporting department_id (new) and office_id (legacy)
    protected $allowedFields = ['code', 'description', 'panel_name', 'office_id', 'department_id'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

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
}
