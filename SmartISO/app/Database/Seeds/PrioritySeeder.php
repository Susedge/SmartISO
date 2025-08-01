<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PrioritySeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name' => 'low',
                'weight' => 1,
                'color' => '#28a745',
                'sla_hours' => 120,
                'description' => 'Low priority requests - 5 business days'
            ],
            [
                'name' => 'normal',
                'weight' => 2,
                'color' => '#6c757d',
                'sla_hours' => 72,
                'description' => 'Normal priority requests - 3 business days'
            ],
            [
                'name' => 'high',
                'weight' => 3,
                'color' => '#ffc107',
                'sla_hours' => 48,
                'description' => 'High priority requests - 2 business days'
            ],
            [
                'name' => 'urgent',
                'weight' => 4,
                'color' => '#fd7e14',
                'sla_hours' => 24,
                'description' => 'Urgent requests - 1 business day'
            ],
            [
                'name' => 'critical',
                'weight' => 5,
                'color' => '#dc3545',
                'sla_hours' => 8,
                'description' => 'Critical requests - same day response'
            ]
        ];

        // Using Query Builder to insert data
        $this->db->table('priority_configurations')->insertBatch($data);
    }
}
