<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in() || !has_role('admin')) {
    redirect('../../auth/login.php');
}

$db = db();

$player_id = $_GET['id'] ?? null;
$team_id = $_GET['team_id'] ?? null;
$is_editing = !is_null($player_id);

$player = [
    'first_name' => '',
    'last_name' => '',
    'id_number' => '',
    'position' => '',
    'jersey_number' => '',
    'is_active' => 1,
    'team_id' => $team_id
];

$page_title = 'Add New Player';
$button_text = 'Add Player';

if ($is_editing) {
    $player = $db->fetchRow('SELECT * FROM players WHERE id = ?', [$player_id]);
    if (!$player) {
        $_SESSION['error_message'] = 'Player not found.';
        redirect('../teams.php');
    }
    $team_id = $player['team_id'];
    $page_title = 'Edit Player';
    $button_text = 'Update Player';
} elseif (!$team_id) {
    $_SESSION['error_message'] = 'Team ID is required to add a player.';
    redirect('../teams.php');
}

$team = $db->fetchRow('SELECT name FROM teams WHERE id = ?', [$team_id]);
if (!$team) {
    $_SESSION['error_message'] = 'Team not found.';
    redirect('../teams.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $id_number = sanitize_input($_POST['id_number']);
    $position = sanitize_input($_POST['position']);
    $jersey_number = filter_input(INPUT_POST, 'jersey_number', FILTER_VALIDATE_INT);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($first_name) || empty($last_name) || empty($position) || $jersey_number === false) {
        $_SESSION['error_message'] = 'All fields are required.';
    } else {
        try {
            if ($is_editing) {
                $db->query(
                    'UPDATE players SET first_name = ?, last_name = ?, id_number = ?, position = ?, jersey_number = ?, is_active = ? WHERE id = ?',
                    [$first_name, $last_name, $id_number, $position, $jersey_number, $is_active, $player_id]
                );
                $_SESSION['success_message'] = 'Player updated successfully.';
            } else {
                $db->query(
                    'INSERT INTO players (team_id, first_name, last_name, id_number, position, jersey_number, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$team_id, $first_name, $last_name, $id_number, $position, $jersey_number, $is_active]
                );
                $_SESSION['success_message'] = 'Player added successfully.';
            }
            redirect("../teams/view_team.php?id=$team_id");
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i><?php echo htmlspecialchars($page_title); ?> for <?php echo htmlspecialchars($team['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                        <?php endif; ?>
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($player['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($player['last_name']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" value="<?php echo htmlspecialchars($player['id_number']); ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">Position</label>
                                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($player['position']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jersey_number" class="form-label">Jersey Number</label>
                                    <input type="number" class="form-control" id="jersey_number" name="jersey_number" value="<?php echo htmlspecialchars($player['jersey_number']); ?>" required>
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($player['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Player is Active</label>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="../teams/view_team.php?id=<?php echo $team_id; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i><?php echo $button_text; ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
