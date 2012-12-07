<?php

require_once("bootstrap.php");

$stdin = fopen('php://stdin', 'r');

$key = getenv('TOOPHER_CONSUMER_KEY');
$secret = getenv('TOOPHER_CONSUMER_SECRET');
if(empty($key) || empty($secret)){
    echo("enter consumer credentials (set environment variables to prevent prompting):\n");
    echo("TOOPHER_CONSUMER_KEY=");
    $key = rtrim(fgets($stdin));
    echo("TOOPHER_CONSUMER_SECRET=");
    $secret = rtrim(fgets($stdin));
}

echo ("using key=$key, secret=$secret\n");
$toopher = new ToopherAPI($key, $secret);

echo("\nSTEP 1: Pair device\n");
echo("enter pairing phrase:");
$phrase = rtrim(fgets($stdin));
echo("enter user name:");
$userName = rtrim(fgets($stdin));

$pairing = $toopher->pair($phrase, $userName);

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
    echo("sending authentication request...\n");
    $auth = $toopher->authenticate($pairing['id'], $terminalName, $action);
    while($auth['pending']){
        echo("waiting for authentication...\n");
        sleep(1);
        $auth = $toopher->getAuthenticationStatus($auth['id']);
    }

    echo("Successfully authorized action '$action'.  Enter another action to authorize again, or [Ctrl+C] to exit:");
}
?>
