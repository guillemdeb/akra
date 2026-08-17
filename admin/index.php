<?php
// admin/index.php — Redirigeix al dashboard
session_start();
header('Location: ' . (isset($_SESSION['akra_admin']) ? 'dashboard.php' : 'login.php'));
exit;
