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
use Rhumsaa\Uuid\Uuid;

class ToopherIframeTests extends PHPUnit_Framework_TestCase {

	const OAUTH_NONCE = '12345678';
	const IFRAME_KEY = 'abcdefg';
	const IFRAME_SECRET = 'hijklmnop';
	const REQUEST_TOKEN = 's9s7vsb';

	public static function getOauthTimestamp()
	{
		date_default_timezone_set('UTC');
		return mktime(0, 16, 40, 1, 1, 1970);
	}

	public static function getOauthNonce()
	{
		return self::OAUTH_NONCE;
	}

	public static function getIframeKey()
	{
		return self::IFRAME_KEY;
	}

	public static function getIframeSecret()
	{
		return self::IFRAME_SECRET;
	}

	public static function getRequestToken()
	{
		return self::REQUEST_TOKEN;
	}

	protected function setUp()
	{
		$this->toopherIframe = new ToopherIframe($this->getIframeKey(), $this->getIframeSecret(), 'https://api.toopher.test/v1/');
	}

	public function testToopherIframeGetAuthenticationUrlReturnsValidUrl()
	{
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/authenticate?v=2&username=jdoe&reset_email=jdoe%40example.com&action_name=Log+In&session_token=s9s7vsb&requester_metadata=None&expires=1300&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=YN%2BkKNTaoypsB37fsjvMS8vsG5A%3D';
		$authenticationUrl = $this->toopherIframe->getAuthenticationUrl('jdoe', 'jdoe@example.com', $this->getRequestToken());
		$this->assertTrue($authenticationUrl == $expectedUrl, 'Authentication url was incorrect');
	}

	public function testToopherIframeGetAuthenticationUrlWithExtrasReturnsValidUrl()
	{
		$extras = array('allow_inline_pairing' => 'false');
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/authenticate?v=2&username=jdoe&reset_email=jdoe%40example.com&action_name=it+is+a+test&session_token=s9s7vsb&requester_metadata=None&expires=1300&allow_inline_pairing=false&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=W%2F2dcdsVc7YgdSCZuEo8ViHLlOo%3D';
		$authenticationUrl = $this->toopherIframe->getAuthenticationUrl('jdoe', 'jdoe@example.com', $this->getRequestToken(), 'it is a test', 'None', $extras);
		$this->assertTrue($authenticationUrl == $expectedUrl, 'Authentication url was incorrect');
	}

	public function testToopherIframeGetUserManagementUrlReturnsValidUrl()
	{
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/manage_user?v=2&username=jdoe&reset_email=jdoe%40example.com&expires=1300&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=NjwH5yWPE2CCJL8v%2FMNknL%2BeTpE%3D';
		$userManagementUrl = $this->toopherIframe->getUserManagementUrl('jdoe', 'jdoe@example.com');
		$this->assertTrue($userManagementUrl == $expectedUrl, 'User management url was incorrect');
	}

	public function testToopherIframeGetUserManagementUrlWithExtrasReturnsValidUrl()
	{
		$extras = array('ttl' => '100');
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/manage_user?v=2&username=jdoe&reset_email=jdoe%40example.com&expires=1100&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=sV8qoKnxJ3fxfP6AHNa0eNFxzJs%3D';
		$userManagementUrl = $this->toopherIframe->getUserManagementUrl('jdoe', 'jdoe@example.com', $extras);
		$this->assertTrue($userManagementUrl == $expectedUrl, 'User management url was incorrect');
	}

	public function testToopherIframeValidatePostbackWithGoodSignatureIsSuccessful()
	{
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'timestamp' => array($this->getOauthTimestamp()),
			'session_token' => array('s9s7vsb'),
			'toopher_sig' => array('6d2c7GlQssGmeYYGpcf+V/kirOI=')
		);
		try {
			$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
		} catch (Exception $e) {
			$this->fail('Valid signature, timestamp, and session token did not return validated data');
		}
	}

	/**
	* @expectedException         SignatureValidationError
	* @expectedExceptionMessage  Computed signature does not match
	*/
	public function testToopherIframeValidatePostbackWithBadSignatureFails()
	{
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'timestamp' => array(mktime(0, 16, 40, 1, 1, 1970)),
			'session_token' => array('s9s7vsb'),
			'toopher_sig' => array('invalid')
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}

	/**
	* @expectedException         SignatureValidationError
	* @expectedExceptionMessage  TTL Expired
	*/
	public function testToopherIframeValidatePostbackWithExpiredSignatureFails()
	{
		$this->toopherIframe->setTimeStampOverride(mktime(0, 16, 40, 2, 1, 1970));
		$data = array(
			'foo' => array('bar'),
			'timestamp' => array($this->getOauthTimestamp()),
			'session_token' => array('s9s7vsb'),
			'toopher_sig' => array('6d2c7GlQssGmeYYGpcf+V/kirOI=')
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Session token does not match expected value
	*/
	public function testToopherIframeValidatePostbackWithInvalidSessionTokenFails()
	{
		$this->toopherIframe->setTimeStampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'timestamp' => array(mktime(0, 16, 40, 1, 1, 1970)),
			'session_token' => array('invalid token'),
			'toopher_sig' => array('6d2c7GlQssGmeYYGpcf+V/kirOI=')
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: timestamp
	*/
	public function testToopherIframeValidatePostbackMissingTimestampFails()
	{
		$this->toopherIframe->setTimeStampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'session_token' => array('s9s7vsb'),
			'toopher_sig' => array('6d2c7GlQssGmeYYGpcf+V/kirOI=')
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: toopher_sig
	*/
	public function testToopherIframeValidatePostbackMissingSignatureFails()
	{
		$this->toopherIframe->setTimeStampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'session_token' => array('s9s7vsb'),
			'timestamp' => mktime(0, 16, 40, 1, 1, 1970)
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: session_token
	*/
	public function testToopherIframeValidatePostbackMissingSessionTokenFails()
	{
		$this->toopherIframe->setTimeStampOverride($this->getOauthTimestamp());
		$data = array(
			'foo' => array('bar'),
			'timestamp' => array($this->getOauthTimestamp()),
			'toopher_sig' => array('6d2c7GlQssGmeYYGpcf+V/kirOI=')
		);
		$this->toopherIframe->validatePostback($data, 's9s7vsb', 5);
	}
}
