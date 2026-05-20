<?php
class Notification extends BaseModel
{
    public function create(int $userId, string $title, string $message): bool
    {
        return $this->db->prepare("INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?,?,?,0,NOW())")->execute([$userId,$title,$message]);
    }
    public function getByUser(int $userId, int $limit=10): array
    {
        $stmt=$this->db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function allByUser(int $userId): array
    {
        $stmt=$this->db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    public function unreadCount(int $userId): int
    {
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    public function markRead(int $id, int $userId): bool
    {
        return $this->db->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id,$userId]);
    }
}
