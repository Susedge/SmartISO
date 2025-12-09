<?php
// Test the notification filtering through the NotificationModel

// Define constants
define('ROOTPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('APPPATH', ROOTPATH . 'app' . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', ROOTPATH . 'vendor/codeigniter4/framework/system/');
define('WRITEPATH', ROOTPATH . 'writable' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');

require __DIR__ . '/../vendor/autoload.php';

// Bootstrap CodeIgniter
$app = \Config\Services::codeigniter();
$app->initialize();

echo "=================================================================\n";
echo "NOTIFICATION FILTERING TEST (via NotificationModel)\n";
echo "=================================================================\n\n";

// Get department admin
$userModel = new \App\Models\UserModel();
$deptAdmins = $userModel->where('user_type', 'department_admin')
                        ->where('active', 1)
                        ->findAll();

if (empty($deptAdmins)) {
    echo "✗ No department admins found!\n";
    exit(1);
}

$deptAdmin = $deptAdmins[0];
echo "Testing with: {$deptAdmin['full_name']} (ID: {$deptAdmin['id']})\n";
echo "Department: {$deptAdmin['department_id']}\n\n";

// Test getUserNotifications
$notificationModel = new \App\Models\NotificationModel();
$notifications = $notificationModel->getUserNotifications($deptAdmin['id'], 50);

echo "=== FILTERED NOTIFICATIONS (via getUserNotifications) ===\n";
echo "Returned " . count($notifications) . " notification(s)\n\n";

if (empty($notifications)) {
    echo "✓ No notifications returned - filtering working correctly!\n";
    echo "  (Department admin from dept {$deptAdmin['department_id']} should not see notifications from other depts)\n\n";
} else {
    // Check each notification
    $wrongDeptCount = 0;
    $submissionModel = new \App\Models\FormSubmissionModel();
    
    foreach ($notifications as $notif) {
        echo "Notification ID: {$notif['id']} - {$notif['title']}\n";
        
        if (!empty($notif['submission_id'])) {
            $submission = $submissionModel->find($notif['submission_id']);
            if ($submission) {
                $submitter = $userModel->find($submission['submitted_by']);
                $submitterDept = $submitter['department_id'] ?? null;
                
                $match = ($submitterDept == $deptAdmin['department_id']) ? '✓' : '✗';
                if ($match === '✗') $wrongDeptCount++;
                
                echo "  {$match} Submitter Dept: {$submitterDept} | Dept Admin Dept: {$deptAdmin['department_id']}\n";
            }
        } else {
            echo "  ℹ Non-submission notification (allowed)\n";
        }
        echo "\n";
    }
    
    if ($wrongDeptCount > 0) {
        echo "✗ FILTER NOT WORKING: {$wrongDeptCount} notification(s) from other departments!\n";
    } else {
        echo "✓ FILTER WORKING: All notifications are from the correct department!\n";
    }
}

// Test getUnreadCount
echo "\n=== UNREAD COUNT TEST ===\n";
$unreadCount = $notificationModel->getUnreadCount($deptAdmin['id']);
echo "Unread count: {$unreadCount}\n";
echo "  (Should match filtered notification count)\n\n";

echo "=================================================================\n";
echo "TEST COMPLETE\n";
echo "=================================================================\n";
