<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ConfigurationModel;

class DatabaseBackup extends BaseController
{
    protected $configurationModel;

    public function __construct()
    {
        $this->configurationModel = new ConfigurationModel();
    }

    /**
     * Display backup management page
     */
    public function index()
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        
        // Create backup directory if it doesn't exist
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Get list of backup files
        $backups = [];
        $files = glob($backupDir . 'db_backup_*.sql');
        
        if ($files) {
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            foreach ($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'filepath' => $file,
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }
        }

        // Get backup settings
        $autoBackupEnabled = (bool)$this->configurationModel->getConfig('auto_backup_enabled', false);
        $backupTime = $this->configurationModel->getConfig('backup_time', '02:00');

        $data = [
            'title' => 'Database Backup Management',
            'backups' => $backups,
            'autoBackupEnabled' => $autoBackupEnabled,
            'backupTime' => $backupTime
        ];

        return view('admin/database_backup/index', $data);
    }

    /**
     * Create a manual backup
     */
    public function create()
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
            }
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        try {
            $result = $this->performBackup();
            
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'filename' => $result['filename'],
                    'csrfHash' => csrf_hash()
                ]);
            }
            
            return redirect()->to('/admin/database-backup')
                ->with('message', 'Backup created successfully: ' . $result['filename']);
        } catch (\Exception $e) {
            log_message('error', 'Backup failed: ' . $e->getMessage());
            
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Backup failed: ' . $e->getMessage()
                ]);
            }
            
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Perform the actual backup
     */
    private function performBackup()
    {
        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'db_backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . $filename;

        $db = \Config\Database::connect();
        
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            throw new \Exception('Failed to create backup file');
        }

        // Write header with SQL settings
        fwrite($fp, "-- Database Backup\n");
        fwrite($fp, "-- Generated: " . date('c') . "\n");
        fwrite($fp, "-- Database: " . $db->database . "\n\n");
        fwrite($fp, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
        fwrite($fp, "SET AUTOCOMMIT = 0;\n");
        fwrite($fp, "START TRANSACTION;\n");
        fwrite($fp, "SET time_zone = \"+00:00\";\n\n");
        fwrite($fp, "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        fwrite($fp, "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        fwrite($fp, "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        fwrite($fp, "/*!40101 SET NAMES utf8mb4 */;\n\n");

        // Get all tables
        $tablesResult = $db->query('SHOW TABLES');
        $tables = [];
        
        foreach ($tablesResult->getResultArray() as $row) {
            $tables[] = array_values($row)[0];
        }

        foreach ($tables as $table) {
            fwrite($fp, "--\n-- Structure for table `{$table}`\n--\n\n");
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

            // Get table structure
            $createResult = $db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createResult->getRowArray();
            $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
            fwrite($fp, $createSql . ";\n\n");

            // Get table data
            $rowsResult = $db->query("SELECT * FROM `{$table}`");
            if ($rowsResult->getNumRows() > 0) {
                fwrite($fp, "--\n-- Dumping data for table `{$table}`\n--\n\n");
                
                foreach ($rowsResult->getResultArray() as $row) {
                    $cols = array_map(function($c) {
                        return "`" . str_replace('`', '``', $c) . "`";
                    }, array_keys($row));
                    
                    $vals = array_map(function($v) use ($db) {
                        if ($v === null) return 'NULL';
                        return "'" . $db->escapeString($v) . "'";
                    }, array_values($row));
                    
                    fwrite($fp, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n");
                }
                fwrite($fp, "\n");
            }
            
            fflush($fp);
        }

        // Write footer
        fwrite($fp, "COMMIT;\n\n");
        fwrite($fp, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
        fwrite($fp, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
        fwrite($fp, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

        fclose($fp);

        // Rotate backups - keep last 14 files
        $files = glob($backupDir . 'db_backup_*.sql');
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $keep = 14;
        if (count($files) > $keep) {
            $toDelete = array_slice($files, $keep);
            foreach ($toDelete as $del) {
                @unlink($del);
            }
        }

        return [
            'filename' => $filename,
            'filepath' => $filepath
        ];
    }

    /**
     * Download a backup file
     */
    public function download($filename = null)
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        if (!$filename) {
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid backup file');
        }

        // Validate filename to prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid filename');
        }

        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $filepath = $backupDir . $filename;

        if (!file_exists($filepath)) {
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Backup file not found');
        }

        return $this->response->download($filepath, null);
    }

    /**
     * Delete a backup file
     */
    public function delete($filename = null)
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
            }
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        if (!$filename) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid backup file'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid backup file');
        }

        // Validate filename to prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid filename'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid filename');
        }

        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $filepath = $backupDir . $filename;

        if (!file_exists($filepath)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Backup file not found'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Backup file not found');
        }

        if (unlink($filepath)) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Backup deleted successfully',
                    'csrfHash' => csrf_hash()
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('message', 'Backup deleted successfully');
        } else {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Failed to delete backup'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Failed to delete backup');
        }
    }

    /**
     * Restore database from backup
     */
    public function restore($filename = null)
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
            }
            return redirect()->to('/dashboard')->with('error', 'Unauthorized access');
        }

        if (!$filename) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid backup file'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid backup file');
        }

        // Validate filename to prevent directory traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'Invalid filename'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Invalid filename');
        }

        $backupDir = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR;
        $filepath = $backupDir . $filename;

        if (!file_exists($filepath)) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Backup file not found'
                ]);
            }
            return redirect()->to('/admin/database-backup')
                ->with('error', 'Backup file not found');
        }

        try {
            // Create a safety backup before restoration
            $this->performBackup();

            $db = \Config\Database::connect();
            
            // Read and execute SQL file
            $sql = file_get_contents($filepath);
            
            if ($sql === false) {
                throw new \Exception('Failed to read backup file');
            }

            // Disable foreign key checks temporarily
            $db->query('SET FOREIGN_KEY_CHECKS = 0');
            $db->query('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');
            $db->query('SET AUTOCOMMIT = 0');
            $db->query('START TRANSACTION');

            // Split SQL into individual statements
            // Remove comments and split by semicolons
            $lines = explode("\n", $sql);
            $statement = '';
            
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Skip empty lines and comments
                if (empty($line) || substr($line, 0, 2) == '--' || substr($line, 0, 2) == '/*') {
                    continue;
                }
                
                $statement .= ' ' . $line;
                
                // If statement ends with semicolon, execute it
                if (substr(rtrim($line), -1) == ';') {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $db->query($statement);
                        } catch (\Exception $e) {
                            // Log but continue with other statements
                            log_message('error', 'SQL statement error: ' . $e->getMessage() . ' | Statement: ' . substr($statement, 0, 200));
                        }
                    }
                    $statement = '';
                }
            }

            // Commit transaction
            $db->query('COMMIT');
            
            // Re-enable foreign key checks
            $db->query('SET FOREIGN_KEY_CHECKS = 1');

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Database restored successfully from ' . $filename,
                    'csrfHash' => csrf_hash()
                ]);
            }

            return redirect()->to('/admin/database-backup')
                ->with('message', 'Database restored successfully from ' . $filename);
        } catch (\Exception $e) {
            log_message('error', 'Restore failed: ' . $e->getMessage());
            
            // Rollback on error
            try {
                $db->query('ROLLBACK');
                $db->query('SET FOREIGN_KEY_CHECKS = 1');
            } catch (\Exception $rollbackEx) {
                log_message('error', 'Rollback failed: ' . $rollbackEx->getMessage());
            }

            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'Restore failed: ' . $e->getMessage()
                ]);
            }

            return redirect()->to('/admin/database-backup')
                ->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle auto backup setting
     */
    public function toggleAutoBackup()
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }

        $enabled = $this->request->getPost('enabled');
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        try {
            $this->configurationModel->setConfig(
                'auto_backup_enabled',
                $enabled,
                'Enable automatic database backups',
                'boolean'
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Auto backup ' . ($enabled ? 'enabled' : 'disabled'),
                'enabled' => $enabled,
                'csrfHash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to update setting: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update backup time
     */
    public function updateBackupTime()
    {
        // Only allow admins and superusers
        $userType = session()->get('user_type');
        if (!in_array($userType, ['admin', 'superuser'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Unauthorized access'
            ]);
        }

        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ]);
        }

        $time = $this->request->getPost('time');

        // Validate time format (HH:MM)
        if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid time format. Use HH:MM (24-hour format)'
            ]);
        }

        try {
            $this->configurationModel->setConfig(
                'backup_time',
                $time,
                'Scheduled time for automatic database backups',
                'string'
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Backup time updated to ' . $time,
                'time' => $time,
                'csrfHash' => csrf_hash()
            ]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Failed to update time: ' . $e->getMessage()
            ]);
        }
    }
}
