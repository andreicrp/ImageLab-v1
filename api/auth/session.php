<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

$user = AuthService::checkAuth();
$csrfToken = AuthService::getCsrfToken();

Response::json([
    'success' => true,
    'authenticated' => ($user !== null),
    'user' => $user,
    'csrf_token' => $csrfToken
]);
