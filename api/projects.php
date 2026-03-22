<?php
// api/projects.php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');           // temporary - for local dev only
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
	// GET /api/projects.php           → list all projects (or single if ?id= is provided)
	// ────────────────────────────────────────────────
	if ($method === 'GET') {
		if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
			// Single project: GET /api/projects.php?id=123
			$id = (int)$_GET['id'];
			$stmt = $db->prepare("
				SELECT id, name, description, status, priority, tags, logo_path,
					   created_at, updated_at
				FROM projects WHERE id = ?
			");
			$stmt->execute([$id]);
			$project = $stmt->fetch();

			if (!$project) {
				http_response_code(404);
				echo json_encode(['success' => false, 'error' => 'Project not found']);
				exit;
			}

			$project['tags'] = $project['tags'] ? explode(',', $project['tags']) : [];
			$project['tags'] = array_map('trim', $project['tags']);

			echo json_encode(['success' => true, 'project' => $project]);
			exit;
		}

		// No ?id → return full list
		$stmt = $db->query("
			SELECT 
				id, name, description, status, priority, tags, logo_path,
				created_at, updated_at
			FROM projects
			ORDER BY 
				FIELD(status, 'backlog', 'todo', 'in_progress', 'review', 'done'),
				priority DESC, name
		");
		$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Convert tags string to array for frontend convenience
		foreach ($projects as &$p) {
			$p['tags'] = $p['tags'] ? explode(',', $p['tags']) : [];
			$p['tags'] = array_map('trim', $p['tags']);
		}
		unset($p);

		echo json_encode(['success' => true, 'projects' => $projects]);
		exit;
	}

    // ────────────────────────────────────────────────
    // POST /api/projects.php          → create new project
    // ────────────────────────────────────────────────
    if ($method === 'POST') {
        $name        = trim($input['name']        ?? '');
        $description = trim($input['description'] ?? '');
        $status      = $input['status']      ?? 'backlog';
        $priority    = $input['priority']    ?? 'medium';
        $tags        = trim($input['tags']    ?? '');

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name is required']);
            exit;
        }

        // Validate enum values
        $valid_status   = ['backlog','todo','in_progress','review','done'];
        $valid_priority = ['low','medium','high','urgent'];
        if (!in_array($status,   $valid_status))   $status   = 'backlog';
        if (!in_array($priority, $valid_priority)) $priority = 'medium';

        $stmt = $db->prepare("
            INSERT INTO projects (name, description, status, priority, tags)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $status, $priority, $tags]);

        $newId = $db->lastInsertId();

        echo json_encode([
            'success' => true,
            'id'      => (int)$newId,
            'message' => 'Project created'
        ]);
        exit;
    }

    // ────────────────────────────────────────────────
    // PUT /api/projects.php?id=123   → update project
    // ────────────────────────────────────────────────
    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
            exit;
        }

        // Only allow updating these fields for now
        $allowed = ['name','description','status','priority','tags','logo_path'];
        $updates = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $input)) {
                $updates[] = "$field = ?";
                $params[]  = $input[$field];

                // Quick enum validation on status & priority
                if ($field === 'status') {
                    $valid = ['backlog','todo','in_progress','review','done'];
                    if (!in_array($input[$field], $valid)) $input[$field] = 'backlog';
                }
                if ($field === 'priority') {
                    $valid = ['low','medium','high','urgent'];
                    if (!in_array($input[$field], $valid)) $input[$field] = 'medium';
                }
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            exit;
        }

        $params[] = $id;
        $sql = "UPDATE projects SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Project updated']);
        exit;
    }

    // ────────────────────────────────────────────────
    // DELETE /api/projects.php?id=123   → delete project
    // ────────────────────────────────────────────────
	if ($method === 'DELETE') {
		$id = (int)($_GET['id'] ?? 0);
		if ($id < 1) {
			http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Missing or invalid id']);
			exit;
		}

		$stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
		$stmt->execute([$id]);

		echo json_encode(['success' => true, 'message' => 'Project deleted']);
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