<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DedupeSchedulesAndAddUniqueConstraint extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Step 1: Find duplicates and log a summary (non-fatal)
        try {
            $sql = "SELECT submission_id, COUNT(id) AS cnt FROM schedules GROUP BY submission_id HAVING COUNT(id) > 1";
            $rows = $db->query($sql)->getResultArray();
            log_message('info', 'DedupeSchedules: Found ' . count($rows) . ' submission(s) with duplicate schedules');
        } catch (\Throwable $e) {
            log_message('error', 'DedupeSchedules: failed to query duplicates: ' . $e->getMessage());
        }

        // Step 2: Remove duplicate rows, keeping the row with the largest id for each submission_id
        // We use a safe nested subquery wrapper to avoid MySQL delete limitations on the same table
        try {
            $deleteSql = "DELETE FROM schedules WHERE id NOT IN (SELECT keep_id FROM (SELECT MAX(id) AS keep_id FROM schedules GROUP BY submission_id) AS keepers)";
            $db->query($deleteSql);
            log_message('info', 'DedupeSchedules: removed duplicate schedule rows keeping highest-id per submission');
        } catch (\Throwable $e) {
            // If for any reason deletion fails, log and continue; do not leave migration broken
            log_message('error', 'DedupeSchedules: failed to delete duplicate rows: ' . $e->getMessage());
            throw $e;
        }

        // Step 3: Add unique constraint on submission_id
        try {
            $forge = \Config\Database::forge();
            // Add unique index; name set explicitly for portability
            $forge->addKey('submission_id', false, true);
            // MySQL's addKey doesn't always support unique via forge when table exists, so run explicit SQL for portability
            $db->query('ALTER TABLE schedules ADD CONSTRAINT uk_schedules_submission_id UNIQUE (submission_id)');
            log_message('info', 'DedupeSchedules: added unique constraint uk_schedules_submission_id on schedules(submission_id)');
        } catch (\Throwable $e) {
            // If adding unique constraint fails (e.g., race condition), rollback the delete is not done here -- it's destructive and should be verified before deploy
            log_message('error', 'DedupeSchedules: failed to add unique constraint: ' . $e->getMessage());
            throw $e;
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        try {
            // Remove unique constraint if exists
            $db->query('ALTER TABLE schedules DROP INDEX uk_schedules_submission_id');
            log_message('info', 'DedupeSchedules: dropped unique index uk_schedules_submission_id');
        } catch (\Throwable $e) {
            log_message('warning', 'DedupeSchedules: unable to drop index during rollback: ' . $e->getMessage());
        }

        // Note: We do not restore previously-deleted duplicate rows. Rollback is best-effort for the constraint only.
    }
}
