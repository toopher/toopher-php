<?php

/*
Copyright (c) 2012 Toopher, Inc

Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies
of the Software, and to permit persons to whom the Software is furnished to do
so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

require_once("bootstrap.php");

$stdin = fopen('php://stdin', 'r');

$key = getenv('TOOPHER_CONSUMER_KEY');
$secret = getenv('TOOPHER_CONSUMER_SECRET');
$url = getenv('TOOPHER_BASE_URL');
if(empty($key) || empty($secret)){
    echo("enter consumer credentials (set environment variables to prevent prompting):\n");
    echo("TOOPHER_CONSUMER_KEY=");
    $key = rtrim(fgets($stdin));
    echo("TOOPHER_CONSUMER_SECRET=");
    $secret = rtrim(fgets($stdin));
}

echo ("using key=$key, secret=$secret\n");
$toopher = new ToopherAPI($key, $secret, $url);

echo("\nSTEP 1: Pair device\n");
echo("enter pairing phrase:");
$phrase = rtrim(fgets($stdin));
echo("enter user name:");
$userName = rtrim(fgets($stdin));

$pairing = $toopher->pair($phrase, $userName);
$pairing_secret = $pairing['secret'];

while(!$pairing['enabled']){
    echo("waiting for authorization...\n");
    sleep(1);
    $pairing = $toopher->getPairingStatus($pairing['id']);
}

echo("paired successfully!\n");
echo("\nSTEP 2: Authenticate login\n");
echo("enter terminal name:");
$terminalName = rtrim(fgets($stdin));
echo("enter action name, or [ENTER] for none:");
while(true){
    $action = rtrim(fgets($stdin));
    echo("enter OTP for local validation, or [ENTER] to validate using Toopher API:");
    $otp = rtrim(fgets($stdin));
    if (strlen($otp) > 0) {
        echo("checking submitted OTP...\n");
        list ($otpValid, $otpDrift) = ToopherAPI::verifyOtp($pairing_secret, $otp);
        echo("OTP check = " . ($otpValid ? "valid with drift=$otpDrift" : "invalid") . ".  Enter another action to authorize again, or [Ctrl+C] to exit:");
    } else {
        echo("sending authentication request...\n");
        $auth = $toopher->authenticate($pairing['id'], $terminalName, $action);
        while($auth['pending']){
            echo("waiting for authentication...\n");
            sleep(1);
            $auth = $toopher->getAuthenticationStatus($auth['id']);
        }
    
        echo("Successfully authorized action '$action'.  Enter another action to authorize again, or [Ctrl+C] to exit:");
    }
}
?>
