<?php
session_start();
if(isset($_SESSION['admin'])){
    include('../../inc/conn.php');
    $get_invoices = $conn->query('SELECT * FROM invoices')->fetchAll();
} else {
    header('Location: ../login.php');
    die();
}

$money = 0;
foreach($get_invoices as $invoice){
    if($invoice['paid'] == "1"){
        $money = $money + $invoice['amount']; 
    }
}
$usd = json_decode(file_get_contents("https://blockchain.info/ticker"))->USD->last * $money;

if(isset($_POST['address'])){
    $address = $_POST['address'];
    foreach($get_invoices as $invoice){
        if($invoice['paid'] == 1){
            exec('python3 send_all_funds.py ' . $invoice['private_key'] . " " . $_POST['address']);
            $update = $conn->prepare('UPDATE invoices SET paid = "3" WHERE id = (:id)');
            $update->bindParam(':id', $invoice['id']);
            $update->execute();
        }
    }
}

if(isset($_GET['cancel'])){
    $update = $conn->prepare('UPDATE invoices SET paid = "2" WHERE id = (:id)');
    $update->bindParam(':id', $_GET['cancel']);
    $update->execute();
}
?>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
<style>
input {
    width: 80% !important;
    display: inline-block !important;
}

.button-custom {
    width: 20% !important;
    display: inline-block !important;
}
</style>
<br><h1>BTC IPN</h1>
<h4><a href="withdraw.php">Withdraw Panel</a></h4>
<h4><a href="logout.php">Logout</a></h4>
<h5>Total Available: <?php echo $money; ?> BTC <span style="color: green;">($<?php echo round($usd, 2); ?>)</span></h5>
<form method="POST">
    <input name="address" class="form-control" type="text" placeholder="BTC Address to move all the money"><button class="btn btn-primary button-custom" type="submit">Send</button> 
</form>
<table class="table table-dark">
<tr>
    <th>#</th>
    <th>SteamID</th>
    <th>Public Address</th>
    <th>Private Address</th>
    <th>Amount</th>
    <th>Credits</th>
    <th>Status</th>
    <th>Date</th>
    <th>Cancel</th>
</tr>
<?php
foreach($get_invoices as $invoice){
    echo '<tr>';
    echo '<td>'.$invoice['id'].'</td>';
    echo '<td>'.$invoice['steamid'].'</td>';
    echo '<td>'.$invoice['public_key'].'</td>';
    echo '<td>'.$invoice['private_key'].'</td>';
    echo '<td>'.$invoice['amount'].'</td>';
    echo '<td>'.$invoice['credits'].'</td>';
    if($invoice['paid'] == "1"){
        echo '<td>Paid</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
    } elseif($invoice['paid'] == "0"){
        echo '<td>Waiting</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
        echo '<td><button type="button" class="btn btn-danger" onclick="fetch(\'http://ipn.yourdomain.com/payments/dashboard/?cancel='.$invoice['id'].'\');">Cancel Invoice</button></td>';
    } else {
        echo '<td>Cancelled</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
    }
    echo '</tr>';

    // made by Amit
}
?>
</table>