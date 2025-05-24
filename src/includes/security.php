<?php

/**
 * Security helper functions for Muni Vihar Registration System
 */

/**
 * Generate CSRF token
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('CSRF token validation failed');
    }
    return true;
}

/**
 * Rate limiting function
 */
function check_rate_limit($key, $max_requests = 5, $time_window = 300)
{
    $current_time = time();

    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'count' => 0,
            'first_request' => $current_time
        ];
    }

    if ($current_time - $_SESSION['rate_limits'][$key]['first_request'] > $time_window) {
        $_SESSION['rate_limits'][$key] = [
            'count' => 1,
            'first_request' => $current_time
        ];
        return true;
    }

    if ($_SESSION['rate_limits'][$key]['count'] >= $max_requests) {
        return false;
    }

    $_SESSION['rate_limits'][$key]['count']++;
    return true;
}

/**
 * Sanitize and validate file upload
 */
function validate_file_upload($file, $allowed_types = ['image/jpeg', 'image/png'], $max_size = 5242880)
{
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new RuntimeException('Invalid parameters.');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent.');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit.');
        default:
            throw new RuntimeException('Unknown errors.');
    }

    if ($file['size'] > $max_size) {
        throw new RuntimeException('Exceeded filesize limit.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);

    if (!in_array($mime_type, $allowed_types)) {
        throw new RuntimeException('Invalid file format.');
    }

    // Generate safe filename
    $extension = array_search($mime_type, [
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
    ], true);

    return [
        'extension' => $extension,
        'mime_type' => $mime_type
    ];
}

/**
 * Secure file upload function
 */
function secure_file_upload($file, $upload_dir, $allowed_types = ['image/jpeg', 'image/png'], $max_size = 5242880)
{
    try {
        $file_info = validate_file_upload($file, $allowed_types, $max_size);

        // Create a unique filename
        $filename = sprintf(
            '%s.%s',
            sha1_file($file['tmp_name']),
            $file_info['extension']
        );

        $filepath = $upload_dir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $filename;
    } catch (RuntimeException $e) {
        return false;
    }
}
