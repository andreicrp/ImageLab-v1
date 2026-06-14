<?php

/**
 * ImageLab API Response Helper Class
 */
class Response {
    /**
     * Send raw JSON response with headers
     * 
     * @param mixed $data
     * @param int $code
     */
    public static function json($data, int $code = 200): void {
        // Clear any previous output buffers if active to avoid corrupted JSON
        if (ob_get_level()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send standard success JSON response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $code
     */
    public static function success($data = [], string $message = '', int $code = 200): void {
        self::json([
            'success' => true,
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Send standard error JSON response
     * 
     * @param string $message
     * @param int $code
     * @param array $errors
     */
    public static function error(string $message = '', int $code = 400, array $errors = []): void {
        self::json([
            'success' => false,
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
