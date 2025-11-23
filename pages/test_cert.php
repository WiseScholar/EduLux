<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check autoloader
$autoload_path = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    die("Error: Composer autoload file not found at $autoload_path. Run 'composer install' in the project root (C:\\xampp\\htdocs\\project\\E-learning-platform).");
}

require_once $autoload_path;

use Fpdf\Fpdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Verify classes exist
if (!class_exists('Fpdf\Fpdf')) {
    die("Error: Fpdf class not found. Ensure 'fpdf/fpdf' is installed via Composer and check namespace in vendor/fpdf/fpdf/src/Fpdf/Fpdf.php.");
}
if (!class_exists('Endroid\QrCode\QrCode')) {
    die("Error: QrCode class not found. Ensure 'endroid/qr-code' is installed via Composer.");
}

// Sample data for testing
$student_name = "John Doe";
$course_title = "Introduction to Programming";
$instructor_name = "Jane Smith";
$completion_date = date('Y-m-d');
$certificate_id = uniqid('CERT_'); // Unique certificate ID for verification

// Create certificate directory
$certificate_dir = dirname(__DIR__) . '/assets/uploads/certificates/';
if (!is_dir($certificate_dir)) {
    if (!mkdir($certificate_dir, 0777, true)) {
        die("Error: Failed to create directory $certificate_dir.");
    }
}

// Generate QR code
try {
    $qr_text = "Certificate ID: $certificate_id\nStudent: $student_name\nCourse: $course_title\nCompleted: $completion_date";
    $qrCode = new QrCode($qr_text);
    $writer = new PngWriter();
    $qr_path = $certificate_dir . time() . '_qrcode.png';
    file_put_contents($qr_path, $writer->write($qrCode)->getString()) or die("Error: Failed to write QR code to $qr_path");
} catch (Exception $e) {
    die("Error generating QR code: " . $e->getMessage());
}

// Generate PDF certificate
class PDF extends Fpdf {
    function Header() {
        // Optional: Add logo
        $logo_path = dirname(__DIR__) . '/assets/images/logo.png';
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 80, 10, 50);
            $this->Ln(40);
        } else {
            $this->Ln(20);
        }
        // Title
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(0, 51, 102); // Dark blue
        $this->Cell(0, 15, 'Certificate of Completion', 0, 1, 'C');
        $this->Ln(10);
        // Decorative border
        $this->SetLineWidth(1);
        $this->SetDrawColor(0, 51, 102);
        $this->Rect(10, 10, 190, 277); // A4 size border
        $this->SetLineWidth(0.2);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'EduLux E-learning Platform | Certificate ID: ' . $GLOBALS['certificate_id'], 0, 0, 'C');
    }

    function CertificateBody($student_name, $course_title, $instructor_name, $completion_date, $qr_path) {
        $this->SetFont('Arial', '', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(20);
        $this->Cell(0, 10, 'This certifies that', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(0, 102, 0); // Green for name
        $this->Cell(0, 12, $student_name, 0, 1, 'C');
        
        $this->SetFont('Arial', '', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'has successfully completed the course', 0, 1, 'C');
        
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 51, 102);
        $this->Cell(0, 12, $course_title, 0, 1, 'C');
        
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(10);
        $this->Cell(0, 10, 'Instructed by ' . $instructor_name, 0, 1, 'C');
        $this->Cell(0, 10, 'Date of Completion: ' . $completion_date, 0, 1, 'C');
        
        // Add QR code
        if (file_exists($qr_path)) {
            $this->Image($qr_path, 80, 180, 50, 50);
        } else {
            $this->Cell(0, 10, 'Error: QR code not found.', 0, 1, 'C');
        }
        
        // Add verification text
        $this->Ln(60);
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 8, 'Scan the QR code to verify this certificate.', 0, 1, 'C');
    }
}

// Create PDF
try {
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->CertificateBody($student_name, $course_title, $instructor_name, $completion_date, $qr_path);
    
    // Save PDF to a file for download
    $pdf_path = $certificate_dir . 'certificate_' . time() . '.pdf';
    $pdf->Output('F', $pdf_path);
    
    // Clean up QR code file
    if (file_exists($qr_path)) {
        unlink($qr_path);
    }
} catch (Exception $e) {
    die("Error generating PDF: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Certificate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .glass-card { 
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 10px; 
            backdrop-filter: blur(10px); 
            padding: 20px; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); 
        }
    </style>
</head>
<body>
    <section class="section-padding py-5">
        <div class="container">
            <h2 class="display-4 fw-bold mb-4 text-center">Test Certificate Preview</h2>
            <p class="text-muted text-center mb-5">Review the certificate below and download it.</p>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="glass-card p-4">
                        <p class="text-center">The certificate has been generated. You can download it below.</p>
                        <div class="text-center mb-4">
                            <a href="../assets/uploads/certificates/<?php echo basename($pdf_path); ?>" 
                               class="btn btn-primary" download>Download Certificate</a>
                        </div>
                        <p class="text-center text-muted">Note: This is a test certificate for John Doe for the course "Introduction to Programming".</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>