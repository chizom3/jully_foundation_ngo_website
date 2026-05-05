<?php
declare(strict_types=1);
require __DIR__ . '/foundation-form-lib.php';

jf_require_post();

if ((string)($_POST['website'] ?? '') !== '') {
    jf_response('Subscription Received', 'Thank you for subscribing to Jully Foundation updates.');
    exit;
}

$email = jf_email_from_post();
$storageFile = __DIR__ . '/newsletter-subscribers.csv';
$isNewFile = !file_exists($storageFile);
$handle = @fopen($storageFile, 'ab');

if ($handle !== false) {
    if ($isNewFile) {
        fputcsv($handle, ['email', 'submitted_at']);
    }
    fputcsv($handle, [$email, date('Y-m-d H:i:s')]);
    fclose($handle);
}

$body = "New newsletter subscription from Jully Foundation website\n\nEmail: {$email}\nSubmitted: " . date('Y-m-d H:i:s');
$headers = [
    'From: Jully Foundation Website <' . JF_FROM_EMAIL . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
];

@mail('info@jullyfoundation.org', JF_SITE_NAME . ': Newsletter Subscription', $body, implode("\r\n", $headers));

jf_response('Subscription Received', 'Thank you for subscribing to Jully Foundation updates.');
