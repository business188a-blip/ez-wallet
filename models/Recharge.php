<?php
class Recharge extends BaseModel
{
    public function create(array $data): int
    {
        $stmt=$this->db->prepare("INSERT INTO recharges (user_id,operator_id,phone_number,amount,reference_no,status,created_at) VALUES (:user_id,:operator_id,:phone_number,:amount,:reference_no,:status,NOW())");
        $stmt->execute([
            ':user_id'=>$data['user_id'], ':operator_id'=>$data['operator_id'], ':phone_number'=>$data['phone_number'],
            ':amount'=>$data['amount'], ':reference_no'=>$data['reference_no'], ':status'=>$data['status'] ?? 'completed'
        ]);
        return (int)$this->db->lastInsertId();
    }
    public function operators(): array
    {
        return $this->db->query("SELECT * FROM recharge_operators WHERE status='active' ORDER BY name")->fetchAll();
    }
    public function adminOperators(): array
    {
        return $this->db->query("SELECT * FROM recharge_operators ORDER BY name")->fetchAll();
    }
    public function createOperator(string $name, string $code): bool
    {
        return $this->db->prepare("INSERT INTO recharge_operators (name, code, status, created_at) VALUES (?, ?, 'active', NOW())")->execute([$name,$code]);
    }
    public function toggleOperator(int $id): bool
    {
        return $this->db->prepare("UPDATE recharge_operators SET status = IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
    }
}
