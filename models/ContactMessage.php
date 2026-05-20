<?php
class ContactMessage extends BaseModel
{
    public function create(array $data): bool
    {
        return $this->db->prepare("INSERT INTO contact_messages (name,email,subject,message,status,created_at) VALUES (?,?,?,?, 'new', NOW())")->execute([$data['name'],$data['email'],$data['subject'],$data['message']]);
    }
    public function all(): array
    {
        return $this->db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
    }
    public function markReviewed(int $id): bool
    {
        return $this->db->prepare("UPDATE contact_messages SET status='reviewed' WHERE id=?")->execute([$id]);
    }
}
