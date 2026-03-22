<?php
// api/documents.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           // temporary - local dev only
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config.php';

try {
    $db = getDb();
    $method = $_SERVER['REQUEST_METHOD'];

    // ────────────────────────────────────────────────
    // GET /api/documents.php?project_id=123   → list documents
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
                id, original_name, stored_path, mime_type, file_size, uploaded_at
            FROM project_documents
            WHERE project_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$project_id]);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add full download URL
        foreach ($docs as &$doc) {
            $doc['url'] = UPLOAD_URL . basename($doc['stored_path']);
        }
        unset($doc);

        echo json_encode([
            'success' => true,
            'documents' => $docs
        ]);
        exit;
    }

    // ────────────────────────────────────────────────
    // POST /api/documents.php   → upload file(s)
    // Expects multipart/form-data with:
    //   - project_id (form field)
    //   - files[]   (one or more files)
    // ────────────────────────────────────────────────
    if ($method === 'POST') {
        $project_id = (int)($_POST['project_id'] ?? 0);

        if ($project_id < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid project_id']);
            exit;
        }

        // Verify project exists
        $check = $db->prepare("SELECT 1 FROM projects WHERE id = ?");
        $check->execute([$project_id]);
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Project not found']);
            exit;
        }

        if (empty($_FILES['files']['name'][0])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No files uploaded']);
            exit;
        }

        $uploaded = [];
        $errors   = [];

        $count = count($_FILES['files']['name']);
        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name'     => $_FILES['files']['name'][$i],
                'type'     => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error'    => $_FILES['files']['error'][$i],
                'size'     => $_FILES['files']['size'][$i],
            ];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File '{$file['name']}' upload error: " . $file['error'];
                continue;
            }

            // Basic sanitization
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safe_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name'], '.' . $ext));
            $safe_name = substr($safe_name, 0, 100); // limit length

            $new_filename = 'doc_' . $project_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $stored_path  = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $stored_path)) {
                $stmt = $db->prepare("
                    INSERT INTO project_documents 
                    (project_id, original_name, stored_path, mime_type, file_size)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $project_id,
                    $file['name'],
                    $new_filename,          // only filename, not full path
                    $file['type'],
                    $file['size']
                ]);

                $uploaded[] = [
                    'id'            => $db->lastInsertId(),
                    'original_name' => $file['name'],
                    'url'           => UPLOAD_URL . $new_filename
                ];
            } else {
                $errors[] = "Failed to save file '{$file['name']}'";
            }
        }

        echo json_encode([
            'success'   => empty($errors),
            'uploaded'  => $uploaded,
            'errors'    => $errors
        ]);
        exit;
    }

    // ────────────────────────────────────────────────
    // DELETE /api/documents.php?id=456   → delete document + file
    // ────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $doc_id = (int)($_GET['id'] ?? 0);

        if ($doc_id < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
            exit;
        }

        $stmt = $db->prepare("
            SELECT stored_path FROM project_documents WHERE id = ?
        ");
        $stmt->execute([$doc_id]);
        $doc = $stmt->fetch();

        if (!$doc) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Document not found']);
            exit;
        }

        // Delete physical file
        $full_path = UPLOAD_DIR . $doc['stored_path'];
        if (file_exists($full_path)) {
            @unlink($full_path);
        }

        // Delete DB record
        $stmt = $db->prepare("DELETE FROM project_documents WHERE id = ?");
        $stmt->execute([$doc_id]);

        echo json_encode(['success' => true, 'message' => 'Document deleted']);
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
