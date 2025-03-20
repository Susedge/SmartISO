<?php

namespace App\Controllers;

use App\Models\DepartmentModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'User Dashboard - SmartISO'
        ];
        
        // Get user's department details
        if (session()->has('department_id')) {
            $departmentModel = new DepartmentModel();
            $data['department'] = $departmentModel->find(session()->get('department_id'));
        }
        
        return view('dashboard', $data);
    }
}
