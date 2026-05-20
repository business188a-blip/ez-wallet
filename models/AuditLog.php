<?php
class AuditLog extends BaseModel
{
    public function log(?int $userId, string $action, string $details): bool
    {
        return $this->db->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())")->execute([$userId,$action,$details]);
    }
}
