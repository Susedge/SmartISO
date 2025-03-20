<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'SmartISO - Intelligent ISO Management',
            'content' => 'Welcome to SmartISO - Your Intelligent ISO Management System'
        ];
        
        return view('landing', $data);
    }
}
