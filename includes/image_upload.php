<?php
/**
 * Image Upload Utility Functions
 * Handles image uploads for teams, players, and coaches
 */

// Include the config file to access ROOT_PATH
require_once __DIR__ . '/../config/config.php';

// Create upload directories if they don't exist
function create_upload_directories() {
    $directories = [
        ROOT_PATH . '/uploads',
        ROOT_PATH . '/uploads/teams',
        ROOT_PATH . '/uploads/players', 
        ROOT_PATH . '/uploads/coaches',
        ROOT_PATH . '/uploads/id', // Added for ID photos
        ROOT_PATH . '/uploads/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            // Added error handling for mkdir
            if (!mkdir($dir, 0755, true)) {
                // You might want to log this error or handle it more gracefully
                error_log("Failed to create directory: $dir");
            }
        }
    }
}

/**
 * Upload and process an image
 * @param array $file $_FILES array element
 * @param string $type 'team', 'player', 'coach', or 'id'
 * @param string $subtype 'logo', 'photo', 'front', 'back' etc. (optional)
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_image($file, $type, $subtype = null) {
    // Create directories
    create_upload_directories();
    
    // Validate file
    $validation = validate_image_file($file);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $filename = $subtype ? "{$type}_{$subtype}_{$timestamp}_{$random}.{$extension}" : "{$type}_{$timestamp}_{$random}.{$extension}";
    
    // Set upload path using ROOT_PATH for an absolute path
    $upload_dir = ROOT_PATH . "/uploads/{$type}/";
    $upload_path = $upload_dir . $filename;
    
    // Ensure the specific type directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => false,
            'error' => 'Failed to move uploaded file. Check directory permissions.'
        ];
    }
    
    // Resize image if needed
    $resized = resize_image($upload_path, $type, $subtype);
    if (!$resized['success']) {
        // Delete original file if resize failed
        unlink($upload_path);
        return $resized;
    }
    
    // Store the path relative to the web root for the database
    $db_path = "uploads/{$type}/{$filename}";
    
    return [
        'success' => true,
        'path' => $db_path,
        'filename' => $filename
    ];
}

/**
 * Validate uploaded image file
 * @param array $file $_FILES array element
 * @return array ['success' => bool, 'error' => string]
 */
function validate_image_file($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error' => 'File upload failed: ' . $file['error']
        ];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return [
            'success' => false,
            'error' => 'File size too large. Maximum size is 5MB.'
        ];
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return [
            'success' => false,
            'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'
        ];
    }
    
    return ['success' => true];
}

/**
 * Resize image to appropriate dimensions
 * @param string $filepath Path to the image file
 * @param string $type 'team', 'player', 'coach', or 'id'
 * @param string $subtype 'logo', 'photo', etc. (optional)
 * @return array ['success' => bool, 'error' => string]
 */
function resize_image($filepath, $type, $subtype = null) {
    // Get image info
    $image_info = getimagesize($filepath);
    if (!$image_info) {
        return [
            'success' => false,
            'error' => 'Invalid image file'
        ];
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $mime_type = $image_info['mime'];
    
    // Set target dimensions based on type and subtype
    switch ($type) {
        case 'team':
            if ($subtype === 'logo') {
                $target_width = 300;
                $target_height = 300;
            } else {
                $target_width = 600;
                $target_height = 400;
            }
            break;
        case 'player':
        case 'coach':
            $target_width = 400;
            $target_height = 500;
            break;
        case 'id': // Added for ID photos
            $target_width = 800;
            $target_height = 800;
            break;
        default:
            $target_width = 400;
            $target_height = 400;
    }
    
    // Calculate new dimensions (maintain aspect ratio)
    $ratio = min($target_width / $width, $target_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Create image resource
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($filepath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filepath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return [
                'success' => false,
                'error' => 'Unsupported image format'
            ];
    }
    
    if (!$source) {
        return [
            'success' => false,
            'error' => 'Failed to create image resource'
        ];
    }
    
    // Create new image
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefill($destination, 0, 0, $transparent);
    }
    
    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save resized image
    $success = false;
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $success = imagejpeg($destination, $filepath, 85);
            break;
        case 'image/png':
            $success = imagepng($destination, $filepath, 8);
            break;
        case 'image/gif':
            $success = imagegif($destination, $filepath);
            break;
    }
    
    // Clean up
    imagedestroy($source);
    imagedestroy($destination);
    
    if (!$success) {
        return [
            'success' => false,
            'error' => 'Failed to save resized image'
        ];
    }
    
    return ['success' => true];
}

/**
 * Delete an image file
 * @param string $filepath Database path (e.g., 'teams/uploads/player/image.jpg')
 * @return bool Success status
 */
function delete_image($filepath) {
    // Convert relative path to absolute path
    $absolute_path = ROOT_PATH . '/' . $filepath;
    
    if (file_exists($absolute_path) && is_file($absolute_path)) {
        return unlink($absolute_path);
    }
    return true; // File doesn't exist, consider it "deleted"
}

/**
 * Get image URL for display
 * @param string $filepath Database path (e.g., 'teams/uploads/player/image.jpg')
 * @param string $type 'team', 'player', 'coach', or 'default'
 * @return string Full URL or default image
 */
function get_image_url($filepath, $type = 'default') {
    // Check if the file exists on the server using the absolute path
    $absolute_path = ROOT_PATH . '/' . $filepath;

    if (empty($filepath) || !file_exists($absolute_path)) {
        // Return default image based on type
        switch ($type) {
            case 'team':
                return 'assets/images/default-team.png';
            case 'player':
                return 'assets/images/default-player.png';
            case 'coach':
                return 'assets/images/default-coach.png';
            default:
                return 'assets/images/default-avatar.png';
        }
    }
    
    // Return the web-accessible URL
    return $filepath;
}

/**
 * Generate thumbnail for image display
 * @param string $filepath Database path
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @return string Thumbnail path
 */
function generate_thumbnail($filepath, $width = 150, $height = 150) {
    $absolute_filepath = ROOT_PATH . '/' . $filepath;

    if (!file_exists($absolute_filepath)) {
        return get_image_url('', 'default');
    }
    
    $path_info = pathinfo($absolute_filepath);
    $thumbnail_path_abs = $path_info['dirname'] . '/thumbnails/' . $path_info['basename'];
    
    // Create thumbnails directory if it doesn't exist
    $thumb_dir_abs = $path_info['dirname'] . '/thumbnails/';
    if (!file_exists($thumb_dir_abs)) {
        mkdir($thumb_dir_abs, 0755, true);
    }
    
    // Generate thumbnail if it doesn't exist
    if (!file_exists($thumbnail_path_abs)) {
        $image_info = getimagesize($absolute_filepath);
        $mime_type = $image_info['mime'];
        
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($absolute_filepath);
                break;
            case 'image/png':
                $source = imagecreatefrompng($absolute_filepath);
                break;
            case 'image/gif':
                $source = imagecreatefromgif($absolute_filepath);
                break;
            default:
                return get_image_url('', 'default');
        }
        
        $destination = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefill($destination, 0, 0, $transparent);
        }
        
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $width, $height, $image_info[0], $image_info[1]);
        
        switch ($mime_type) {
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($destination, $thumbnail_path_abs, 85);
                break;
            case 'image/png':
                imagepng($destination, $thumbnail_path_abs, 8);
                break;
            case 'image/gif':
                imagegif($destination, $thumbnail_path_abs);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    // Return the web-accessible URL
    $relative_path = str_replace(ROOT_PATH . '/', '', $thumbnail_path_abs);
    return $relative_path;
}

/**
 * Simple upload without resizing (fallback for restricted environments)
 * @param array $file $_FILES array element
 * @param string $type 'team', 'player', 'coach', or 'id'
 * @param string $subtype optional subtype to include in filename
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_image_simple($file, $type, $subtype = null) {
    // Basic validation (reuse validate_image_file for size/type and upload error)
    $validation = validate_image_file($file);
    if (!$validation['success']) {
        return $validation;
    }

    // Ensure directories exist
    create_upload_directories();

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $timestamp = time();
    $random = bin2hex(random_bytes(8));
    $filename = $subtype ? "{$type}_{$subtype}_{$timestamp}_{$random}.{$extension}" : "{$type}_{$timestamp}_{$random}.{$extension}";

    $upload_dir = ROOT_PATH . "/uploads/{$type}/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $upload_path = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => false,
            'error' => 'Failed to move uploaded file (simple). Check directory permissions.'
        ];
    }

    return [
        'success' => true,
        'path' => "uploads/{$type}/{$filename}",
        'filename' => $filename
    ];
}