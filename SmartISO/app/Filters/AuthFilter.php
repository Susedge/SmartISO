<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // If user is not logged in, redirect to login page
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login')->with('error', 'You must be logged in to access this page');
        }
        
        // Check for specific user types if arguments are provided
        if (!empty($arguments)) {
            $userType = session()->get('user_type');
            if (!in_array($userType, $arguments)) {
                return redirect()->to('/dashboard')->with('error', 'You do not have permission to access that page');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after
    }
}
