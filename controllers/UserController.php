<?php
class UserController extends BaseController
{
    public function dashboard(): void
    {
        require_login(); $user=auth_user(); $tx=new Transaction(); $note=new Notification();
        $this->view('user/dashboard', ['title'=>'Dashboard','user'=>$user,'recentTransactions'=>$tx->recentByUser((int)$user['id'],6),'notifications'=>$note->getByUser((int)$user['id'],6),'unreadCount'=>$note->unreadCount((int)$user['id'])], 'dashboard');
    }
    public function profile(): void { require_login(); $this->view('user/profile', ['title'=>'Profile','user'=>auth_user()], 'dashboard'); }
    public function updateProfile(): void
    {
        require_login(); verify_csrf(); $user=auth_user(); $userModel=new User();
        $name=trim($_POST['name'] ?? ''); $username=trim($_POST['username'] ?? ''); $phone=trim($_POST['phone'] ?? '');
        if ($name==='' || $username==='' || $phone==='') { flash('error','Name, username, and phone are required.'); redirect('index.php?route=profile'); }
        $avatar=$user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $config=app_config(); if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'],0775,true);
            $ext=strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','webp'],true)) { $fileName=uniqid('avatar_',true).'.'.$ext; if (move_uploaded_file($_FILES['avatar']['tmp_name'],$config['upload_dir'].'/'.$fileName)) $avatar=$fileName; }
        }
        $userModel->updateProfile((int)$user['id'], ['name'=>$name,'username'=>$username,'phone'=>$phone,'avatar'=>$avatar]);
        (new AuditLog())->log((int)$user['id'],'profile_update','Updated profile'); flash('success','Profile updated successfully.'); redirect('index.php?route=profile');
    }
    public function updatePassword(): void
    {
        require_login(); verify_csrf(); $user=auth_user(); $current=(string)($_POST['current_password'] ?? ''); $password=(string)($_POST['password'] ?? ''); $confirm=(string)($_POST['confirm_password'] ?? '');
        if (!password_verify($current, $user['password_hash'])) { flash('error','Current password is incorrect.'); redirect('index.php?route=profile'); }
        if (strlen($password)<8 || $password!==$confirm) { flash('error','New password must be at least 8 characters and both fields must match.'); redirect('index.php?route=profile'); }
        (new User())->updatePassword((int)$user['id'], password_hash($password,PASSWORD_DEFAULT)); (new Notification())->create((int)$user['id'],'Password updated','Your account password was changed.'); flash('success','Password changed successfully.'); redirect('index.php?route=profile');
    }
    public function addMoney(): void { require_login(); $this->view('user/add_money', ['title'=>'Add Money','user'=>auth_user()], 'dashboard'); }
    public function doAddMoney(): void
    {
        require_login(); verify_csrf(); $user=auth_user(); $amount=(float)($_POST['amount'] ?? 0); if ($amount<=0) { flash('error','Please enter a valid amount.'); redirect('index.php?route=wallet/add-money'); }
        $db=Database::getInstance();
        try { $db->beginTransaction(); (new Wallet())->adjustBalance((int)$user['id'],$amount); $ref=generate_reference('TOP'); (new Transaction())->create(['reference_no'=>$ref,'user_id'=>$user['id'],'type'=>'topup','direction'=>'credit','amount'=>$amount,'description'=>'Wallet top-up simulation']); (new Notification())->create((int)$user['id'],'Money added','Your wallet was credited with '.money($amount).'.'); $db->commit(); flash('success','Money added successfully.'); } catch (Throwable $e) { $db->rollBack(); flash('error','Unable to add money right now.'); }
        redirect('index.php?route=dashboard');
    }
    public function sendMoney(): void { require_login(); $this->view('user/send_money', ['title'=>'Send Money','user'=>auth_user()], 'dashboard'); }
    public function doSendMoney(): void
    {
        require_login(); verify_csrf(); $sender=auth_user(); $recipientIdentifier=trim($_POST['recipient'] ?? ''); $amount=(float)($_POST['amount'] ?? 0); $description=trim($_POST['description'] ?? '');
        if ($recipientIdentifier==='' || $amount<=0) { flash('error','Recipient and valid amount are required.'); redirect('index.php?route=wallet/send-money'); }
        $userModel=new User(); $recipient=filter_var($recipientIdentifier,FILTER_VALIDATE_EMAIL) ? $userModel->findByEmail($recipientIdentifier) : $userModel->findByUsername($recipientIdentifier);
        if (!$recipient || $recipient['status'] !== 'active') { flash('error','Recipient account not found or inactive.'); redirect('index.php?route=wallet/send-money'); }
        if ((int)$recipient['id']===(int)$sender['id']) { flash('error','You cannot send money to yourself.'); redirect('index.php?route=wallet/send-money'); }
        $wallet=new Wallet(); if ($wallet->getBalance((int)$sender['id']) < $amount) { flash('error','Insufficient wallet balance.'); redirect('index.php?route=wallet/send-money'); }
        $db=Database::getInstance();
        try {
            $db->beginTransaction(); $reference=generate_reference('TRF'); $wallet->adjustBalance((int)$sender['id'],-$amount); $wallet->adjustBalance((int)$recipient['id'],$amount);
            $txn=new Transaction(); $txn->create(['reference_no'=>$reference,'user_id'=>$sender['id'],'related_user_id'=>$recipient['id'],'type'=>'transfer','direction'=>'debit','amount'=>$amount,'description'=>$description ?: 'Money sent to '.$recipient['name']]);
            $txn->create(['reference_no'=>$reference,'user_id'=>$recipient['id'],'related_user_id'=>$sender['id'],'type'=>'transfer','direction'=>'credit','amount'=>$amount,'description'=>'Money received from '.$sender['name']]);
            $note=new Notification(); $note->create((int)$sender['id'],'Money sent','You sent '.money($amount).' to '.$recipient['name'].'.'); $note->create((int)$recipient['id'],'Money received','You received '.money($amount).' from '.$sender['name'].'.');
            $db->commit(); flash('success','Money sent successfully.');
        } catch (Throwable $e) { $db->rollBack(); flash('error','Unable to process transfer right now.'); }
        redirect('index.php?route=dashboard');
    }
    public function billPayment(): void { require_login(); $this->view('user/bill_payment', ['title'=>'Bill Payment','categories'=>(new BillPayment())->categories(),'user'=>auth_user()], 'dashboard'); }
    public function doBillPayment(): void
    {
        require_login(); verify_csrf(); $user=auth_user(); $categoryId=(int)($_POST['bill_category_id'] ?? 0); $accountNumber=trim($_POST['account_number'] ?? ''); $amount=(float)($_POST['amount'] ?? 0);
        if ($categoryId<=0 || $accountNumber==='' || $amount<=0) { flash('error','All bill payment fields are required.'); redirect('index.php?route=wallet/bill-payment'); }
        $wallet=new Wallet(); if ($wallet->getBalance((int)$user['id']) < $amount) { flash('error','Insufficient wallet balance.'); redirect('index.php?route=wallet/bill-payment'); }
        $db=Database::getInstance();
        try { $db->beginTransaction(); $ref=generate_reference('BIL'); $wallet->adjustBalance((int)$user['id'],-$amount); (new BillPayment())->create(['user_id'=>$user['id'],'bill_category_id'=>$categoryId,'account_number'=>$accountNumber,'amount'=>$amount,'reference_no'=>$ref]); (new Transaction())->create(['reference_no'=>$ref,'user_id'=>$user['id'],'type'=>'bill_payment','direction'=>'debit','amount'=>$amount,'description'=>'Bill payment for account '.$accountNumber]); (new Notification())->create((int)$user['id'],'Bill payment completed','Your bill payment of '.money($amount).' was successful.'); $db->commit(); flash('success','Bill payment completed successfully.'); } catch (Throwable $e) { $db->rollBack(); flash('error','Unable to process bill payment.'); }
        redirect('index.php?route=dashboard');
    }
    public function recharge(): void { require_login(); $this->view('user/recharge', ['title'=>'Mobile Recharge','operators'=>(new Recharge())->operators(),'user'=>auth_user()], 'dashboard'); }
    public function doRecharge(): void
    {
        require_login(); verify_csrf(); $user=auth_user(); $operatorId=(int)($_POST['operator_id'] ?? 0); $phone=trim($_POST['phone_number'] ?? ''); $amount=(float)($_POST['amount'] ?? 0);
        if ($operatorId<=0 || $phone==='' || $amount<=0) { flash('error','All recharge fields are required.'); redirect('index.php?route=wallet/recharge'); }
        $wallet=new Wallet(); if ($wallet->getBalance((int)$user['id']) < $amount) { flash('error','Insufficient wallet balance.'); redirect('index.php?route=wallet/recharge'); }
        $db=Database::getInstance();
        try { $db->beginTransaction(); $ref=generate_reference('RCH'); $wallet->adjustBalance((int)$user['id'],-$amount); (new Recharge())->create(['user_id'=>$user['id'],'operator_id'=>$operatorId,'phone_number'=>$phone,'amount'=>$amount,'reference_no'=>$ref]); (new Transaction())->create(['reference_no'=>$ref,'user_id'=>$user['id'],'type'=>'recharge','direction'=>'debit','amount'=>$amount,'description'=>'Mobile recharge for '.$phone]); (new Notification())->create((int)$user['id'],'Recharge successful','Your mobile recharge of '.money($amount).' was completed.'); $db->commit(); flash('success','Recharge completed successfully.'); } catch (Throwable $e) { $db->rollBack(); flash('error','Unable to process recharge.'); }
        redirect('index.php?route=dashboard');
    }
    public function transactions(): void
    {
        require_login(); $filters=['type'=>trim($_GET['type'] ?? ''),'status'=>trim($_GET['status'] ?? ''),'date_from'=>trim($_GET['date_from'] ?? ''),'date_to'=>trim($_GET['date_to'] ?? '')];
        $this->view('user/transactions', ['title'=>'Transactions','transactions'=>(new Transaction())->getByUser((int)auth_user()['id'],$filters),'filters'=>$filters], 'dashboard');
    }
    public function transactionDetail(): void
    {
        require_login(); $id=(int)($_GET['id'] ?? 0); $transaction=(new Transaction())->findByIdForUser($id,(int)auth_user()['id']); if (!$transaction) { flash('error','Transaction not found.'); redirect('index.php?route=transactions'); }
        $this->view('user/transaction_detail', ['title'=>'Transaction Detail','transaction'=>$transaction], 'dashboard');
    }
    public function notifications(): void { require_login(); $this->view('user/notifications', ['title'=>'Notifications','notifications'=>(new Notification())->allByUser((int)auth_user()['id'])], 'dashboard'); }
    public function markNotificationRead(): void { require_login(); verify_csrf(); (new Notification())->markRead((int)($_POST['id'] ?? 0),(int)auth_user()['id']); flash('success','Notification updated.'); redirect('index.php?route=notifications'); }
}
