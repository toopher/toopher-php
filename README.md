# ToopherPHP [![Build Status](https://travis-ci.org/toopher/toopher-php.png?branch=master)](https://travis-ci.org/toopher/toopher-php)

ToopherPHP is a Toopher API library that simplifies the task of interfacing with the Toopher API from PHP code.  This project includes all the dependency libraries and handles the required OAuth and JSON functionality so you can focus on just using the API.

### PHP Version
\>=5.3.0

### Documentation
Make sure you visit [https://dev.toopher.com](https://dev.toopher.com) to get acquainted with the Toopher API fundamentals.  The documentation there will tell you the details about the operations this API wrapper library provides.

## ToopherApi Workflow

### Step 1: Pair
Before you can enhance your website's actions with Toopher, your customers will need to pair their mobile device's Toopher app with your website.  To do this, they generate a unique pairing phrase from within the app on their mobile device.  You will need to prompt them for a pairing phrase as part of the Toopher enrollment process.  Once you have a pairing phrase, just send it to the Toopher web service along with your requester credentials and we'll return a pairing ID that you can use whenever you want to authenticate an action for that user.

```php
require_once("toopher_api.php");

// Create an API object using your credentials
$toopherApi = new ToopherApi("<your consumer key>", "<your consumer secret>");

// Step 1 - Pair with their mobile device's Toopher app
$pairing = $toopherApi->pair("username@yourservice.com", "pairing phrase");
```

### Step 2: Authenticate
You have complete control over what actions you want to authenticate using Toopher (logging in, changing account information, making a purchase, etc.).  Just send us the username or pairing ID and we'll make sure they actually want it to happen. You can also choose to provide the following optional parameters: terminal name, requester specified ID and action name (*default: "Log in"*).

```php
// Step 2 - Authenticate a log in
$authRequest = $toopherApi->authenticate("username", "my computer");

// Once they've responded you can then check the status
$authRequest->refreshFromServer();
if ($authRequest->pending == false && $authRequest->granted == true) {
  // Success!
}
```

## ToopherIframe Workflow

### Step 1: Embed a request in an IFRAME
1. Generate an authentication URL by providing a username.
2. Display a webapage to your user that embeds this URL within an `<iframe>` element.

```php
require_once("toopher_api.php")

// Create an API object using your credentials
$iframeApi = new ToopherIframe("<your consumer key>", "<your consumer secret>");

$authIframeUrl = $iframeApi->getAuthenticationUrl("username@yourservice.com");

// Add an <iframe> element to your HTML:
// <iframe id="toopher-iframe" src=authIframeUrl />
```

### Step 2: Validate the postback data

The simplest way to validate the postback data is to call `isAuthenticationGranted` to check if the authentication request was granted.

```php
// Retrieve the postback data as a string from POST parameter 'iframe_postback_data'

// Returns boolean indicating if authentication request was granted by user
$authenticationRequestGranted = $iframeApi->isAuthenticationGranted(postback_data)

if ($authenticationRequestGranted) {
    // Success!
}
```

### Handling Errors
If any request runs into an error a `ToopherRequestException` will be thrown with more details on what went wrong.

### Demo
Check out `demo/toopher_demo.php` for an example program that walks you through the whole process!  Simply run the command below:
```shell
$ php demo/toopher_demo.php
```

## Contributing
### Dependencies
Toopher manages dependencies with [composer](http://getcomposer.org).  To ensure all dependencies are up-to-date run the command below from the root directory:
```shell
$ composer install
```

### Tests
To run the tests enter:
```shell
$ phpunit test
```
*Note: `phpunit` may be found in `vendor/bin/php` so your test command
would be:*
```shell
$ vendor/bin/phpunit test
```

## License
ToopherPHP is licensed under the MIT License. See LICENSE.txt for the full text.
