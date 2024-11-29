<?php
// Start output buffering immediately
ob_start();
session_start();
$UserId = $_SESSION['UserId'];

// Include necessary files
require_once('../includes/notification.php');
require_once('../includes/db.php');
require_once('../includes/plugin/tcpdf/tcpdf.php');

// Fetch user info
$GetUserInfo = "SELECT * FROM user WHERE UserId = $UserId";
$UserInfo = mysqli_query($mysqli, $GetUserInfo);
$ColUser = mysqli_fetch_assoc($UserInfo);

// Filter report income if a search term is provided
$SearchTerm = isset($_GET['filter']) ? mysqli_real_escape_string($mysqli, $_GET['filter']) : '';
$GetIncomeHistory = "
    SELECT assets.*, category.CategoryName, account.AccountName 
    FROM assets 
    LEFT JOIN category ON assets.CategoryId = category.CategoryId 
    LEFT JOIN account ON assets.AccountId = account.AccountId 
    WHERE assets.UserId = $UserId
      AND (assets.Title LIKE '%$SearchTerm%' 
      OR account.AccountName LIKE '%$SearchTerm%'
      OR assets.Description LIKE '%$SearchTerm%' 
      OR category.CategoryName LIKE '%$SearchTerm%')
    ORDER BY assets.Date DESC";
$IncomeReport = mysqli_query($mysqli, $GetIncomeHistory);

// Set up TCPDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Horizon Finance Manager');
$pdf->SetTitle('Income Report');
$pdf->SetHeaderData('logo.gif', 20, 'Your Company Name', 'Income Report');
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

// Table headers
$tbl_header = '<table border="1" align="center" cellpadding="4">';
$thead = '
    <thead>
        <tr align="center" style="font-weight: bold;">
            <th style="width:200px;">Title</th>
            <th style="width:100px;">Date</th>
            <th style="width:150px;">Category</th>
            <th style="width:150px;">Account</th>
            <th style="width:250px;">Description</th>
            <th style="width:100px;">Amount</th>
        </tr>
    </thead>';
$tbl_footer = '</table>';

// Table content
$tbl = '';
$Sum = 0;
while ($col = mysqli_fetch_assoc($IncomeReport)) {
    $Title = htmlspecialchars($col['Title']);
    $Date = date("M d, Y", strtotime($col['Date']));
    $CategoryName = htmlspecialchars($col['CategoryName']);
    $AccountName = htmlspecialchars($col['AccountName']);
    $Description = htmlspecialchars($col['Description']);
    $Amount = $ColUser['Currency'] . ' ' . number_format($col['Amount'], 2);

    $Sum += $col['Amount'];

    $tbl .= "
        <tr>
            <td style='width:200px;'>$Title</td>
            <td style='width:100px;'>$Date</td>
            <td style='width:150px;'>$CategoryName</td>
            <td style='width:150px;'>$AccountName</td>
            <td style='width:250px;'>$Description</td>
            <td style='width:100px;'>$Amount</td>
        </tr>";
}

// Total income
$totalRow = "<h4 style='text-align:right; font-weight:bold;'>Total: " . htmlspecialchars($ColUser['Currency']) . " " . number_format($Sum, 2) . "</h4>";

// Output PDF content
$pdfContent = $tbl_header . $thead . $tbl . $tbl_footer . $totalRow;
$pdf->writeHTML($pdfContent, true, false, false, false, '');

// Clear output buffer and send the PDF
ob_end_clean();
$pdf->Output('Income_Report.pdf', 'I');
?>
