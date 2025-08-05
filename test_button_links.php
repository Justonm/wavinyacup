<?php
require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo "<h2>Not Logged In</h2>";
    echo "<p>You need to login first to test the buttons.</p>";
    echo "<a href='auth/login.php'>Login Here</a>";
    exit;
}

$user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Button Link Test - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .btn {
            cursor: pointer;
            pointer-events: auto;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1>Button Link Test</h1>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>User Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
                        <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
                        <p><strong>Role:</strong> <?php echo $user['role']; ?></p>
                        <p><strong>Permissions:</strong></p>
                        <ul>
                            <li>manage_teams: <?php echo has_permission('manage_teams') ? 'YES' : 'NO'; ?></li>
                            <li>manage_players: <?php echo has_permission('manage_players') ? 'YES' : 'NO'; ?></li>
                            <li>manage_coaches: <?php echo has_permission('manage_coaches') ? 'YES' : 'NO'; ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Team Registration</h5>
                    </div>
                    <div class="card-body">
                        <a href="teams/register.php" class="btn btn-primary w-100 mb-2" onclick="console.log('Test Team Registration clicked')">
                            <i class="fas fa-plus me-2"></i>Register Team
                        </a>
                        <a href="admin/teams.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-users me-2"></i>Teams Management
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Player Registration</h5>
                    </div>
                    <div class="card-body">
                        <a href="players/register.php" class="btn btn-success w-100 mb-2" onclick="console.log('Test Player Registration clicked')">
                            <i class="fas fa-plus me-2"></i>Register Player
                        </a>
                        <a href="admin/players.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-user me-2"></i>Players Management
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Coach Registration</h5>
                    </div>
                    <div class="card-body">
                        <a href="coaches/register.php" class="btn btn-warning w-100 mb-2" onclick="console.log('Test Coach Registration clicked')">
                            <i class="fas fa-plus me-2"></i>Register Coach
                        </a>
                        <a href="admin/coaches.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Coaches Management
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                        <p><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
                        <p><strong>Base URL:</strong> <?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug script to ensure links are working
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Test page loaded');
            
            // Add click event listeners to all buttons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    console.log('Button clicked:', this.textContent.trim());
                    console.log('Button href:', this.href);
                    
                    // Test if the link is accessible
                    if (this.href) {
                        console.log('Testing link accessibility...');
                        fetch(this.href, { method: 'HEAD' })
                            .then(response => {
                                console.log('Link status:', response.status);
                                if (response.status === 200) {
                                    console.log('Link is accessible');
                                } else {
                                    console.log('Link returned status:', response.status);
                                }
                            })
                            .catch(error => {
                                console.log('Link error:', error);
                            });
                    }
                });
            });
        });
    </script>
</body>
</html> 