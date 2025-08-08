<?php
header('Content-Type: application/manifest+json');
require_once '../../core/Database.php';
require_once '../../core/services/ProgressiveWebAppService.php';

$database = new Database();
$establishmentId = $_GET['establishment_id'] ?? null;

$pwaService = new ProgressiveWebAppService($database, $establishmentId);
$manifest = $pwaService->generateManifest();

echo json_encode($manifest, JSON_PRETTY_PRINT);