<?php
declare(strict_types=1);

require_once __DIR__ . '/Be/includes/auth.php';

logout_user();
session_start();
set_flash('success', 'You have been logged out.');
header('Location: Main.php');
exit;

