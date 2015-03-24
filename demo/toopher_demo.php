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

require_once('bootstrap.php');

$stdin = fopen('php://stdin', 'r');

const DEFAULT_USERNAME = 'demo@toopher.com';
const DEFAULT_TERMINAL_NAME = 'my computer';

function printHorizontalLine($character = '-')
{
    echo(str_repeat($character, 40) . "\n");
}

function printTextWithUnderline($text, $character = '-')
{
    echo ("\n" . $text . "\n");
    printHorizontalLine($character);
}

function initializeApi()
{
    global $stdin;

    $key = getenv('TOOPHER_CONSUMER_KEY');
    $secret = getenv('TOOPHER_CONSUMER_SECRET');

    if(empty($key) || empty($secret)){
        echo("Enter your requester credentials (from https://dev.toopher.com).\n");
        echo("Hint: Set the TOOPHER_CONSUMER_SECRET and TOOPHER_CONSUMER_SECRET environment variables to avoid this prompt.\n");
        echo('TOOPHER_CONSUMER_KEY:');
        $key = rtrim(fgets($stdin));
        echo('TOOPHER_CONSUMER_SECRET:');
        $secret = rtrim(fgets($stdin));
    }

    return new ToopherApi($key, $secret, getenv('TOOPHER_BASE_URL'));
}

function pairDeviceWithToopher($toopher)
{
    global $stdin;

    while(true) {
        printTextWithUnderline('STEP 1: Pair requester with phone');
        echo("Pairing phrases are generated on the mobile app\n");

        do {
            echo('Enter pairing phrase: ');
            $phrase = rtrim(fgets($stdin));
        } while (empty($phrase));


        echo(sprintf('Enter a username for this pairing [%s]: ', DEFAULT_USERNAME));
        $userName = rtrim(fgets($stdin));

        if (empty($userName)) {
            $userName = DEFAULT_USERNAME;
        }

        try {
            $pairing = $toopher->pair($userName, $phrase);
            break;
        } catch (Exception $e) {
            echo (sprintf("The pairing phrase was not accepted (Reason: %s) \n", $e->getMessage()));
        }
    }

    while(true) {
        echo('Authorize pairing on phone and then press return to continue.');
        rtrim(fgets($stdin));
        echo("\nChecking status of pairing request...\n");

        try {
            $pairing->refreshFromServer();
            if ($pairing->pending) {
                echo("The pairing has not been authorized by the phone yet.\n");
            } elseif ($pairing->enabled) {
                echo("Pairing complete\n");
                return $pairing;
            } else {
                echo("The pairing has been denied.\n");
                break;
            }
        } catch (Exception $e) {
            echo (sprintf("Could not check pairing status (Reason: %s)\n", $e->getMessage()));
        }
    }
}

function authenticateWithToopher($pairing, $toopher)
{
    global $stdin;

    while(true) {
        printTextWithUnderline('STEP 2: Authenticate log in');

        echo(sprintf('Enter a terminal name for this authentication request [%s]: ', DEFAULT_TERMINAL_NAME));
        $terminalName = rtrim(fgets($stdin));

        if (empty($terminalName)) {
            $terminalName = DEFAULT_TERMINAL_NAME;
        }

        echo("Sending authentication request...\n");
        try {
            $auth = $toopher->authenticate($pairing->user->name, $terminalName);
        } catch (Exception $e) {
            echo (sprintf('Error initiating authentication (Reason: %s)', $e->getMessage()));
            break;
        }

        while(true) {
            echo ('Respond to authentication request on phone and then press return to continue.');
            rtrim(fgets($stdin));
            echo ("\nChecking status of authentication request...\n");

            try {
                $auth->refreshFromServer();
            } catch (Exception $e) {
                echo (sprintf('Could not check authentication status (Reason: %s)', $e->getMessage()));
            }

            if ($auth->pending) {
                echo ("The authentication request has not received a response from the phone yet.\n");
            } else {
                $automation = $auth->automated ? 'automatically ' : '';
                $result = $auth->granted ? 'granted' : 'denied';
                echo ('The request was ' . $automation . $result . "!\n" );
                break;
            }
        }
        echo('Press return to authenticate again, or [Ctrl+C] to exit');
        rtrim(fgets($stdin));
    }
}

function demo()
{
    printTextWithUnderline('Toopher Library Demo', '=');

    $toopher = initializeApi();

    do {
        $pairing = pairDeviceWithToopher($toopher);
    } while (!$pairing);

    authenticateWithToopher($pairing, $toopher);
}

demo()

?>
