<?php
// The "notifications" table
class Notification extends Model
{
    // Save a notification for a user (does nothing if the user has no account)
    public function add($user_id, $type, $title, $message, $loan_id = null, $renewal_id = null)
    {
        if (empty($user_id)) {
            return;
        }
        $this->run(
            'INSERT INTO notifications (user_id, type, title, message, loan_id, renewal_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$user_id, $type, shorten($title, 160), shorten($message, 500), $loan_id, $renewal_id]
        );
    }

    public function countUnread($user_id)
    {
        return (int)$this->getValue('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user_id]);
    }

    public function countAll($user_id)
    {
        return (int)$this->getValue('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$user_id]);
    }

    // The newest notifications of one user (only my own: WHERE user_id = my id)
    public function latest($user_id, $limit, $unread_only = false)
    {
        $sql = 'SELECT * FROM notifications WHERE user_id = ?';
        if ($unread_only) {
            $sql .= ' AND is_read = 0';
        }
        $sql .= ' ORDER BY created_at DESC, notification_id DESC LIMIT ' . (int)$limit;
        return $this->getRows($sql, [$user_id]);
    }

    // "AND user_id = ..." means you can only change your OWN notifications
    public function markRead($notification_id, $user_id)
    {
        $this->run(
            'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ? AND user_id = ?',
            [$notification_id, $user_id]
        );
    }

    public function markAllRead($user_id)
    {
        $this->run('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0', [$user_id]);
    }
}
