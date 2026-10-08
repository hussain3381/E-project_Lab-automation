<?php
// Validate public contact requests and store them for an administrator to review.
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/security.php';

function contact_process_submission(mysqli $connection): array
{
    $state = ['success' => '', 'error' => ''];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return $state;
    }
    if (!csrf_is_valid(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $state['error'] = 'Your form expired. Refresh the page and try again.';
        return $state;
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || strlen($name) > 120) {
        $state['error'] = 'Enter your name (maximum 120 characters).';
        return $state;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $state['error'] = 'Enter a valid email address.';
        return $state;
    }
    if ($subject === '' || strlen($subject) > 160) {
        $state['error'] = 'Enter a subject (maximum 160 characters).';
        return $state;
    }
    if ($message === '' || strlen($message) > 5000) {
        $state['error'] = 'Enter a message (maximum 5,000 characters).';
        return $state;
    }

    $statement = $connection->prepare(
        "INSERT INTO contact_messages (name, email, subject, message, status) VALUES (?, ?, ?, ?, 'New')"
    );
    $statement->bind_param('ssss', $name, $email, $subject, $message);
    $statement->execute();
    $statement->close();
    $state['success'] = 'Message saved. An administrator can review it from the database.';

    return $state;
}
