<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\ConfigurationModel;

class BackupDatabase extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:backup';
    protected $description = 'Create a database backup';
    protected $usage       = 'db:backup';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        CLI::write('Starting database backup...', 'yellow');
        
        // Check if auto backup is enabled
        $configModel = new ConfigurationModel();
        $autoBackupEnabled = (bool)$configModel->getConfig('auto_backup_enabled', false);
        
        if (!$autoBackupEnabled) {
            CLI::write('Automatic backups are disabled in system settings.', 'red');
            CLI::write('To enable: Go to Admin > Configurations > System Settings > Edit auto_backup_enabled', 'yellow');
            return;
        }
        
        try {
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

            CLI::write('Creating backup file: ' . $filename, 'green');

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

            CLI::write('Backing up ' . count($tables) . ' tables...', 'yellow');

            foreach ($tables as $table) {
                CLI::print('  - ' . $table . '... ');
                
                fwrite($fp, "--\n-- Structure for table `{$table}`\n--\n\n");
                fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");

                // Get table structure
                $createResult = $db->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createResult->getRowArray();
                $createSql = $createRow['Create Table'] ?? array_values($createRow)[1] ?? '';
                fwrite($fp, $createSql . ";\n\n");

                // Get table data
                $rowsResult = $db->query("SELECT * FROM `{$table}`");
                $rowCount = $rowsResult->getNumRows();
                
                if ($rowCount > 0) {
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
                
                CLI::write('Done (' . $rowCount . ' rows)', 'green');
                fflush($fp);
            }

            // Write footer
            fwrite($fp, "COMMIT;\n\n");
            fwrite($fp, "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
            fwrite($fp, "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
            fwrite($fp, "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");

            fclose($fp);

            $fileSize = filesize($filepath);
            CLI::newLine();
            CLI::write('Backup completed successfully!', 'green');
            CLI::write('File: ' . $filepath, 'white');
            CLI::write('Size: ' . number_format($fileSize / 1024 / 1024, 2) . ' MB', 'white');
            
        } catch (\Exception $e) {
            CLI::error('Backup failed: ' . $e->getMessage());
            log_message('error', 'Scheduled backup failed: ' . $e->getMessage());
        }
    }
}
