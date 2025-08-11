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

    public function getUnread()
    {
        $userId = session()->get('user_id');
        
        $notifications = $this->notificationModel->getUserNotifications($userId, 10, true);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);
        
        return $this->response->setJSON([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    public function markAsRead($id = null)
    {
        $userId = session()->get('user_id');
        
        if ($id) {
            // Mark specific notification as read
            $result = $this->notificationModel->markAsRead($id, $userId);
        } else {
            // Mark all notifications as read
            $result = $this->notificationModel->markAllAsRead($userId);
        }
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $result,
                'message' => $result ? 'Notification(s) marked as read' : 'Failed to update notification'
            ]);
        }
        
        $message = $result ? 'Notification(s) marked as read' : 'Failed to update notification';
        return redirect()->back()->with($result ? 'success' : 'error', $message);
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
                return $this->response->setJSON(['success' => false, 'message' => 'Notification not found']);
            }
            return redirect()->back()->with('error', 'Notification not found');
        }
        
        $result = $this->notificationModel->delete($id);
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
            ]);
        }
        
        $message = $result ? 'Notification deleted' : 'Failed to delete notification';
        return redirect()->back()->with($result ? 'success' : 'error', $message);
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
