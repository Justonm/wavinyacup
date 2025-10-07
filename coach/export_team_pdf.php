<?php
// coach/export_team_pdf.php - Generate and export team players as a PDF

require_once '../config/config.php';
require_once '../includes/helpers.php';
require_once '../includes/fpdf/fpdf.php'; // Or TCPDF if you prefer

// Check if user is logged in and has coach role
if (!is_logged_in() || !has_role('coach')) {
    redirect('../auth/login.php');
}

$user = get_logged_in_user();
$db = db();

$team_id = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;

if ($team_id <= 0) {
    die("Invalid team ID.");
}

// Verify that the coach manages this team
$coach = $db->fetchRow("
    SELECT c.id, c.user_id, t.name as team_name, t.team_photo
    FROM coaches c
    LEFT JOIN teams t ON c.team_id = t.id
    WHERE c.user_id = ? AND c.team_id = ?
", [$user['id'], $team_id]);

if (!$coach) {
    die("Unauthorized access. You do not manage this team.");
}

// Fetch team players
$players = $db->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.email, u.phone, u.id_number
    FROM players p
    JOIN users u ON p.user_id = u.id
    WHERE p.team_id = ? AND p.is_active = 1
    ORDER BY u.first_name ASC
", [$team_id]);

class PDF extends FPDF {
    private $team_name;
    private $team_photo;

    function setTeamInfo($name, $photo) {
        $this->team_name = $name;
        $this->team_photo = $photo;
    }
    
    // Page header
    function Header() {
        // Logo or team photo
        if ($this->team_photo && file_exists('../' . $this->team_photo)) {
            $this->Image('../' . $this->team_photo, 10, 6, 30);
        }
        
        // Title
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Team Roster: ' . $this->team_name, 0, 0, 'C');
        $this->Ln(20);
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
    
    // Colored table
    function FancyTable($header, $data) {
        // Colors, line width and bold font
        $this->SetFillColor(46, 117, 182);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        
        // Header
        $w = array(10, 45, 25, 30, 20, 30, 25); // Set column widths
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        
        // Color and font restoration
        $this->SetFillColor(224, 235, 255);
        $this->SetTextColor(0);
        $this->SetFont('');
        
        // Data
        $fill = false;
        foreach ($data as $row) {
            $this->Cell($w[0], 6, $row['jersey_number'], 'LR', 0, 'C', $fill);
            $this->Cell($w[1], 6, utf8_decode($row['first_name'] . ' ' . $row['last_name']), 'LR', 0, 'L', $fill);
            $this->Cell($w[2], 6, utf8_decode($row['position']), 'LR', 0, 'L', $fill);
            $this->Cell($w[3], 6, $row['id_number'], 'LR', 0, 'L', $fill);
            
            // Calculate age
            $dob = new DateTime($row['date_of_birth']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            $this->Cell($w[4], 6, $age, 'LR', 0, 'C', $fill);
            
            $this->Cell($w[5], 6, $row['phone'], 'LR', 0, 'L', $fill);
            $this->Cell($w[6], 6, $row['email'], 'LR', 0, 'L', $fill);
            
            $this->Ln();
            $fill = !$fill;
        }
        
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->setTeamInfo($coach['team_name'], $coach['team_photo']);
$pdf->AliasNbPages();
$pdf->AddPage();

// Set up table header
$header = array('Jersey #', 'Name', 'Position', 'ID Number', 'Age', 'Phone', 'Email');
$pdf->FancyTable($header, $players);

$filename = 'team_roster_' . str_replace(' ', '_', $coach['team_name']) . '.pdf';

$pdf->Output('I', $filename);