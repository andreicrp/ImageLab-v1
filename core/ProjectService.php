<?php

require_once __DIR__ . '/Database.php';

/**
 * ProjectService Class
 * Handles CRUD operations for saving user design workspaces, editors, and canvas JSON logs.
 */
class ProjectService {
    /**
     * Save/Create a user project
     * 
     * @param int $userId Active user session
     * @param string $projectName Title of the project
     * @param array $projectData Array containing canvas, layers, edits, history, original files
     * @param int|null $projectId Project ID if updating, null if new
     * @return int|bool Saved project ID on success, false on failure
     */
    public function saveProject(int $userId, string $projectName, array $projectData, ?int $projectId = null): int|bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $serializedData = json_encode($projectData);

        if ($projectId !== null) {
            // Update existing project
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET project_name = ?, project_data = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?
            ");
            if ($stmt->execute([$projectName, $serializedData, $projectId, $userId])) {
                return $projectId;
            }
        } else {
            // Insert new project
            $stmt = $pdo->prepare("
                INSERT INTO projects (user_id, project_name, project_data) 
                VALUES (?, ?, ?)
            ");
            if ($stmt->execute([$userId, $projectName, $serializedData])) {
                return (int)$pdo->lastInsertId();
            }
        }
        return false;
    }

    /**
     * Load project data by ID
     */
    public function getProject(int $userId, int $projectId): ?array {
        $pdo = Database::getConnection();
        if ($pdo === null) return null;

        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
        $stmt->execute([$projectId, $userId]);
        $project = $stmt->fetch();
        if ($project) {
            $project['project_data'] = json_decode($project['project_data'], true);
            return $project;
        }
        return null;
    }

    /**
     * Get list of projects belonging to a user (no large JSON data included)
     */
    public function getUserProjects(int $userId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->prepare("
            SELECT id, project_name, created_at, updated_at 
            FROM projects 
            WHERE user_id = ? 
            ORDER BY updated_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete a project
     */
    public function deleteProject(int $userId, int $projectId): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
        return $stmt->execute([$projectId, $userId]);
    }
}
