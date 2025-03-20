<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\DepartmentModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $departmentModel = new DepartmentModel();
        
        $data = [
            'title' => 'Admin Dashboard',
            'userCount' => $userModel->countAll(),
            'departmentCount' => $departmentModel->countAll(),
            'userTypes' => $userModel->select('user_type, COUNT(*) as count')
                                     ->groupBy('user_type')
                                     ->findAll(),
            'latestUsers' => $userModel->orderBy('created_at', 'DESC')
                                      ->limit(5)
                                      ->findAll()
        ];
        
        return view('admin/dashboard', $data);
    }
}
