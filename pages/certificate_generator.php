<?php
// pages/certificate_generator.php - FINAL PUBLIC CERTIFICATE GENERATOR
require_once __DIR__ . '/../includes/config.php';

// Ensure FPDF library is available (adjust path if needed)
require_once ROOT_PATH . 'vendor/autoload.php';
use Fpdf\Fpdf;

// --- 1. SECURITY AND DATA CHECK ---
if (!isset($_SESSION['user_id'])) {
    // Redirect if not logged in (though Achievement link should prevent this)
    header("Location: " . BASE_URL . "pages/auth/login.php");
    exit;
}

$certificate_code = $_GET['code'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($certificate_code)) {
    die("Error: Missing certificate validation code.");
}

// --- 2. DATA FETCH (Using Code and User ID for security) ---
$cert = $pdo->prepare("
    SELECT 
        ce.*, 
        c.title, 
        u.first_name, 
        u.last_name, 
        i.first_name AS instructor_first_name, 
        i.last_name AS instructor_last_name
    FROM certificates ce
    JOIN courses c ON c.id = ce.course_id 
    JOIN users u ON u.id = ce.user_id 
    JOIN users i ON c.instructor_id = i.id -- Get instructor details
    WHERE ce.certificate_code = ? AND ce.user_id = ?
");
$cert->execute([$certificate_code, $user_id]);
$cert = $cert->fetch();

if (!$cert) {
    die("Certificate not found, not yet issued, or access denied.");
}


// --- 3. FPDF GENERATION (Enhanced Design) ---

$pdf = new Fpdf('L', 'mm', 'A4'); // Landscape A4 format
$pdf->AddPage();
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();

// Define EduLux Primary Color (e.g., #4f46e5 converted to RGB)
$eduLuxR = 79;
$eduLuxG = 70;
$eduLuxB = 229;
$darkGray = 50;

// --- A. Border (Premium Border) ---
$pdf->SetLineWidth(3);
$pdf->SetDrawColor($eduLuxR, $eduLuxG, $eduLuxB);
$pdf->Rect(10, 10, $pageWidth - 20, $pageHeight - 20, 'D');

// --- C. Title: Certificate of Completion ---
$pdf->SetY(40);
$pdf->SetFont('Times', 'B', 48); // Formal font
$pdf->SetTextColor($eduLuxR, $eduLuxG, $eduLuxB); 
$pdf->Cell(0, 30, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');
$pdf->Ln(5);

// --- D. Body Text ---
$pdf->SetTextColor($darkGray, $darkGray, $darkGray); // Dark Gray text

// Line 1: This certifies that
$pdf->SetFont('Helvetica', '', 18);
$pdf->Cell(0, 15, 'This certifies that', 0, 1, 'C');
$pdf->Ln(5);

// Line 2: Name (Student)
$pdf->SetFont('Times', 'B', 38);
// Student Name is fetched via JOIN users u
$pdf->Cell(0, 25, strtoupper($cert['first_name'] . ' ' . $cert['last_name']), 0, 1, 'C');
$pdf->Ln(5);

// Line 3: has successfully completed the course
$pdf->SetFont('Helvetica', '', 18);
$pdf->Cell(0, 15, 'has successfully completed the premium program', 0, 1, 'C');
$pdf->Ln(5);

// Line 4: Course Title
$pdf->SetFont('Times', 'BI', 32); // Italic for Course Title
$pdf->SetTextColor($eduLuxR, $eduLuxG, $eduLuxB); 
$pdf->Cell(0, 25, $cert['title'], 0, 1, 'C');
$pdf->Ln(15);

// --- E. Footer Details (Signatures/Validation) ---
$pdf->SetTextColor(100, 100, 100); 
$pdf->SetFont('Helvetica', '', 12);

// Column 1: Issue Date
$pdf->SetX(20);
$pdf->Cell(80, 5, 'Issued on: ' . date('F j, Y', strtotime($cert['issued_at'])), 0, 0, 'L');

// Column 2: Instructor Signature (Placeholder line)
$pdf->SetX($pageWidth / 2 - 40);
$pdf->Cell(80, 5, '_________________________', 0, 0, 'C');

// Column 3: Validation Code
$pdf->SetX($pageWidth - 100);
$pdf->Cell(80, 5, 'Validation Code: ' . $cert['certificate_code'], 0, 1, 'R');

// Instructor Name
$pdf->SetX($pageWidth / 2 - 40);
$pdf->SetY($pdf->GetY() + 5);
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(80, 5, 'Instructor: ' . $cert['instructor_first_name'] . ' ' . $cert['instructor_last_name'], 0, 1, 'C');


// Final Output - Force download prompt
$pdf->Output('D', 'EduLux_Certificate_' . $cert['certificate_code'] . '.pdf');
?>