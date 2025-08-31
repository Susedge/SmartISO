<?php

namespace App\Models;

use CodeIgniter\Model;

class FormModel extends Model
{
    protected $table = 'forms';
    protected $primaryKey = 'id';
    protected $allowedFields = ['code', 'description', 'panel_name', 'office_id'];
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
}
