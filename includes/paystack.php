<?php
define('PAYSTACK_SECRET_KEY', 'sk_test_d4c5f35b32d2139b1e4d5bdf26199d771dac3eb8'); // Replace with live key later
define('PAYSTACK_PUBLIC_KEY', 'pk_test_6f980ab06190756f228e90c43b4086c9eb86c7e0'); // Add your public key
define('PAYSTACK_CALLBACK_URL', BASE_URL . 'payment/callback.php');
define('PAYSTACK_VERIFY_URL', 'https://api.paystack.co/transaction/verify/');
?>