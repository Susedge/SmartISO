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
        
        // Check session timeout (default 30 minutes, configurable)
        $sessionTimeout = $this->getSessionTimeout();
        $lastActivity = session()->get('last_activity');
        
        if ($lastActivity && (time() - $lastActivity) > $sessionTimeout) {
            // Session has expired
            session()->destroy();
            return redirect()->to('/auth/login')->with('error', 'Your session has expired. Please log in again.');
        }
        
        // Update last activity time
        session()->set('last_activity', time());
        
        // Check for specific user types if arguments are provided
        if (!empty($arguments)) {
            $userType = session()->get('user_type');
            if (!in_array($userType, $arguments)) {
                return redirect()->to('/dashboard')->with('error', 'You do not have permission to access that page');
            }
        }
    }
    
    /**
     * Get session timeout from configuration or default to 30 minutes
     */
    private function getSessionTimeout()
    {
        $db = \Config\Database::connect();
        
        try {
            // Try to get from configurations table
            $builder = $db->table('configurations');
            $config = $builder->where('config_key', 'session_timeout')->get()->getRow();
            
            if ($config && isset($config->config_value)) {
                return (int)$config->config_value * 60; // Convert minutes to seconds
            }
        } catch (\Exception $e) {
            // If configurations table doesn't exist or error occurs, use default
        }
        
        // Default to 30 minutes (1800 seconds)
        return 1800;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing after
    }
}
