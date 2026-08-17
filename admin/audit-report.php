<?php
require_once 'includes/core.php';
requireLogin();

$audit = getAudit($_GET['id'] ?? '');
if (!$audit) { header('Location: audits.php'); exit; }
$client = getClient($audit['client_id']);
if (!$client) { header('Location: audits.php'); exit; }

$proposals = array_values(array_filter(getProposals(), fn($p) => $p['audit_id'] === $audit['id']));
$view = 'admin';

include 'includes/audit-report-render.php';
