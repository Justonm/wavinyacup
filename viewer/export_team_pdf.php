<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Authenticate and authorize viewer
if (!is_logged_in()) {
    redirect(app_base_url() . '/auth/login.php');
}

$db = db();
$team_id = $_GET['id'] ?? null;

if (!$team_id) {
    die('Invalid team ID - No ID provided.');
}

// Debug: Check if team exists first
$team_exists = $db->fetchRow("SELECT id, name FROM teams WHERE id = ?", [$team_id]);
if (!$team_exists) {
    die("Invalid team ID - Team {$team_id} not found in database.");
}

// Fetch team details with ward info
$team = $db->fetchRow("SELECT t.*, w.name as ward_name, sc.name as sub_county_name FROM teams t LEFT JOIN wards w ON t.ward_id = w.id LEFT JOIN sub_counties sc ON w.sub_county_id = sc.id WHERE t.id = ?", [$team_id]);

if (!$team) {
    die("Invalid team ID - Could not fetch team details for team {$team_id}.");
}

// Fetch coach details with image
$coach = $db->fetchRow("SELECT u.first_name, u.last_name, u.profile_image, c.coach_image FROM users u JOIN coaches c ON u.id = c.user_id WHERE c.team_id = ?", [$team_id]);

// Fetch players with all registration data including images
$players = $db->fetchAll("
    SELECT 
        p.*, 
        u.first_name, 
        u.last_name, 
        u.id_number AS national_id,
        u.phone,
        u.email,
        u.profile_image,
        p.player_image
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? 
    ORDER BY p.jersey_number ASC
", [$team_id]);

// Get the absolute path for the team photo
$team_photo_path = null;
if (!empty($team['team_photo'])) {
    $potential_path = dirname(__DIR__) . '/uploads/teams/' . $team['team_photo'];
    // IMPORTANT: Dompdf requires file:// or absolute path
    if (file_exists($potential_path)) {
        $team_photo_path = $potential_path;
    }
}

// Start HTML generation
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Team Details: <?php echo htmlspecialchars($team['name']); ?></title>
    <style>
        /* Changed font to support special characters better with Dompdf */
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; } 
        .header { text-align: center; margin-bottom: 20px; }
        .header img { max-width: 150px; max-height: 150px; }
        .header h1 { margin: 0; }
        .team-info { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .team-info p { margin: 0 0 5px; }
        .players-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .players-table th, .players-table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: middle; }
        .players-table th { background-color: #f2f2f2; font-weight: bold; }
        .badge { padding: 2px 6px; border-radius: 3px; color: white; font-size: 10px; }
        .bg-success { background-color: #28a745; }
        .bg-secondary { background-color: #6c757d; }
        .player-photo { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .coach-info { display: flex; align-items: center; margin: 10px 0; }
        .coach-photo { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <?php if ($team_photo_path): ?>
            <img src="file://<?php echo $team_photo_path; ?>" alt="Team Photo">
        <?php endif; ?>
        <h1><?php echo htmlspecialchars($team['name']); ?></h1>
    </div>

    <div class="team-info">
        <h3>Team Information</h3>
        <p><strong>Team Name:</strong> <?php echo htmlspecialchars($team['name']); ?></p>
        <p><strong>Team Code:</strong> <?php echo htmlspecialchars($team['team_code']); ?></p>
        <p><strong>Founded Year:</strong> <?php echo $team['founded_year'] ? htmlspecialchars($team['founded_year']) : 'Not specified'; ?></p>
        <p><strong>Home Ground:</strong> <?php echo $team['home_ground'] ? htmlspecialchars($team['home_ground']) : 'Not specified'; ?></p>
        <p><strong>Team Colors:</strong> <?php echo $team['team_colors'] ? htmlspecialchars($team['team_colors']) : 'Not specified'; ?></p>
        <div class="coach-info">
            <?php 
            $coach_image_path = null;
            if ($coach && !empty($coach['coach_image'])) {
                $potential_coach_path = dirname(__DIR__) . '/uploads/coaches/' . $coach['coach_image'];
                if (file_exists($potential_coach_path)) {
                    $coach_image_path = $potential_coach_path;
                }
            } elseif ($coach && !empty($coach['profile_image'])) {
                $potential_profile_path = dirname(__DIR__) . '/uploads/' . $coach['profile_image'];
                if (file_exists($potential_profile_path)) {
                    $coach_image_path = $potential_profile_path;
                }
            }
            ?>
            <?php if ($coach_image_path): ?>
                <img src="file://<?php echo $coach_image_path; ?>" alt="Coach Photo" class="coach-photo">
            <?php endif; ?>
            <p><strong>Coach:</strong> <?php echo $coach ? htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) : 'Not Assigned'; ?></p>
        </div>
        <p><strong>Ward:</strong> <?php echo htmlspecialchars($team['ward_name']); ?></p>
        <p><strong>Sub-County:</strong> <?php echo htmlspecialchars($team['sub_county_name']); ?></p>
        <p><strong>Status:</strong> <span class="badge bg-<?php echo $team['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($team['status']); ?></span></p>
        <p><strong>Registration Date:</strong> <?php echo date('F j, Y', strtotime($team['created_at'])); ?></p>
    </div>

    <h3>Player List (<?php echo count($players); ?>)</h3>
    <table class="players-table">
        <thead>
            <tr>
                <th>Photo</th>
                <th>#</th>
                <th>Name</th>
                <th>Position</th>
                <th>Date of Birth</th>
                <th>ID Number</th>
                <th>Height (cm)</th>
                <th>Weight (kg)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($players)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;">No players found for this team.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($players as $player): ?>
                    <tr>
                        <td>
                            <?php 
                            $player_image_path = null;
                            if (!empty($player['player_image'])) {
                                $potential_player_path = dirname(__DIR__) . '/uploads/players/' . $player['player_image'];
                                if (file_exists($potential_player_path)) {
                                    $player_image_path = $potential_player_path;
                                }
                            } elseif (!empty($player['profile_image'])) {
                                $potential_profile_path = dirname(__DIR__) . '/uploads/' . $player['profile_image'];
                                if (file_exists($potential_profile_path)) {
                                    $player_image_path = $potential_profile_path;
                                }
                            }
                            ?>
                            <?php if ($player_image_path): ?>
                                <img src="file://<?php echo $player_image_path; ?>" alt="Player Photo" class="player-photo">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; background-color: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px;">No Photo</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($player['jersey_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($player['position'])); ?></td>
                        <td><?php echo $player['date_of_birth'] ? date('M j, Y', strtotime($player['date_of_birth'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($player['national_id']); ?></td>
                        <td><?php echo $player['height_cm'] ? htmlspecialchars($player['height_cm']) : 'N/A'; ?></td>
                        <td><?php echo $player['weight_kg'] ? htmlspecialchars($player['weight_kg']) : 'N/A'; ?></td>
                        <td>
                            <span class="badge bg-<?php echo $player['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $player['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php
$html = ob_get_clean();

// Instantiate and use the dompdf class
$options = new Options();
// Setting isHtml5ParserEnabled to true helps with robust HTML parsing
$options->setIsHtml5ParserEnabled(true); 
$options->set('defaultFont', 'DejaVu Sans'); // Set the default font to one that supports Unicode
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $team['name']) . '_details.pdf';
$dompdf->stream($filename, ['Attachment' => true]);