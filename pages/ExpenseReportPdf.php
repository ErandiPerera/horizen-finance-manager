<?php
// Start output buffering
ob_start();
session_start();
// Include necessary files
require_once('../includes/db.php');
require_once('../includes/plugin/tcpdf/tcpdf.php');

// Suppress notices and warnings to avoid disrupting PDF output
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Validate User ID from session
$UserId = isset($_SESSION['UserId']) ? intval($_SESSION['UserId']) : 0;
if ($UserId <= 0) {
    die("Error: Invalid User ID.");
}
// Fetch user details
$GetUserInfo = "SELECT * FROM user WHERE UserId = $UserId";
$UserInfo = mysqli_query($mysqli, $GetUserInfo);
$ColUser = mysqli_fetch_assoc($UserInfo);
if (!$ColUser) {
    die("Error: User data not found.");
}

// Fetch expense history
$SearchTerm = isset($_GET['filter']) ? mysqli_real_escape_string($mysqli, $_GET['filter']) : '';
$GetExpenseHistory = "
    SELECT bills.*, category.CategoryName, account.AccountName 
    FROM bills 
    LEFT JOIN category ON bills.CategoryId = category.CategoryId 
    LEFT JOIN account ON bills.AccountId = account.AccountId 
    WHERE bills.UserId = $UserId 
      AND (bills.Title LIKE '%$SearchTerm%' 
           OR account.AccountName LIKE '%$SearchTerm%' 
           OR bills.Description LIKE '%$SearchTerm%' 
           OR category.CategoryName LIKE '%$SearchTerm%') 
    ORDER BY bills.Dates DESC";
$ExpenseReport = mysqli_query($mysqli, $GetExpenseHistory);
if (!$ExpenseReport) {
    die("Error: Could not fetch expense data.");
}
// Initialize PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Horizon Finance Manager');
$pdf->SetTitle('Expence Report');
$pdf->SetHeaderData('logo.gif', 20, 'Horizon Finance Manager', 'Expence Report');
$pdf->setFooterData([0, 64, 0], [0, 64, 128]);
$pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
$pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->SetFont('dejavusans', '', 9, '', true);
$pdf->AddPage('L', 'A4');

// Generate table headers
$tbl_header = '<table border="1" align="center" cellpadding="4">';
$thead = '
<thead align="center">
    <tr style="font-weight:bold; text-align:center;">
        <td style="width:200px;">Title</td>
        <td style="width:100px;">Date</td>
        <td style="width:150px;">Category</td>
        <td style="width:150px;">Account</td>
        <td style="width:250px;">Description</td>
        <td style="width:100px;">Amount</td>
    </tr>
</thead>';
$tbl_footer = '</table>';

// Populate table rows with data
$tbl = '';
$Sum = 0;
while ($col = mysqli_fetch_assoc($ExpenseReport)) {
    $Title = htmlspecialchars($col['Title']);
    $Date = date("M d, Y", strtotime($col['Dates']));
    $CategoryName = htmlspecialchars($col['CategoryName']);
    $AccountName = htmlspecialchars($col['AccountName']);
    $Description = htmlspecialchars($col['Description']);
    $Amount = htmlspecialchars($ColUser['Currency'] . ' ' . number_format($col['Amount'], 2));

    $Sum += $col['Amount'];

    $tbl .= "
    <tr>
        <td style='width:200px;'>$Title</td>
        <td style='width:100px;'>$Date</td>
        <td style='width:150px;'>$CategoryName</td>
        <td style='width:150px;'>$AccountName</td>
        <td style='width:250px;'>$Description</td>
        <td style='width:100px; text-align:right;'>$Amount</td>
    </tr>";
}

// Add total row
$totalRow = "
    <tr>
        <td colspan='5' style='text-align:right; font-weight:bold;'>Total:</td>
        <td style='width:100px; text-align:right; font-weight:bold;'>" . htmlspecialchars($ColUser['Currency']) . " " . number_format($Sum, 2) . "</td>
    </tr>";

// Combine table headers, content, and footer
$pdfContent = "<table border='1' align='center' cellpadding='4'>$thead<tbody>$tbl$totalRow</tbody></table>";

// Write content to PDF
$pdfContent = $tbl_header . $thead . $tbl . $tbl_footer . $totalRow;
$pdf->writeHTML($pdfContent, true, false, false, false, '');

// Clear output buffer and output the PDF
ob_end_clean();
header('Content-Type: application/pdf');
$pdf->Output('Expense_Report.pdf', 'I');
?>
