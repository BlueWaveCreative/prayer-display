<?php
header('Cache-Control: no-store');
header('Content-Type: text/plain');

$to = 'kenny@bluewavecreativedesign.com, josh@bluewavecreativedesign.com';
$subject = 'Prayer Display — Test Alert Email';
$body = "This is a test email from the Prayer Display health check system.\n\n";
$body .= "If you received this, email alerts are working correctly.\n\n";
$body .= "Sent: " . date('Y-m-d H:i:s T') . "\n";

$headers = "From: Prayer Display <no-reply@bluewavecreativedesign.com>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo "SUCCESS: Email sent to {$to}\n";
    echo "Check your inbox (and spam folder).\n";
} else {
    echo "FAILED: mail() returned false.\n";
    echo "PHP mail may not be configured on this server.\n";
}
