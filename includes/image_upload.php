<?php
/**
 * Image Upload Utility Functions
 * Handles image uploads for teams, players, and coaches
 */

// Create upload directories if they don't exist
function create_upload_directories() {
    $directories = [
        'uploads',
        'uploads/teams',
        'uploads/players', 
        'uploads/coaches',
        'uploads/temp'
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Upload and process an image
 * @param array $file $_FILES array element
 * @param string $type 'team', 'player', or 'coach'
 * @param string $subtype 'logo', 'photo', etc. (optional)
 * @param int $id ID of the entity (for unique naming)
 * @return array ['success' => bool, 'path' => string, 'error' => string]
 */
function upload_image($file, $type, $subtype = null, $id = null) {
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
    
    // Set upload path
    $upload_dir = "uploads/{$type}s/";
    $upload_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => false,
            'error' => 'Failed to move uploaded file'
        ];
    }
    
    // Resize image if needed
    $resized = resize_image($upload_path, $type, $subtype);
    if (!$resized['success']) {
        // Delete original file if resize failed
        unlink($upload_path);
        return $resized;
    }
    
    return [
        'success' => true,
        'path' => $upload_path,
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
 * @param string $type 'team', 'player', or 'coach'
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
 * @param string $filepath Path to the image file
 * @return bool Success status
 */
function delete_image($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return true; // File doesn't exist, consider it "deleted"
}

/**
 * Get image URL for display
 * @param string $filepath Database path
 * @return string Full URL or default image
 */
function get_image_url($filepath, $type = 'default') {
    if (empty($filepath) || !file_exists($filepath)) {
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
    
    return $filepath;
}

/**
 * Generate thumbnail for image display
 * @param string $filepath Path to original image
 * @param int $width Thumbnail width
 * @param int $height Thumbnail height
 * @return string Thumbnail path
 */
function generate_thumbnail($filepath, $width = 150, $height = 150) {
    if (!file_exists($filepath)) {
        return get_image_url('', 'default');
    }
    
    $path_info = pathinfo($filepath);
    $thumbnail_path = $path_info['dirname'] . '/thumbnails/' . $path_info['basename'];
    
    // Create thumbnails directory if it doesn't exist
    $thumb_dir = $path_info['dirname'] . '/thumbnails/';
    if (!file_exists($thumb_dir)) {
        mkdir($thumb_dir, 0755, true);
    }
    
    // Generate thumbnail if it doesn't exist
    if (!file_exists($thumbnail_path)) {
        $image_info = getimagesize($filepath);
        $mime_type = $image_info['mime'];
        
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
                imagejpeg($destination, $thumbnail_path, 85);
                break;
            case 'image/png':
                imagepng($destination, $thumbnail_path, 8);
                break;
            case 'image/gif':
                imagegif($destination, $thumbnail_path);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    return $thumbnail_path;
} 