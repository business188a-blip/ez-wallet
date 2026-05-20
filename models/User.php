<?php
class User extends BaseModel
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.id=?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username=?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO users (name, username, email, phone, password_hash, role, status, avatar, created_at, updated_at) VALUES (:name,:username,:email,:phone,:password_hash,:role,'active',:avatar,NOW(),NOW())");
        $stmt->execute([
            ':name'=>$data['name'], ':username'=>$data['username'], ':email'=>$data['email'], ':phone'=>$data['phone'],
            ':password_hash'=>$data['password_hash'], ':role'=>$data['role'] ?? 'user', ':avatar'=>$data['avatar'] ?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->db->prepare("INSERT INTO wallets (user_id, balance, created_at, updated_at) VALUES (?,0,NOW(),NOW())")->execute([$id]);
        return $id;
    }
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET name=:name, username=:username, phone=:phone, avatar=:avatar, updated_at=NOW() WHERE id=:id");
        return $stmt->execute([':name'=>$data['name'],':username'=>$data['username'],':phone'=>$data['phone'],':avatar'=>$data['avatar'],':id'=>$id]);
    }
    public function updatePassword(int $id, string $hash): bool
    {
        return $this->db->prepare("UPDATE users SET password_hash=?, updated_at=NOW() WHERE id=?")->execute([$hash,$id]);
    }
    public function all(string $search=''): array
    {
        if ($search) {
            $like='%'.$search.'%';
            $stmt=$this->db->prepare("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.name LIKE ? OR u.email LIKE ? OR u.username LIKE ? ORDER BY u.created_at DESC");
            $stmt->execute([$like,$like,$like]);
            return $stmt->fetchAll();
        }
        return $this->db->query("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id ORDER BY u.created_at DESC")->fetchAll();
    }
    public function setStatus(int $id, string $status): bool
    {
        return $this->db->prepare("UPDATE users SET status=?, updated_at=NOW() WHERE id=?")->execute([$status,$id]);
    }
    public function counts(): array
    {
        return [
            'total_users'=>(int)$this->db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn(),
            'active_users'=>(int)$this->db->query("SELECT COUNT(*) FROM users WHERE role='user' AND status='active'")->fetchColumn(),
        ];
    }
}
