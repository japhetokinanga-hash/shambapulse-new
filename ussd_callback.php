<?php
// Africa's Talking USSD callback: POST sessionId, serviceCode, phoneNumber, text
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/ussd.php';
header('Content-Type: text/plain; charset=utf-8');
echo ussd_handle(norm_phone($_POST['phoneNumber'] ?? ''), trim($_POST['text'] ?? ''));
