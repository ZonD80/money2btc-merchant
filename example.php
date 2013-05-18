<?php

/*
 * This is integration example. This page should be located on pingback_uri of your site.
 * WARNING, THIS SCRIPT WILL NOT WORK ON PRODUCTION, THIS IS PSEUDO-CODE ONLY FOR DEMONSTRATION OF YOUR MERCHANT LOGIC
 * 
 * AGAIN, THIS IS PSEUDO-CODE, includes following:
 * 1. use of old mysql library
 * 2. unescaped values
 * 3. mysql connection was not initialized
 * 
 * Example table "transactions":
 * UUID varchar(37) PRIMARY KEY
 * account_id int(10) UNSIGNED NOT NULL
 * good_id int(10) UNSIGNED NOT NULL
 * status varchar(60)
 */

require_once 'classes/money2btc.class.php';

// here we handle pushed IPN query
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // check and get transaction details from moneybtc
    $transaction = (money2btc::check_transaction($_POST['data'], $_POST['request_key']));
    if (!$transaction->success)
        die(); // request failed or transaction data mismatch


        
// get UUID of transaction
    $uuid = $transaction->receipt['info']['uuid'];
    //check that transaction is already processed
    $already_exists = mysql_query("SELECT COUNT(*) FROM transactions WHERE uuid='$uuid' AND status='ok'");
    if ($already_exists)
        die('duplicate transaction');

    // decrypt data array and check it
    $data = json_decode($transaction->decrypted_data, true);
    // here is account id for example
    $account_id = $data['account_id'];
    // and good id for exmaple
    $good_id = $data['good_id'];
    // get status of transaction
    $status = $transaction->receipt['info']['status'];

    mysql_query("INSERT INTO transactions (uuid,account_id,good_id,status) VALUES ($uuid,$account_id,$good_id,'$status') ON DUPLICATE KEY UPDATE uuid=$uuid,account_id=$account_id,good_id=$good_id,status='status'");
    if ($status == 'ok') {
        /*
         * Here provide your goods to user
         */
        die('OK');
    }
}

// here general page code:
$uuid = (string) $_GET['uuid'];

if ($uuid) {
    $transaction = mysql_query("SELECT * FROM transactions WHERE uuid='uuid'");
    if (!$transaction) {
        /*
         * Display error message to user, that transaction does not completed yet
         */
    } elseif ($transaction['status'] == 'failed_bc' || $transaction['status'] == 'failed_gw') {
        /*
         * Display failed message. This transaction failed
         */
    } elseif ($transaction['status'] == 'ok') {
        /*
         * Display success message to user
         */
    }
} else {
    /*
     * here is your checkout button and payment form
     */
    // encode your data here
    $data = json_encode(array('account_id' => 'buyer account id', 'good_id' => 'your good ID'));
    // initialize monet2btc class
    $m2btc = new money2btc('URI of your page', $data, "Buy Wonderful good", 10.10/* for ten bucks and ten cents */, 'your bitcoin address here');
    // print form with buy button
    print $m2btc->get_form();
}
?>