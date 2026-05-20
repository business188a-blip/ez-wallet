<?php
class PublicController extends BaseController
{
    public function home(): void
    {
        $this->view('public/home', ['title' => 'Home']);
    }

    public function about(): void
    {
        $this->view('public/about', ['title' => 'About']);
    }

    public function contact(): void
    {
        $this->view('public/contact', ['title' => 'Contact']);
    }

    public function submitContact(): void
    {
        verify_csrf();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name === '' || $email === '' || $subject === '' || $message === '') {
            flash('error', 'All contact form fields are required.');
            redirect('index.php?route=contact');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid email address.');
            redirect('index.php?route=contact');
        }

        (new ContactMessage())->create(compact('name', 'email', 'subject', 'message'));
        flash('success', 'Your message has been submitted successfully.');
        redirect('index.php?route=contact');
    }
}
