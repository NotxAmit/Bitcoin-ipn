<?php
session_start();
if(isset($_SESSION['admin'])){
    include('../../inc/conn.php');
    $get_invoices = $conn->query('SELECT * FROM withdraw')->fetchAll();
} else {
    header('Location: ../login.php');
    die();
}

$money = 0;
foreach($get_invoices as $invoice){
    if($invoice['type'] == "0"){
        $money = $money + $invoice['amount']; 
    }
}
$usd = json_decode(file_get_contents("https://blockchain.info/ticker"))->USD->last * $money;

if(isset($_GET['cancel'])){
    $update = $conn->prepare('UPDATE withdraw SET type = "2" WHERE id = (:id)');
    $update->bindParam(':id', $_GET['cancel']);
    $update->execute();
}

if(isset($_GET['paid'])){
    $update = $conn->prepare('UPDATE withdraw SET type = "1" WHERE id = (:id)');
    $update->bindParam(':id', $_GET['paid']);
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
<br><h1>Withdraw Requests</h1>
<h4><a href="index.php">Invoices</a></h4>
<h5>Total Owed: <?php echo $money; ?> BTC <span style="color: green;">($<?php echo round($usd, 2); ?>)</span></h5>
<table class="table table-dark">
<tr>
    <th>#</th>
    <th>SteamID</th>
    <th>Total Won</th>
    <th>Total Lost</th>
    <th>Public Address</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Date</th>
    <th>Paid</th>
    <th>Cancel</th>
</tr>
<?php
foreach($get_invoices as $invoice){
    echo '<tr>';
    echo '<td>'.$invoice['id'].'</td>';
    echo '<td>'.$invoice['steamid'].'</td>';
    if($invoice['type'] == 0){
        $select = $conn->prepare('SELECT * FROM users WHERE steamid = (:steamid)');
        $select->bindParam(':steamid', $invoice['steamid']);
        $select->execute();
        $out0 = $select->fetch();

        echo '<td>'.$out0['total_won'].'</td>';
        echo '<td>'.$out0['total_lose'].'</td>';

    } else {
        echo '<td>N/A</td>';
        echo '<td>N/A</td>';
    }
    echo '<td>'.$invoice['address'].'</td>';
    echo '<td>'.$invoice['amount'].' BTC</td>';
    if($invoice['type'] == "0"){
        echo '<td>Unpaid</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
        echo '<td><button type="button" class="btn btn-success" onclick="fetch(\'https://ipn.yourdomain.com//payments/dashboard/withdraw.php?paid='.$invoice['id'].'\');">Paid</button></td>';
        echo '<td><button type="button" class="btn btn-danger" onclick="fetch(\'http://ipn.yourdomain.com//payments/dashboard/withdraw.php?cancel='.$invoice['id'].'\');">Cancel Invoice</button></td>';
    } elseif($invoice['type'] == "1"){
        echo '<td>Paid</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
    } else {
        echo '<td>Cancelled</td>';
        echo '<td>'.date("m/d/y", $invoice['timestamp']).'</td>';
        echo '<td></td>';
        echo '<td></td>';
    }
    echo '</tr>';
}
?>
</table>