<?php
class AuthController extends BaseController
{
    public function register(): void
    {
        require_guest();
        $this->view('auth/register', ['title' => 'Register']);
    }
    public function doRegister(): void
    {
        require_guest(); verify_csrf();
        $userModel = new User(); $notificationModel = new Notification(); $audit = new AuditLog();
        $name=trim($_POST['name'] ?? ''); $username=trim($_POST['username'] ?? ''); $email=trim($_POST['email'] ?? ''); $phone=trim($_POST['phone'] ?? '');
        $password=(string)($_POST['password'] ?? ''); $confirm=(string)($_POST['confirm_password'] ?? '');
        if ($name==='' || $username==='' || $email==='' || $phone==='' || $password==='') { flash('error','All fields are required.'); redirect('index.php?route=register'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error','Invalid email address.'); redirect('index.php?route=register'); }
        if (strlen($password) < 8) { flash('error','Password must be at least 8 characters.'); redirect('index.php?route=register'); }
        if ($password !== $confirm) { flash('error','Passwords do not match.'); redirect('index.php?route=register'); }
        if ($userModel->findByEmail($email)) { flash('error','Email already exists.'); redirect('index.php?route=register'); }
        if ($userModel->findByUsername($username)) { flash('error','Username already exists.'); redirect('index.php?route=register'); }
        $avatar = null;
        if (!empty($_FILES['avatar']['name'])) {
            $config = app_config(); if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'],0775,true);
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','webp'],true)) {
                $fileName = uniqid('avatar_',true).'.'.$ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $config['upload_dir'].'/'.$fileName)) $avatar=$fileName;
            }
        }
        $userId = $userModel->create(['name'=>$name,'username'=>$username,'email'=>$email,'phone'=>$phone,'password_hash'=>password_hash($password,PASSWORD_DEFAULT),'avatar'=>$avatar]);
        $newUser = $userModel->findById($userId);
        $notificationModel->create($userId,'Welcome to EZ Wallet','Your account has been created successfully.');
        $audit->log($userId,'register','User account created');
        login_user($newUser); flash('success','Registration completed successfully.'); redirect('index.php?route=dashboard');
    }
    public function login(): void
    {
        require_guest(); $this->view('auth/login', ['title' => 'Login']);
    }
    public function doLogin(): void
    {
        require_guest(); verify_csrf();
        $email=trim($_POST['email'] ?? ''); $password=(string)($_POST['password'] ?? '');
        $user=(new User())->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) { flash('error','Invalid email or password.'); redirect('index.php?route=login'); }
        if ($user['status'] !== 'active') { flash('error','Your account is inactive. Please contact admin.'); redirect('index.php?route=login'); }
        login_user($user); (new AuditLog())->log((int)$user['id'],'login','User logged in'); flash('success','Welcome back, '.$user['name'].'!');
        redirect('index.php?route=' . ($user['role']==='admin' ? 'admin/dashboard' : 'dashboard'));
    }
    public function logout(): void
    {
        $user=auth_user(); if ($user) (new AuditLog())->log((int)$user['id'],'logout','User logged out');
        logout_user(); session_start(); flash('success','You have been logged out.'); redirect('index.php?route=login');
    }
    public function forgotPassword(): void
    {
        require_guest(); $this->view('auth/forgot_password', ['title' => 'Forgot Password']);
    }
    public function sendResetLink(): void
    {
        require_guest(); verify_csrf(); $email=trim($_POST['email'] ?? '');
        if (!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) { flash('error','Please enter a valid email.'); redirect('index.php?route=forgot-password'); }
        $user=(new User())->findByEmail($email); if (!$user) { flash('error','No account found with that email.'); redirect('index.php?route=forgot-password'); }
        $token=(new PasswordReset())->createToken($email); flash('success','Reset token created. For local demo, use this token on reset page: '.$token); redirect('index.php?route=reset-password&token='.urlencode($token));
    }
    public function resetPassword(): void
    {
        require_guest(); $token=trim($_GET['token'] ?? ''); $reset=$token ? (new PasswordReset())->findValidToken($token) : null;
        $this->view('auth/reset_password', ['title' => 'Reset Password', 'token'=>$token, 'reset'=>$reset]);
    }
    public function doResetPassword(): void
    {
        require_guest(); verify_csrf();
        $token=trim($_POST['token'] ?? ''); $password=(string)($_POST['password'] ?? ''); $confirm=(string)($_POST['confirm_password'] ?? '');
        $resetModel=new PasswordReset(); $reset=$resetModel->findValidToken($token);
        if (!$reset) { flash('error','Invalid or expired reset token.'); redirect('index.php?route=forgot-password'); }
        if (strlen($password)<8 || $password!==$confirm) { flash('error','Password must be at least 8 characters and both passwords must match.'); redirect('index.php?route=reset-password&token='.urlencode($token)); }
        $user=(new User())->findByEmail($reset['email']); if (!$user) { flash('error','User account not found.'); redirect('index.php?route=forgot-password'); }
        (new User())->updatePassword((int)$user['id'], password_hash($password,PASSWORD_DEFAULT)); $resetModel->markUsed((int)$reset['id']);
        (new Notification())->create((int)$user['id'],'Password changed','Your password was updated successfully.'); flash('success','Password reset completed. Please log in.'); redirect('index.php?route=login');
    }
}
