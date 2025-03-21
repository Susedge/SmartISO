<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSignatureToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'signature' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'user_type',
                'comment' => 'Path to user signature file'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'signature');
    }
}
