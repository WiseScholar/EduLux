<?php
// student/certificate.php - Enhanced Premium Design
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use Fpdf\Fpdf;

// --- 1. SESSION & SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "pages/auth/login.php");
    exit;
}

$course_id = (int)$_GET['course_id'];
$user_id = $_SESSION['user_id'];

// --- 2. DATA FETCH ---
$cert = $pdo->prepare("SELECT cert.*, c.title, u.first_name, u.last_name FROM certificates cert 
           JOIN courses c ON c.id = cert.course_id 
           JOIN users u ON u.id = cert.user_id 
           WHERE cert.user_id = ? AND cert.course_id = ?");
$cert->execute([$user_id, $course_id]);
$cert = $cert->fetch();

if (!$cert) die("Certificate not found or not yet issued.");


// --- 3. FPDF GENERATION (Enhanced Design) ---

$pdf = new Fpdf('L', 'mm', 'A4'); // Landscape A4 format
$pdf->AddPage();
$pageWidth = $pdf->GetPageWidth();
$pageHeight = $pdf->GetPageHeight();

// Define EduLux Primary Color (e.g., #4f46e5 converted to RGB)
$eduLuxR = 79;
$eduLuxG = 70;
$eduLuxB = 229;

// --- A. Border (Optional - adds formal structure) ---
$pdf->SetLineWidth(3);
$pdf->SetDrawColor($eduLuxR, $eduLuxG, $eduLuxB);
$pdf->Rect(10, 10, $pageWidth - 20, $pageHeight - 20, 'D');

// --- B. Logo/Header (Placeholder Image) ---
// If you have a logo image, use it here. E.g., $pdf->Image('path/to/logo.png', 130, 20, 30);

// --- C. Title: Certificate of Completion ---
$pdf->SetY(40);
$pdf->SetFont('Times', 'B', 48); // Formal font
$pdf->SetTextColor($eduLuxR, $eduLuxG, $eduLuxB); 
$pdf->Cell(0, 30, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');
$pdf->Ln(5);

// --- D. Body Text ---
$pdf->SetTextColor(50, 50, 50); // Dark Gray text

// Line 1: This certifies that
$pdf->SetFont('Helvetica', '', 18);
$pdf->Cell(0, 15, 'This certifies that', 0, 1, 'C');
$pdf->Ln(5);

// Line 2: Name
$pdf->SetFont('Times', 'B', 38);
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

// --- E. Footer Details ---
$pdf->SetTextColor(100, 100, 100); 
$pdf->SetFont('Helvetica', '', 12);
$pdf->Cell($pageWidth / 2, 10, 'Issued on: ' . date('F j, Y', strtotime($cert['issued_at'])), 0, 0, 'L');
$pdf->Cell($pageWidth / 2 - 20, 10, 'Validation Code: ' . $cert['certificate_code'], 0, 1, 'R');

// Final Output
$pdf->Output('D', 'EduLux_Certificate_' . $cert['certificate_code'] . '.pdf');
?>