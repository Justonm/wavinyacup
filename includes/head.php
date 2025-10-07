<?php
// Shared head include for all pages
// Usage:
//   $page_title = 'Page Title';
//   $extra_head = '<style>/* optional per-page styles */</style>';
//   include dirname(__DIR__) . '/includes/head.php';

// Ensure config and constants available
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$title_text = isset($page_title) && $page_title
    ? htmlspecialchars($page_title) . ' - ' . APP_NAME
    : APP_NAME;

?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title_text; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="<?php echo APP_URL; ?>/assets/css/main.css" rel="stylesheet">
<link rel="icon" type="image/png" href="<?php echo APP_URL; ?>/assets/images/logo.png">
<?php if (!empty($extra_head)) { echo $extra_head; } ?>
