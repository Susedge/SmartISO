<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class Api extends ResourceController
{
    /**
     * Get current time in system timezone
     */
    public function currentTime()
    {
        helper('timezone');
        
        $data = [
            'time' => now_in_timezone('M j, Y g:i A T'),
            'timezone' => get_system_timezone()
        ];
        
        return $this->respond($data);
    }
}
