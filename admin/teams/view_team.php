<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/includes/helpers.php';

// Check if user is logged in and has permission
if (!has_permission('manage_players')) {
    redirect('../../auth/login.php');
}

$db = db();
$team_id = $_GET['id'] ?? null;
$success = $_GET['success'] ?? '';

if (!is_numeric($team_id) || $team_id <= 0) {
    redirect('teams.php');
}

$team = $db->fetchRow("SELECT * FROM teams WHERE id = ?", [$team_id]);
if (!$team) {
    redirect('teams.php');
}

$players = $db->fetchAll("SELECT * FROM players WHERE team_id = ? AND is_active = 1", [$team_id]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['name']); ?> - Team Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Team: <?php echo htmlspecialchars($team['name']); ?></h2>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <a href="../../players/register_players.php?team_id=<?php echo $team_id; ?>" class="btn btn-primary mb-3">Register Another Player</a>

    <h3>Registered Players (<?php echo count($players); ?>/22)</h3>
    <?php if (empty($players)): ?>
        <div class="alert alert-info">No players have been registered for this team yet.</div>
    <?php else: ?>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Position</th>
                <th>Jersey Number</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($players as $index => $player): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($player['position']); ?></td>
                    <td><?php echo htmlspecialchars($player['jersey_number']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="teams.php" class="btn btn-secondary mt-3">Back to Teams List</a>
</div>
</body>
</html>