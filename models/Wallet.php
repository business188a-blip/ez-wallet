<?php
class Wallet extends BaseModel
{
    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id=?");
        $stmt->execute([$userId]);
        return (float)($stmt->fetchColumn() ?: 0);
    }
    public function adjustBalance(int $userId, float $amount): bool
    {
        return $this->db->prepare("UPDATE wallets SET balance = balance + ?, updated_at=NOW() WHERE user_id=?")->execute([$amount,$userId]);
    }
}
