<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApprovalColumnsToFormSubmissions extends Migration
{
    public function up()
    {
        // First check if approver_id already exists - don't try to add it again
        if (!$this->db->fieldExists('approver_id', 'form_submissions')) {
            $fields = [
                'approved_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                ],
                'approval_comments' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                ],
                'approver_id' => [
                    'type'    => 'INT',
                    'constraint' => 11,
                    'null'    => true,
                ],
                'rejected_reason' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                ],
                'signature_applied' => [
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ]
            ];
        } else {
            // If approver_id already exists, don't include it
            $fields = [
                'approved_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                ],
                'approval_comments' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                ],
                'rejected_reason' => [
                    'type'    => 'TEXT',
                    'null'    => true,
                ],
                'signature_applied' => [
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ]
            ];
        }
        
        $this->forge->addColumn('form_submissions', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('form_submissions', ['approved_at', 'approval_comments', 'rejected_reason', 'signature_applied']);
        
        // Only drop approver_id if it wasn't added by the other migration
        if ($this->db->fieldExists('approver_id', 'form_submissions')) {
            $this->forge->dropColumn('form_submissions', 'approver_id');
        }
    }
}
