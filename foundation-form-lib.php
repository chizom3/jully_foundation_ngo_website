<?php
declare(strict_types=1);

const JF_FROM_EMAIL = 'info@jullyfoundation.org';
const JF_SITE_NAME = 'Jully Foundation';

function jf_clean_text(string $value): string
{
    $value = trim($value);
    return preg_replace('/[\r\n]+/', "\n", $value) ?? $value;
}

function jf_clean_header(string $value): string
{
    return trim(preg_replace('/[\r\n]+/', ' ', $value) ?? $value);
}

function jf_label(string $key): string
{
    return ucwords(str_replace(['_', '-'], ' ', $key));
}

function jf_wants_json(): bool
{
    $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $requestedWith = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'fetch';
}

function jf_response(string $title, string $message, int $statusCode = 200): void
{
    http_response_code($statusCode);

    if (jf_wants_json()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'ok' => $statusCode >= 200 && $statusCode < 300,
            'title' => $title,
            'message' => $message,
        ]);
        return;
    }

    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$safeTitle} | Jully Foundation</title>
  <style>
    body{margin:0;font-family:Arial,sans-serif;color:#0b1c30;background:#f8f9ff}
    main{display:grid;min-height:100vh;place-items:center;padding:32px}
    section{width:min(100%,640px);padding:38px;border-radius:12px;background:#fff;box-shadow:0 18px 45px rgba(11,28,48,.12)}
    h1{margin:0 0 14px;font-size:clamp(30px,5vw,44px);color:#79008e}
    p{color:#514251;font-size:18px;line-height:1.6}
    a{display:inline-block;margin-top:16px;padding:12px 18px;border-radius:8px;background:#006e2f;color:#fff;text-decoration:none;font-weight:700}
  </style>
</head>
<body><main><section><h1>{$safeTitle}</h1><p>{$safeMessage}</p><a href="./">Return Home</a></section></main></body>
</html>
HTML;
}

function jf_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        jf_response('Form Not Submitted', 'Please submit the form from the Jully Foundation website.', 405);
        exit;
    }
}

function jf_email_from_post(array $preferredNameKeys = ['name', 'firstName', 'projectName', 'orgName']): string
{
    $email = jf_clean_header((string)($_POST['email'] ?? $_POST['projectEmail'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jf_response('Invalid Email Address', 'Please enter a valid email address.', 400);
        exit;
    }
    return $email;
}

function jf_sender_name(array $keys = ['name', 'firstName', 'projectName', 'orgName']): string
{
    foreach ($keys as $key) {
        $value = jf_clean_header((string)($_POST[$key] ?? ''));
        if ($value !== '') {
            $last = jf_clean_header((string)($_POST['lastName'] ?? ''));
            return trim($value . ' ' . $last);
        }
    }
    return 'Website Visitor';
}

function jf_submission_body(string $formName): string
{
    $lines = ["New {$formName} submission from " . JF_SITE_NAME, ''];
    foreach ($_POST as $key => $value) {
        if (in_array($key, ['website'], true)) {
            continue;
        }
        if (is_array($value)) {
            $value = implode(', ', array_map('strval', $value));
        }
        $value = jf_clean_text((string)$value);
        if ($value !== '') {
            $lines[] = jf_label((string)$key) . ': ' . $value;
        }
    }
    $lines[] = '';
    $lines[] = 'Submitted: ' . date('Y-m-d H:i:s');
    return implode("\n", $lines);
}

function jf_send_form(string $recipient, string $formName, string $successMessage): void
{
    jf_require_post();

    if ((string)($_POST['website'] ?? '') !== '') {
        jf_response('Message Sent', $successMessage);
        exit;
    }

    $replyTo = jf_email_from_post();
    $senderName = jf_sender_name();
    $subject = JF_SITE_NAME . ': ' . $formName . ' - ' . $senderName;
    $body = jf_submission_body($formName);
    $headers = [
        'From: Jully Foundation Website <' . JF_FROM_EMAIL . '>',
        'Reply-To: ' . $replyTo,
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $sent = @mail($recipient, $subject, $body, implode("\r\n", $headers));
    if ($sent) {
        jf_response('Message Sent', $successMessage);
        return;
    }

    jf_response('Message Could Not Be Sent', 'The website could not send your message. Please email ' . $recipient . ' directly.', 500);
}
