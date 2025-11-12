<?php

namespace App\Controllers;

use App\Models\NotificationModel;

class Notifications extends BaseController
{
    protected $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $userId = session()->get('user_id');
        
        $data['title'] = 'Notifications';
        $data['notifications'] = $this->notificationModel->getUserNotifications($userId, 50);
        $data['unreadCount'] = $this->notificationModel->getUnreadCount($userId);
        
        return view('notifications/index', $data);
    }

    public function view($id)
    {
        $userId = session()->get('user_id');
        $userType = session()->get('user_type');

        // Ensure notification belongs to user
        $notification = $this->notificationModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$notification) {
            return redirect()->back()->with('error', 'Notification not found');
        }

        // Mark as read (idempotent)
        $this->notificationModel->markAsRead($id, $userId);

        // Enhanced routing: Direct approving authorities, admins, and dept admins to Review & Sign page for submissions
        if (!empty($notification['submission_id']) && in_array($userType, ['approving_authority', 'admin', 'superuser', 'department_admin'])) {
            // Check if this is a submission notification that needs approval
            $submissionModel = new \App\Models\FormSubmissionModel();
            $submission = $submissionModel->find($notification['submission_id']);
            
            if ($submission && $submission['status'] === 'submitted') {
                // Redirect to Review & Sign page for pending approvals
                return redirect()->to(base_url('forms/approve/' . $notification['submission_id']));
            }
        }

        // Prefer redirecting to the related submission when available (for other cases)
        if (!empty($notification['submission_id'])) {
            return redirect()->to(base_url('forms/submission/' . $notification['submission_id']));
        }

        // Otherwise, if action_url present, redirect there
        if (!empty($notification['action_url'])) {
            return redirect()->to($notification['action_url']);
        }

        // Fall back to the notifications list — we no longer render a standalone notification page
        return redirect()->to(base_url('notifications'));
    }

    public function getUnread()
    {
        $userId = session()->get('user_id');
        
        $notifications = $this->notificationModel->getUserNotifications($userId, 10, true);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        return $this->response->setJSON([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash()
        ]);
    }

    public function markAsRead($id = null)
    {
        $userId = session()->get('user_id');
        
        // If route parameter missing, check POST payload for id (robust fallback for AJAX clients)
        if (!$id && $this->request->getPost('id')) {
            $id = $this->request->getPost('id');
        }

        if ($id) {
            // Mark specific notification as read
            $result = $this->notificationModel->markAsRead($id, $userId);
            // Fallback: if model returns false, try direct DB update (helps in environments where Model::update may behave differently)
            if ($result === false) {
                try {
                    $db = \Config\Database::connect();
                    $builder = $db->table('notifications')->where('id', $id)->where('user_id', $userId)->update(['read' => 1]);
                    $result = ($builder !== false);
                } catch (\Exception $e) {
                    $result = false;
                    log_message('error', 'Notifications fallback DB update failed: ' . $e->getMessage());
                }
            }
        } else {
            // Mark all notifications as read
            $result = $this->notificationModel->markAllAsRead($userId);
        }
        // Normalize success: builder->update may return number of affected rows or true; treat false as failure
        $success = ($result !== false);

        // --- Debug logging: capture what the server received for diagnosis ---
        try {
            $debug = [
                'time' => date('c'),
                'user_id' => $userId,
                'route_id_param' => $id,
                'posted' => $this->request->getPost(),
                'rawInput' => $this->request->getBody(),
                'headers' => [
                    'X-CSRF-TOKEN' => $this->request->getHeaderLine('X-CSRF-TOKEN'),
                    'X-Requested-With' => $this->request->getHeaderLine('X-Requested-With')
                ],
                'result' => $result,
                'success' => $success
            ];
            // Append JSON line to writable logs so user can inspect without opening browser devtools
            $logPath = WRITEPATH . 'logs' . DIRECTORY_SEPARATOR . 'notifications_debug.log';
            @file_put_contents($logPath, json_encode($debug) . PHP_EOL, FILE_APPEND | LOCK_EX);
    } catch (\Exception $e) {
            // Swallow logging errors to avoid interfering with normal flow
            log_message('error', 'Failed to write notifications debug log: ' . $e->getMessage());
        }

        $message = $success ? 'Notification(s) marked as read' : 'Failed to update notification';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $success,
                'message' => $message,
                'result' => $result,
                'posted' => $this->request->getPost(),
                'rawInput' => $this->request->getBody(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash()
            ]);
        }

        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    public function delete($id)
    {
        $userId = session()->get('user_id');
        
        // Verify notification belongs to user
        $notification = $this->notificationModel->where('id', $id)
                                               ->where('user_id', $userId)
                                               ->first();
        
        if (!$notification) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Notification not found', 'csrfName' => csrf_token(), 'csrfHash' => csrf_hash()]);
            }
            return redirect()->back()->with('error', 'Notification not found');
        }
        
        $result = $this->notificationModel->delete($id);
        $success = ($result !== false);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $success,
                'message' => $success ? 'Notification deleted' : 'Failed to delete notification',
                'result' => $result,
                'posted' => $this->request->getPost(),
                'rawInput' => $this->request->getBody(),
                'csrfName' => csrf_token(),
                'csrfHash' => csrf_hash()
            ]);
        }

        $message = $success ? 'Notification deleted' : 'Failed to delete notification';
        return redirect()->back()->with($success ? 'success' : 'error', $message);
    }

    public function cleanup()
    {
        // Check if user is admin
        if (session()->get('user_type') !== 'admin') {
            return redirect()->back()->with('error', 'Access denied');
        }
        
        $deletedCount = $this->notificationModel->cleanupOldNotifications();
        
        return redirect()->back()->with('success', "Cleaned up {$deletedCount} old notifications");
    }
}
