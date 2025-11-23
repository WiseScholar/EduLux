<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Fpdf\Fpdf;
var_dump(class_exists('Fpdf\Fpdf'));
try {
    $pdf = new Fpdf();
    echo "Fpdf loaded successfully.";
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>