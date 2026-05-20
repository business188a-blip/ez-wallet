<?php
class Transaction extends BaseModel
{
    public function create(array $data): int
    {
        $stmt=$this->db->prepare("INSERT INTO transactions (reference_no,user_id,related_user_id,type,direction,amount,fee,description,status,created_at) VALUES (:reference_no,:user_id,:related_user_id,:type,:direction,:amount,:fee,:description,:status,NOW())");
        $stmt->execute([
            ':reference_no'=>$data['reference_no'], ':user_id'=>$data['user_id'], ':related_user_id'=>$data['related_user_id'] ?? null,
            ':type'=>$data['type'], ':direction'=>$data['direction'], ':amount'=>$data['amount'], ':fee'=>$data['fee'] ?? 0,
            ':description'=>$data['description'] ?? null, ':status'=>$data['status'] ?? 'completed'
        ]);
        return (int)$this->db->lastInsertId();
    }
    public function getByUser(int $userId, array $filters=[]): array
    {
        $sql="SELECT t.*, u.name AS related_name, u.email AS related_email FROM transactions t LEFT JOIN users u ON u.id=t.related_user_id WHERE t.user_id=:user_id";
        $params=[':user_id'=>$userId];
        if (!empty($filters['type'])) {{$sql.=" AND t.type=:type"; $params[':type']=$filters['type'];}}
        if (!empty($filters['status'])) {{$sql.=" AND t.status=:status"; $params[':status']=$filters['status'];}}
        if (!empty($filters['date_from'])) {{$sql.=" AND DATE(t.created_at)>=:date_from"; $params[':date_from']=$filters['date_from'];}}
        if (!empty($filters['date_to'])) {{$sql.=" AND DATE(t.created_at)<=:date_to"; $params[':date_to']=$filters['date_to'];}}
        $sql.=" ORDER BY t.created_at DESC";
        $stmt=$this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function findByIdForUser(int $id, int $userId): ?array
    {
        $stmt=$this->db->prepare("SELECT t.*, u.name AS related_name, u.email AS related_email FROM transactions t LEFT JOIN users u ON u.id=t.related_user_id WHERE t.id=? AND t.user_id=?");
        $stmt->execute([$id,$userId]); return $stmt->fetch() ?: null;
    }
    public function recentByUser(int $userId, int $limit=5): array
    {
        $stmt=$this->db->prepare("SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT {$limit}");
        $stmt->execute([$userId]); return $stmt->fetchAll();
    }
    public function all(array $filters=[]): array
    {
        $sql="SELECT t.*, u.name, u.email FROM transactions t INNER JOIN users u ON u.id=t.user_id WHERE 1=1"; $params=[];
        if (!empty($filters['type'])) {{$sql.=" AND t.type=:type"; $params[':type']=$filters['type'];}}
        if (!empty($filters['status'])) {{$sql.=" AND t.status=:status"; $params[':status']=$filters['status'];}}
        if (!empty($filters['search'])) {{$sql.=" AND (t.reference_no LIKE :search OR u.name LIKE :search OR u.email LIKE :search)"; $params[':search']='%'.$filters['search'].'%';}}
        $sql.=" ORDER BY t.created_at DESC"; $stmt=$this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function stats(): array
    {
        return [
            'total_transactions'=>(int)$this->db->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
            'wallet_activity'=>(float)$this->db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status='completed'")->fetchColumn(),
            'bill_payments'=>(int)$this->db->query("SELECT COUNT(*) FROM bill_payments")->fetchColumn(),
            'recharges'=>(int)$this->db->query("SELECT COUNT(*) FROM recharges")->fetchColumn(),
        ];
    }
    public function dailySummary(int $days=7): array
    {
        $stmt=$this->db->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS total_count, SUM(amount) AS total_amount FROM transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY day ASC");
        $stmt->execute([$days]); return $stmt->fetchAll();
    }
}
