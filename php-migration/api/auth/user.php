<?php
/**
 * API - Informations utilisateur connecté
 */

header('Content-Type: application/json');

if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

try {
    $user = Auth::user();
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
            'phone' => $user['phone'],
            'establishment_id' => $user['establishment_id'],
            'establishment_name' => $user['establishment_name'],
            'establishment_slug' => $user['establishment_slug'],
            'is_active' => $user['is_active'],
            'last_login_at' => $user['last_login_at'],
            'created_at' => $user['created_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    Utils::log("API User info error: " . $e->getMessage(), 'ERROR');
}