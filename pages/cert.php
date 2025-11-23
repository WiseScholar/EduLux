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

// Verify classes exist
if (!class_exists('Fpdf\Fpdf')) {
    die("Error: Fpdf class not found. Ensure 'fpdf/fpdf' is installed via Composer and check namespace in vendor/fpdf/fpdf/src/Fpdf/Fpdf.php.");
}

// Check GD extension for WebP support
if (!extension_loaded('gd') || !function_exists('imagecreatefromwebp')) {
    die("Error: GD extension with WebP support is required for WebP images. Enable GD in php.ini or convert images to PNG/JPEG.");
}

// Sample data for testing
$student_name = "EBENEZER MAWUFEMOR TEFE";
$course_title = "Introduction to Programming";
$instructor_name = "DR WELBECK";
$completion_date = date('Y-m-d');
$certificate_id = uniqid('CERT_'); // Unique certificate ID for verification

// Create certificate directory
$certificate_dir = dirname(__DIR__) . '/assets/uploads/certificates/';
if (!is_dir($certificate_dir)) {
    if (!mkdir($certificate_dir, 0777, true)) {
        die("Error: Failed to create directory $certificate_dir.");
    }
}

// Function to convert WebP to PNG
function convertWebPtoPNG($webp_path, $output_dir) {
    if (!file_exists($webp_path)) {
        return false;
    }
    $extension = strtolower(pathinfo($webp_path, PATHINFO_EXTENSION));
    if ($extension !== 'webp') {
        return $webp_path; // Return original path if not WebP
    }
    $image = imagecreatefromwebp($webp_path);
    if ($image === false) {
        die("Error: Failed to load WebP image $webp_path.");
    }
    $png_path = $output_dir . 'temp_' . time() . '_' . basename($webp_path, '.webp') . '.png';
    if (!imagepng($image, $png_path)) {
        imagedestroy($image);
        die("Error: Failed to convert WebP to PNG for $webp_path.");
    }
    imagedestroy($image);
    return $png_path;
}

// Generate PDF certificate
class PDF extends Fpdf {
    private $temp_files = []; // Track temporary files for cleanup

    function Header() {
        global $certificate_dir;

        // Background image (optional)
        $bg_path = dirname(__DIR__) . '/assets/images/erm1.webp';
        if (file_exists($bg_path)) {
            $bg_converted = convertWebPtoPNG($bg_path, $certificate_dir);
            if ($bg_converted) {
                $this->Image($bg_converted, 0, 0, 210, 297); // A4 size
                if ($bg_converted !== $bg_path) {
                    $this->temp_files[] = $bg_converted; // Track for cleanup
                }
            }
        }

        // Logo
        $logo_path = dirname(__DIR__) . '/assets/images/erm.webp';
        if (file_exists($logo_path)) {
            $logo_converted = convertWebPtoPNG($logo_path, $certificate_dir);
            if ($logo_converted) {
                $this->Image($logo_converted, 80, 15, 50);
                $this->Ln(40);
                if ($logo_converted !== $logo_path) {
                    $this->temp_files[] = $logo_converted; // Track for cleanup
                }
            }
        } else {
            $this->Ln(20);
        }

        // Watermark (subtle text)
        $this->SetFont('Arial', 'I', 40);
        $this->SetTextColor(200, 200, 200);
        $this->SetXY(0, 100);
        $this->Cell(210, 10, 'EduLux', 0, 0, 'C', false);
        
        // Double border (gold and navy)
        $this->SetLineWidth(1.5);
        $this->SetDrawColor(255, 215, 0); // Gold
        $this->Rect(10, 10, 190, 277);
        $this->SetLineWidth(0.5);
        $this->SetDrawColor(27, 38, 59); // Navy blue
        $this->Rect(12, 12, 186, 273);
        $this->SetLineWidth(0.2);

        // Title
        $this->SetFont('Times', 'B', 36); // Elegant serif font
        $this->SetTextColor(27, 38, 59); // Navy blue
        $this->SetY(60);
        $this->Cell(0, 15, 'Certificate of Completion', 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'EduLux E-learning Platform | Certificate ID: ' . $GLOBALS['certificate_id'], 0, 0, 'C');
    }

    function CertificateBody($student_name, $course_title, $instructor_name, $completion_date) {
        // Glassmorphism background for text block (regular rectangle)
        $this->SetFillColor(248, 248, 248); // Very light gray (#F8F8F8) for transparency
        $this->SetDrawColor(224, 224, 224); // Subtle gray border (#E0E0E0)
        $this->SetLineWidth(0.3);
        $this->Rect(30, 85, 150, 130, 'DF'); // Regular rectangle

        // Certificate text
        $this->SetFont('Times', 'B', 20); // Larger, bold
        $this->SetTextColor(0, 0, 0); // Black
        $this->SetY(90);
        $this->Cell(0, 12, 'This certifies that', 0, 1, 'C');
        
        $this->SetFont('Times', 'B', 30); // Larger for name
        $this->SetTextColor(255, 215, 0); // Gold
        $this->Cell(0, 15, $student_name, 0, 1, 'C');
        
        $this->SetFont('Times', 'B', 18); // Larger, bold
        $this->SetTextColor(0, 0, 0); // Black
        $this->Ln(5);
        $this->Cell(0, 12, 'has successfully completed the course', 0, 1, 'C');
        
        $this->SetFont('Times', 'B', 24); // Larger for course title
        $this->SetTextColor(27, 38, 59); // Navy blue
        $this->Cell(0, 15, $course_title, 0, 1, 'C');
        
        $this->SetFont('Times', 'B', 16); // Larger, bold
        $this->SetTextColor(0, 0, 0); // Black
        $this->Ln(10);
        $this->Cell(0, 12, 'Instructed by ' . $instructor_name, 0, 1, 'C');
        $this->Cell(0, 12, 'Date of Completion: ' . $completion_date, 0, 1, 'C');
        
        // Signature line
        $this->Ln(15);
        $this->SetFont('Times', 'I', 14); // Larger, italic
        $this->SetTextColor(0, 0, 0); // Black
        $this->Cell(0, 12, '___________________________', 0, 1, 'C');
        $this->Cell(0, 10, 'Instructor Signature', 0, 1, 'C');
    }

    // Cleanup temporary files
    function __destruct() {
        foreach ($this->temp_files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}

// Create PDF
try {
    /** @var PDF $pdf */
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->CertificateBody($student_name, $course_title, $instructor_name, $completion_date);
    
    // Save PDF to a file for download
    $pdf_path = $certificate_dir . 'certificate_' . time() . '.pdf';
    $pdf->Output('F', $pdf_path);
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
                        <p class="text-center text-muted">Note: This is a test certificate for <?php echo htmlspecialchars($student_name); ?> for the course "<?php echo htmlspecialchars($course_title); ?>".</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>