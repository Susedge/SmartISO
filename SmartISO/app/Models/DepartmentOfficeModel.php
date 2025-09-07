<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentOfficeModel extends Model
{
    protected $table = 'department_office';
    protected $primaryKey = null; // composite
    protected $allowedFields = ['department_id', 'office_id'];
    public $timestamps = false;
}
