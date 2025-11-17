<?php

namespace App\Controllers;

class SessionDiagnostic extends BaseController
{
    /**
     * Display session diagnostic information
     * Access via: http://localhost:8080/session-diagnostic
     */
    public function index()
    {
        $session = session();
        $sessionData = $session->get();
        
        $data = [
            'title' => 'Session Diagnostic',
            'has_session' => !empty($sessionData),
            'session_data' => $sessionData,
            'user_id' => $session->get('user_id'),
            'user_type' => $session->get('user_type'),
            'username' => $session->get('username'),
            'full_name' => $session->get('full_name'),
            'department_id' => $session->get('department_id'),
            'office_id' => $session->get('office_id'),
            'is_department_admin' => $session->get('is_department_admin'),
            'is_logged_in' => $session->get('isLoggedIn'),
        ];
        
        return view('diagnostic/session', $data);
    }
}
