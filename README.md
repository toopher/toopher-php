# ToopherAPI PHP Client

#### Introduction
ToopherAPI PHP Client simplifies the task of interfacing with the Toopher API from PHP code.  This project includes all the dependency libraries and handles the required OAuth and JSON functionality so you can focus on just using the API.

#### Learn the Toopher API
Make sure you visit [http://dev.toopher.com](http://dev.toopher.com) to get acquainted with the Toopher API fundamentals.  The documentation there will tell you the details about the operations this API wrapper library provides.

#### OAuth Authentication

The first step to accessing the Toopher API is to sign up for an account at the development portal [http://dev.toopher.com](http://dev.toopher.com) and create a "requester". When that process is complete, your requester is issued OAuth 1.0a credentials in the form of a consumer key and secret. Your key is used to identify your requester when Toopher interacts with your customers, and the secret is used to sign each request so that we know it is generated by you.  This library properly formats each request with your credentials automatically.

#### The Toopher Two-Step
Interacting with the Toopher web service involves two steps: pairing, and authenticating.

##### Pair
Before you can enhance your website's actions with Toopher, your customers will need to pair their phone's Toopher app with your website.  To do this, they generate a unique, nonsensical "pairing phrase" from within the app on their phone.  You will need to prompt them for a pairing phrase as part of the Toopher enrollment process.  Once you have a pairing phrase, just send it to the Toopher API along with your requester credentials and we'll return a pairing ID that you can use whenever you want to authenticate an action for that user.

##### Authenticate
You have complete control over what actions you want to authenticate using Toopher (for example: logging in, changing account information, making a purchase, etc.).  Just send us the user's pairing ID, a name for the terminal they're using, and a description of the action they're trying to perform and we'll make sure they actually want it to happen.

#### Librarified
This library makes it super simple to do the Toopher two-step.  Check it out:

```php
require_once("toopher_api.php");

// Create an API object using your credentials
$toopherApi = new ToopherAPI($key, $secret);

// Step 1 - Pair with their phone's Toopher app
$pairingStatus = $toopherApi->pair("pairing phrase", "username@yourservice.com");

// Step 2 - Authenticate a log in
$authStatus = $toopherApi->authenticate($pairingStatus['id'], "my computer");

// Once they've responded you can then check the status
while($authStatus['pending']){
    $authStatus = $toopherApi->getAuthenticationStatus($authStatus['id']);
    sleep(1);
}
if($authStatus['granted']){
    // Success!
} else {
    // user declined the authorization!
}
```

#### Dependencies
Toopher manages dependencies with [composer](http://getcomposer.org).  To ensure all dependencies are up-to-date, execute the following command:
```shell
$ composer install
```
from the root directory the package (the same directory that this README is located in)

#### Handling Errors
If any request runs into an error a `ToopherRequestException` will be thrown with more details on what went wrong.

#### Example code
Check out demo/toopher_demo.php for an example program that walks you through the whole process!  Simply execute the script as follows:
```shell
$ php demo/toopher_demo.php
```
To avoid being prompted for your Toopher API key and secret, you can define them in the $TOOPHER_CONSUMER_KEY and $TOOPHER_CONSUMER_SECRET environment variables

#### Tests
To run all unit tests:
```shell
$ phpunit test/test_toopher_api.php
```
Note: `phpunit` may be found in `vendor/bin/php` so your test command
would be
```shell
$ vendor/bin/phpunit test/test_toopher_api.php
```
