<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class DepartmentAdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $userType = session()->get('user_type');
        
        // Only allow admin, department_admin, and superuser to access admin routes
        if (!in_array($userType, ['admin', 'department_admin', 'superuser'])) {
            return redirect()->to('/dashboard')->with('error', 'You do not have permission to access admin panel');
        }
        
        // Store department context for department_admin users
        if ($userType === 'department_admin') {
            $departmentId = session()->get('department_id');
            
            // Ensure department_admin has a department assigned
            if (empty($departmentId)) {
                return redirect()->to('/dashboard')->with('error', 'Your account is not assigned to a department. Please contact an administrator.');
            }
            
            // Store this in session for easy access in controllers
            session()->set('is_department_admin', true);
            session()->set('scoped_department_id', $departmentId);
        } else {
            // For global admins and superusers
            session()->set('is_department_admin', false);
            session()->set('scoped_department_id', null);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after
    }
}
