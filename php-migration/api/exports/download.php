<?php
/**
 * API Exports - Téléchargement fichiers export
 */

require_once '../../config/config.php';
require_once '../../core/Database.php';
require_once '../../core/Auth.php';
require_once '../../core/Utils.php';
require_once '../../core/services/ExportService.php';

// Authentification requise
if (!Auth::check()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$currentUser = Auth::user();
$establishmentId = $currentUser['establishment_id'];

// Vérifier permissions
if (!Auth::hasRole('admin')) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Permissions insuffisantes']);
    exit;
}

$exportId = $_GET['id'] ?? null;

if (!$exportId) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID export manquant']);
    exit;
}

$exportService = new ExportService();

try {
    $export = $exportService->getExportById($exportId);
    
    if (!$export || $export['establishment_id'] != $establishmentId) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Export non trouvé']);
        exit;
    }
    
    if ($export['status'] !== 'completed') {
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Export non terminé']);
        exit;
    }
    
    $filePath = $export['file_path'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Fichier non trouvé']);
        exit;
    }
    
    // Définir les headers pour le téléchargement
    $filename = basename($filePath);
    $mimeType = mime_content_type($filePath);
    
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Envoyer le fichier
    readfile($filePath);
    
    // Logger le téléchargement
    Utils::log("Export downloaded: {$exportId} by user {$currentUser['id']}", 'INFO');
    
} catch (Exception $e) {
    Utils::log("Export download error: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erreur serveur']);
}
?>