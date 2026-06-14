<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';
require_once __DIR__ . '/../../core/ProjectService.php';

AuthService::startSession();

$user = AuthService::checkAuth();
if (!$user) {
    Response::error('Unauthorized. Please login.', 401);
}

$userId = (int)$user['id'];
$projectService = new ProjectService();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $list = $projectService->getUserProjects($userId);
        Response::success($list, 'User projects fetched.');
    } elseif ($action === 'load') {
        $projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($projectId <= 0) {
            Response::error('Invalid project ID.', 400);
        }
        $project = $projectService->getProject($userId, $projectId);
        if ($project) {
            Response::success($project, 'Project loaded.');
        } else {
            Response::error('Project not found or access denied.', 404);
        }
    } else {
        Response::error('Invalid action.', 400);
    }
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF
    if (!AuthService::validateCsrfToken($csrfToken)) {
        Response::error('CSRF security check failed.', 403);
    }

    if ($action === 'save') {
        $projectName = $_POST['project_name'] ?? 'Untitled Project';
        $projectDataRaw = $_POST['project_data'] ?? '';
        $projectId = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;

        if (empty($projectDataRaw)) {
            Response::error('Missing project data content.', 400);
        }

        $projectData = json_decode($projectDataRaw, true);
        if ($projectData === null) {
            Response::error('Invalid project data JSON format.', 400);
        }

        $savedId = $projectService->saveProject($userId, $projectName, $projectData, $projectId);
        if ($savedId) {
            Response::success(['id' => $savedId], 'Project saved successfully!');
        } else {
            Response::error('Failed to save project.', 500);
        }
    } elseif ($action === 'delete') {
        $projectId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($projectId <= 0) {
            Response::error('Invalid project ID.', 400);
        }
        if ($projectService->deleteProject($userId, $projectId)) {
            Response::success([], 'Project deleted successfully.');
        } else {
            Response::error('Failed to delete project.', 500);
        }
    } else {
        Response::error('Invalid action.', 400);
    }
} else {
    Response::error('Invalid method.', 405);
}
