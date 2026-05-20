<?php
class AdminController extends BaseController
{
    public function dashboard(): void
    {
        require_admin(); $userModel=new User(); $txnModel=new Transaction();
        $this->view('admin/dashboard', ['title'=>'Admin Dashboard','counts'=>$userModel->counts(),'stats'=>$txnModel->stats(),'recentTransactions'=>array_slice($txnModel->all(),0,8),'dailySummary'=>$txnModel->dailySummary(14)], 'admin');
    }
    public function users(): void { require_admin(); $search=trim($_GET['search'] ?? ''); $this->view('admin/users', ['title'=>'Manage Users','users'=>(new User())->all($search),'search'=>$search], 'admin'); }
    public function toggleUserStatus(): void { require_admin(); verify_csrf(); (new User())->setStatus((int)($_POST['id'] ?? 0), trim($_POST['status'] ?? 'inactive')); flash('success','User status updated.'); redirect('index.php?route=admin/users'); }
    public function transactions(): void { require_admin(); $filters=['search'=>trim($_GET['search'] ?? ''),'type'=>trim($_GET['type'] ?? ''),'status'=>trim($_GET['status'] ?? '')]; $this->view('admin/transactions', ['title'=>'All Transactions','transactions'=>(new Transaction())->all($filters),'filters'=>$filters], 'admin'); }
    public function billCategories(): void { require_admin(); $this->view('admin/bill_categories', ['title'=>'Bill Categories','categories'=>(new BillPayment())->adminCategories()], 'admin'); }
    public function storeBillCategory(): void { require_admin(); verify_csrf(); $name=trim($_POST['name'] ?? ''); $code=trim($_POST['code'] ?? ''); if ($name && $code) { (new BillPayment())->createCategory($name,$code); flash('success','Bill category created.'); } else flash('error','Name and code are required.'); redirect('index.php?route=admin/bill-categories'); }
    public function toggleBillCategory(): void { require_admin(); verify_csrf(); (new BillPayment())->toggleCategory((int)($_POST['id'] ?? 0)); flash('success','Bill category status updated.'); redirect('index.php?route=admin/bill-categories'); }
    public function operators(): void { require_admin(); $this->view('admin/operators', ['title'=>'Recharge Operators','operators'=>(new Recharge())->adminOperators()], 'admin'); }
    public function storeOperator(): void { require_admin(); verify_csrf(); $name=trim($_POST['name'] ?? ''); $code=trim($_POST['code'] ?? ''); if ($name && $code) { (new Recharge())->createOperator($name,$code); flash('success','Recharge operator created.'); } else flash('error','Name and code are required.'); redirect('index.php?route=admin/operators'); }
    public function toggleOperator(): void { require_admin(); verify_csrf(); (new Recharge())->toggleOperator((int)($_POST['id'] ?? 0)); flash('success','Operator status updated.'); redirect('index.php?route=admin/operators'); }
    public function contactMessages(): void { require_admin(); $this->view('admin/contact_messages', ['title'=>'Contact Messages','messages'=>(new ContactMessage())->all()], 'admin'); }
    public function reviewContactMessage(): void { require_admin(); verify_csrf(); (new ContactMessage())->markReviewed((int)($_POST['id'] ?? 0)); flash('success','Message marked as reviewed.'); redirect('index.php?route=admin/contact-messages'); }
    public function reports(): void
    {
        require_admin(); $txnModel=new Transaction(); $all=$txnModel->all(); $totals=['credit'=>0,'debit'=>0]; foreach ($all as $row) { if ($row['direction']==='credit') $totals['credit'] += (float)$row['amount']; if ($row['direction']==='debit') $totals['debit'] += (float)$row['amount']; }
        $this->view('admin/reports', ['title'=>'Reports','dailySummary'=>$txnModel->dailySummary(30),'totals'=>$totals], 'admin');
    }
}
