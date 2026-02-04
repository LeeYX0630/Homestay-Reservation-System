<?php
// Module C/generate_receipt.php
session_start();
require_once '../includes/db_connection.php';

// 验证
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['user_id'])) {
    die("Access Denied");
}

if (!isset($_GET['booking_id'])) die("Invalid Request");
$bid = intval($_GET['booking_id']);

// 获取详细数据
$sql = "SELECT b.*, r.room_name, u.full_name, u.email, u.phone 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        LEFT JOIN users u ON b.user_id = u.user_id 
        WHERE b.booking_id = '$bid'";
$result = $conn->query($sql);

if ($result->num_rows == 0) die("Booking not found");
$data = $result->fetch_assoc();

// 计算天数
$d1 = new DateTime($data['check_in_date']);
$d2 = new DateTime($data['check_out_date']);
$diff = $d1->diff($d2);
$days = $diff->days;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo $bid; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Helvetica', sans-serif; background: #555; padding: 20px; display: flex; justify-content: center; }
        
        #receipt-box {
            background: white;
            width: 180mm; /* A4 宽度稍小一点 */
            min-height: 200mm;
            padding: 20mm;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }

        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .brand h1 { margin: 0; color: #d35400; font-size: 28px; }
        .brand p { margin: 5px 0 0; font-size: 12px; color: #666; }
        
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0; color: #333; }
        .badge { background: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; vertical-align: middle; }

        .info-section { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .info-col h4 { border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; color: #555; }
        .info-col p { margin: 3px 0; font-size: 14px; color: #333; }

        .table-box { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table-box th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #ddd; }
        .table-box td { padding: 12px; border-bottom: 1px solid #eee; }
        .total-row td { font-weight: bold; font-size: 18px; border-top: 2px solid #333; color: #d35400; }

        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        
        .btn-download {
            position: fixed; top: 20px; right: 20px;
            background: #d35400; color: white; padding: 15px 30px;
            border: none; border-radius: 5px; cursor: pointer; font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .btn-download:hover { background: #e67e22; }
    </style>
</head>
<body>

    <button onclick="downloadPDF()" class="btn-download" id="dlBtn">Download PDF</button>

    <div id="receipt-box">
        <div class="header">
            <div class="brand">
                <h1>Teh Tarik No Tarik</h1>
                <p>Ayer Keroh, Melaka, Malaysia</p>
                <p>Support: +60 12-345 6789</p>
            </div>
            <div class="invoice-details">
                <h2>RECEIPT</h2>
                <p>Booking ID: <strong>#<?php echo $bid; ?></strong></p>
                <p>Date: <?php echo date("d M Y"); ?></p>
                <span class="badge">PAID</span>
            </div>
        </div>

        <div class="info-section">
            <div class="info-col">
                <h4>Bill To:</h4>
                <p><strong><?php echo $data['full_name']; ?></strong></p>
                <p><?php echo $data['email']; ?></p>
                <p><?php echo $data['phone']; ?></p>
            </div>
            <div class="info-col" style="text-align: right;">
                <h4>Stay Details:</h4>
                <p>Check-In: <strong><?php echo $data['check_in_date']; ?></strong></p>
                <p>Check-Out: <strong><?php echo $data['check_out_date']; ?></strong></p>
                <p>Duration: <strong><?php echo $days; ?> Nights</strong></p>
            </div>
        </div>

        <table class="table-box">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align:center;">Rate</th>
                    <th style="text-align:center;">Nights</th>
                    <th style="text-align:right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?php echo $data['room_name']; ?></strong><br>
                        <small style="color:#777;">Accommodation Charges</small>
                    </td>
                    <td style="text-align:center;">RM <?php echo number_format($data['total_price'] / $days, 2); ?></td>
                    <td style="text-align:center;"><?php echo $days; ?></td>
                    <td style="text-align:right;">RM <?php echo number_format($data['total_price'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right;">GRAND TOTAL</td>
                    <td style="text-align:right;">RM <?php echo number_format($data['total_price'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Thank you for choosing Teh Tarik No Tarik Homestay!</p>
            <p>This is a computer-generated receipt. No signature is required.</p>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('receipt-box');
            const btn = document.getElementById('dlBtn');
            
            btn.style.display = 'none';

            html2pdf()
                .set({
                    margin: 10,
                    filename: 'Receipt_<?php echo $bid; ?>.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                })
                .from(element)
                .save()
                .then(() => {
                    btn.style.display = 'block';
                });
        }
    </script>

</body>
</html>