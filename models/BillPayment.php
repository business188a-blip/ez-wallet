<?php
class BillPayment extends BaseModel
{
    public function create(array $data): int
    {
        $stmt=$this->db->prepare("INSERT INTO bill_payments (user_id,bill_category_id,account_number,amount,reference_no,status,created_at) VALUES (:user_id,:bill_category_id,:account_number,:amount,:reference_no,:status,NOW())");
        $stmt->execute([
            ':user_id'=>$data['user_id'], ':bill_category_id'=>$data['bill_category_id'], ':account_number'=>$data['account_number'],
            ':amount'=>$data['amount'], ':reference_no'=>$data['reference_no'], ':status'=>$data['status'] ?? 'completed'
        ]);
        return (int)$this->db->lastInsertId();
    }
    public function categories(): array
    {
        return $this->db->query("SELECT * FROM bill_categories WHERE status='active' ORDER BY name")->fetchAll();
    }
    public function adminCategories(): array
    {
        return $this->db->query("SELECT * FROM bill_categories ORDER BY name")->fetchAll();
    }
    public function createCategory(string $name, string $code): bool
    {
        return $this->db->prepare("INSERT INTO bill_categories (name, code, status, created_at) VALUES (?, ?, 'active', NOW())")->execute([$name,$code]);
    }
    public function toggleCategory(int $id): bool
    {
        return $this->db->prepare("UPDATE bill_categories SET status = IF(status='active','inactive','active') WHERE id=?")->execute([$id]);
    }
}
