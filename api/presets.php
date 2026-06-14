<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getConnection();

// Handle GET requests (List presets)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $customPresets = [];
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM saved_presets ORDER BY id DESC");
            $customPresets = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Log database error and proceed
        }
    }

    // Default static presets
    $defaultPresets = [
        [
            'id' => 'landscape',
            'preset_name' => 'Vibrant Landscape',
            'preset_data' => json_encode([
                'brightness' => 5, 'contrast' => 15, 'saturation' => 20,
                'sharpness' => 25, 'exposure' => 0, 'highlights' => 0,
                'shadows' => 0, 'temperature' => 5, 'tint' => 0
            ]),
            'is_default' => true
        ],
        [
            'id' => 'portrait',
            'preset_name' => 'Soft Portrait',
            'preset_data' => json_encode([
                'brightness' => 15, 'contrast' => -5, 'saturation' => -5,
                'sharpness' => 5, 'exposure' => 0, 'highlights' => 0,
                'shadows' => 0, 'temperature' => 0, 'tint' => 5
            ]),
            'is_default' => true
        ],
        [
            'id' => 'product',
            'preset_name' => 'Product Studio',
            'preset_data' => json_encode([
                'brightness' => 20, 'contrast' => 10, 'saturation' => 5,
                'sharpness' => 30, 'exposure' => 0, 'highlights' => 5,
                'shadows' => 0, 'temperature' => 0, 'tint' => 0
            ]),
            'is_default' => true
        ],
        [
            'id' => 'social',
            'preset_name' => 'Social Pop',
            'preset_data' => json_encode([
                'brightness' => 10, 'contrast' => 15, 'saturation' => 15,
                'sharpness' => 15, 'exposure' => 5, 'highlights' => 0,
                'shadows' => 0, 'temperature' => 0, 'tint' => 0
            ]),
            'is_default' => true
        ]
    ];

    Response::json([
        'success' => true,
        'defaults' => $defaultPresets,
        'customs' => array_map(function($preset) {
            $preset['is_default'] = false;
            return $preset;
        }, $customPresets)
    ]);
}

// Handle POST requests (Save/Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $name = trim($_POST['preset_name'] ?? '');
        $data = $_POST['preset_data'] ?? ''; // Expecting JSON string of sliders

        if (empty($name) || empty($data)) {
            Response::error('Missing required parameters: preset_name and preset_data.', 400);
        }

        // Verify JSON format
        json_decode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data format for preset_data.', 400);
        }

        if (!$db) {
            Response::error('Database connection is not active. Presets cannot be saved.', 503);
        }

        try {
            $stmt = $db->prepare("INSERT INTO saved_presets (preset_name, preset_data) VALUES (?, ?)");
            $stmt->execute([$name, $data]);
            
            Response::json([
                'success' => true,
                'message' => 'Preset saved successfully.',
                'id' => $db->lastInsertId()
            ]);
        } catch (PDOException $e) {
            Response::error('Failed to save preset to database: ' . $e->getMessage(), 500);
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            Response::error('Missing or invalid parameter: id.', 400);
        }

        if (!$db) {
            Response::error('Database connection is not active.', 503);
        }

        try {
            $stmt = $db->prepare("DELETE FROM saved_presets WHERE id = ?");
            $stmt->execute([$id]);

            Response::json([
                'success' => true,
                'message' => 'Preset deleted successfully.'
            ]);
        } catch (PDOException $e) {
            Response::error('Failed to delete preset: ' . $e->getMessage(), 500);
        }
    } else {
        Response::error('Invalid preset action.', 400);
    }
}
