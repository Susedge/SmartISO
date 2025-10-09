<?php

namespace App\Controllers;

class DebugData extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $data = [];
        
        // Get counts
        $data['form_submissions_count'] = $db->table('form_submissions')->countAll();
        $data['forms_count'] = $db->table('forms')->countAll();
        $data['users_count'] = $db->table('users')->countAll();
        $data['departments_count'] = $db->table('departments')->countAll();
        
        // Get status distribution
        $data['status_distribution'] = $db->table('form_submissions')
            ->select('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->getResultArray();
        
        // Get form_submissions columns
        $data['form_submissions_columns'] = $db->query('SHOW COLUMNS FROM form_submissions')->getResultArray();
        
        // Get sample submissions
        $data['sample_submissions'] = $db->table('form_submissions')
            ->limit(5)
            ->get()
            ->getResultArray();
        
        // Output as JSON
        return $this->response->setJSON($data);
    }
}
