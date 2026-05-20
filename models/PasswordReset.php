<?php
class PasswordReset extends BaseModel
{
    public function createToken(string $email): string
    {
        $token = bin2hex(random_bytes(24));
        $this->db->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())")->execute([$email,$token]);
        return $token;
    }
    public function findValidToken(string $token): ?array
    {
        $stmt=$this->db->prepare("SELECT * FROM password_resets WHERE token=? AND expires_at > NOW() AND used_at IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$token]); return $stmt->fetch() ?: null;
    }
    public function markUsed(int $id): bool
    {
        return $this->db->prepare("UPDATE password_resets SET used_at=NOW() WHERE id=?")->execute([$id]);
    }
}
