<?php
// api/notes.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           // temporary - local dev only
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

try {
    $db = getDb();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // ────────────────────────────────────────────────
    // GET /api/notes.php?project_id=123   → list notes for project
    // ────────────────────────────────────────────────
    if ($method === 'GET') {
        $project_id = (int)($_GET['project_id'] ?? 0);

        if ($project_id < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid project_id']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT 
                id, note, created_at
            FROM project_notes
            WHERE project_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$project_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'notes'   => $notes
        ]);
        exit;
    }

    // ────────────────────────────────────────────────
    // POST /api/notes.php          → add new note
    // Expects JSON: {"project_id": 123, "note": "User added milestone X..."}
    // ────────────────────────────────────────────────
    if ($method === 'POST') {
        $project_id = (int)($input['project_id'] ?? 0);
        $note       = trim($input['note'] ?? '');

        if ($project_id < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid project_id']);
            exit;
        }

        if (empty($note)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Note text is required']);
            exit;
        }

        // Optional: enforce max length if desired (e.g. 4000 chars)
        if (strlen($note) > 8000) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Note is too long (max 8000 characters)']);
            exit;
        }

        $stmt = $db->prepare("
            INSERT INTO project_notes (project_id, note)
            VALUES (?, ?)
        ");
        $stmt->execute([$project_id, $note]);

        $newId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'id'      => (int)$newId,
            'message' => 'Note added',
            'created_at' => date('Y-m-d H:i:s')   // approximate - can query if exact time needed
        ]);
        exit;
    }

    // Fallback
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage()
    ]);
}