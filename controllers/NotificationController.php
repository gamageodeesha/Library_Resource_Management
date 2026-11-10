<?php
// The "Notifications" page (every role can open it)
class NotificationController extends Controller
{
    public function index()
    {
        $this->requireLogin();

        $notifications = new Notification($this->pdo);

        $tab = ($_GET['tab'] ?? 'all') === 'unread' ? 'unread' : 'all';

        // "Mark as read" buttons (they only ever change MY notifications)
        $this->handleNotificationPost('notifications', ['tab' => $tab]);

        $notes  = $notifications->latest($_SESSION['user_id'], 100, $tab === 'unread');
        $unread = $notifications->countUnread($_SESSION['user_id']);
        $total  = $notifications->countAll($_SESSION['user_id']);

        $this->render('notifications/index', [
            'page_title'  => 'Notifications',
            'active_page' => 'notifications',
            'tab'         => $tab,
            'notes'       => $notes,
            'unread'      => $unread,
            'total'       => $total,
        ]);
    }
}
