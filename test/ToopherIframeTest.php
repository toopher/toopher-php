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

	protected function getAuthenticationRequestData()
	{
		return array(
			'id' => '1',
			'pending' => 'false',
			'granted' => 'true',
			'automated' => 'false',
			'reason_code' => '100',
			'reason' => 'it is a test',
			'terminal_id' => '1',
			'terminal_name' => 'terminal name',
			'terminal_requester_specified_id' => 'requester specified id',
			'pairing_user_id' => '1',
			'user_name' => 'user name',
			'user_toopher_authentication_enabled' => 'true',
			'action_id' => '1',
			'action_name' => 'action name',
			'toopher_sig' => 's+fYUtChrNMjES5Xa+755H7BQKE=',
			'session_token' => $this->getRequestToken(),
			'timestamp' => '1000',
			'resource_type' => 'authentication_request'
		);
	}

	protected function getPairingData()
	{
		return array(
			'id' => '1',
			'enabled' => 'true',
			'pending' => 'false',
			'pairing_user_id' => '1',
			'user_name' => 'user name',
			'user_toopher_authentication_enabled' => 'true',
			'toopher_sig' => 'ucwKhkPpN4VxNbx3dMypWzi4tBg=',
			'session_token' => $this->getRequestToken(),
			'timestamp' => '1000',
			'resource_type' => 'pairing'
		);
	}

	protected function getUserData()
	{
		return array(
			'id' => '1',
			'name' => 'user name',
			'toopher_authentication_enabled' => 'true',
			'toopher_sig' => 'RszgG9QE1rF9t7DVTGg+1I25yHM=',
	        'session_token' => $this->getRequestToken(),
	        'timestamp' => '1000',
	        'resource_type' => 'requester_user'
		);
	}

	protected function getUrlencodedData($data)
	{
		return array('toopher_iframe_data'=>utf8_encode(http_build_query($data)));
	}

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
		$this->toopherIframe->setTimestampOverride($this->getOauthTimestamp());
	}

	public function testGetAuthenticationUrlOnlyUsernameReturnsValidUrl()
	{
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/authenticate?username=jdoe&reset_email=&action_name=Log+In&session_token=&requester_metadata=&v=2&expires=1300&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=NkaWUjEPRLwgsQMEJGsIQEpyRT4%3D';
		$authenticationUrl = $this->toopherIframe->getAuthenticationUrl('jdoe');
		$this->assertTrue($authenticationUrl == $expectedUrl, 'Authentication url was incorrect');
	}

	public function testGetAuthenticationUrlWithOptionalArgsReturnsValidUrl()
	{
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/authenticate?username=jdoe&reset_email=jdoe%40example.com&action_name=it+is+a+test&session_token=s9s7vsb&requester_metadata=metadata&v=2&expires=1300&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=2TydgMnUwWoiwfpljKpSaFg0Luo%3D';
		$authenticationUrl = $this->toopherIframe->getAuthenticationUrl('jdoe', 'jdoe@example.com', $this->getRequestToken(), 'it is a test', 'metadata');
		$this->assertTrue($authenticationUrl == $expectedUrl, 'Authentication url was incorrect');
	}

	public function testGetAuthenticationUrlWithOptionalArgsAndExtrasReturnsValidUrl()
	{
		$extras = array(
			'allow_inline_pairing' => 'false',
			'automation_allowed' => 'false',
			'challenge_required' => 'true',
			'ttl' => '100'
		);
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/authenticate?username=jdoe&reset_email=jdoe%40example.com&action_name=it+is+a+test&session_token=s9s7vsb&requester_metadata=metadata&allow_inline_pairing=false&automation_allowed=false&challenge_required=true&v=2&expires=1100&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=61dqeQNPFxNy8PyEFB9e5UfgN8s%3D';
		$authenticationUrl = $this->toopherIframe->getAuthenticationUrl('jdoe', 'jdoe@example.com', $this->getRequestToken(), 'it is a test', 'metadata', $extras);
		$this->assertTrue($authenticationUrl == $expectedUrl, 'Authentication url was incorrect');
	}

	public function testToopherIframeGetUserManagementUrlReturnsValidUrl()
	{
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/manage_user?v=2&username=jdoe&reset_email=jdoe%40example.com&expires=1300&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=NjwH5yWPE2CCJL8v%2FMNknL%2BeTpE%3D';
		$userManagementUrl = $this->toopherIframe->getUserManagementUrl('jdoe', 'jdoe@example.com');
		$this->assertTrue($userManagementUrl == $expectedUrl, 'User management url was incorrect');
	}

	public function testToopherIframeGetUserManagementUrlWithExtrasReturnsValidUrl()
	{
		$extras = array('ttl' => '100');
		$this->toopherIframe->setNonceOverride($this->getOauthNonce());
		$expectedUrl = 'https://api.toopher.test/v1/web/manage_user?v=2&username=jdoe&reset_email=jdoe%40example.com&expires=1100&oauth_consumer_key=abcdefg&oauth_signature_method=HMAC-SHA1&oauth_version=1.0&oauth_nonce=12345678&oauth_timestamp=1000&oauth_signature=sV8qoKnxJ3fxfP6AHNa0eNFxzJs%3D';
		$userManagementUrl = $this->toopherIframe->getUserManagementUrl('jdoe', 'jdoe@example.com', $extras);
		$this->assertTrue($userManagementUrl == $expectedUrl, 'User management url was incorrect');
	}

	public function testProcessPostbackReturnsAuthenticationRequest()
	{
		$authData = $this->getUrlencodedData($this->getAuthenticationRequestData());
		$authRequest = $this->toopherIframe->processPostback($authData, $this->getRequestToken());
		$this->assertTrue($authRequest->id == '1', 'Authentication request id was incorrect');
		$this->assertTrue($authRequest->pending == false, 'Authentication request should not be pending');
		$this->assertTrue($authRequest->granted == true, 'Authentication request should be granted');
		$this->assertTrue($authRequest->automated == false, 'Authentication request should not be automated');
		$this->assertTrue($authRequest->reason_code == '100', 'Authentication request reason code was incorrect');
		$this->assertTrue($authRequest->reason == 'it is a test', 'Authentication request reason was incorrect');
		$this->assertTrue($authRequest->terminal->id == '1', 'Terminal id was incorrect');
		$this->assertTrue($authRequest->terminal->name == 'terminal name', 'Terminal name was incorrect');
		$this->assertTrue($authRequest->terminal->requester_specified_id == 'requester specified id', 'Terminal requester specified id was incorrect');
		$this->assertTrue($authRequest->user->id == '1', 'User id was incorrect');
		$this->assertTrue($authRequest->user->name == 'user name', 'User name was incorrect');
		$this->assertTrue($authRequest->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
		$this->assertTrue($authRequest->action->id == '1', 'Action id was incorrect');
		$this->assertTrue($authRequest->action->name == 'action name', 'Action name was incorrect');
	}

	public function testProcessPostbackReturnsPairing()
	{
		$pairingData = $this->getUrlencodedData($this->getPairingData());
		$pairing = $this->toopherIframe->processPostback($pairingData, $this->getRequestToken());
		$this->assertTrue($pairing->id == '1', 'Pairing id was incorrect');
		$this->assertTrue($pairing->enabled == true, 'Pairing should be enabled');
		$this->assertTrue($pairing->pending == false, 'Pairing should not be pending');
		$this->assertTrue($pairing->user->id == '1', 'User id was incorrect');
		$this->assertTrue($pairing->user->name == 'user name', 'User name was incorrect');
		$this->assertTrue($pairing->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}

	public function testProcessPostbackReturnsUser()
	{
		$userData = $this->getUrlencodedData($this->getUserData());
		$user = $this->toopherIframe->processPostback($userData, $this->getRequestToken());
		$this->assertTrue($user->id == '1', 'User id was incorrect');
		$this->assertTrue($user->name == 'user name', 'User name was incorrect');
		$this->assertTrue($user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}

	public function testProcessPostbackWithExtrasReturnsAuthenticationRequest()
	{
		$extras = array('ttl' => '5');
		$authRequest = $this->toopherIframe->processPostback($this->getUrlencodedData($this->getAuthenticationRequestData()), $this->getRequestToken(), $extras);
		$this->assertTrue(is_a($authRequest, 'AuthenticationRequest'), 'AuthenticationRequest should be returned');
	}

	public function testProcessPostbackWithoutRequestTokenReturnsAuthenticationRequest()
	{
		$authRequest = $this->toopherIframe->processPostback($this->getUrlencodedData($this->getAuthenticationRequestData()));
		$this->assertTrue(is_a($authRequest, 'AuthenticationRequest'), 'AuthenticationRequest should be returned');
	}

	/**
	* @expectedException         SignatureValidationError
	* @expectedExceptionMessage  Computed signature does not match
	*/
	public function testProcessPostbackWithBadSignatureRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		$authData['toopher_sig'] = 'invalid';
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException         SignatureValidationError
	* @expectedExceptionMessage  TTL Expired
	*/
	public function testProcessPostbackWithExpiredSignatureRaisesError()
	{
		$this->toopherIframe->setTimeStampOverride(mktime(0, 16, 40, 2, 1, 1970));
		$authData = $this->getAuthenticationRequestData();
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: toopher_sig
	*/
	public function testProcessPostbackMissingSignatureRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		unset($authData['toopher_sig']);
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: timestamp
	*/
	public function testProcessPostbackMissingTimestampRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		unset($authData['timestamp']);
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Missing required keys: session_token
	*/
	public function testProcessPostbackMissingSessionTokenRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		unset($authData['session_token']);
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException        SignatureValidationError
	* @expectedExceptionMessage Session token does not match expected value
	*/
	public function testProcessPostbackWithInvalidSessionTokenRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		$authData['session_token'] = 'invalid';
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException		ToopherRequestException
	* @expectedExceptionMessage	The postback resource type is not valid: invalid
	*/
	public function testProcessPostbackWithInvalidResourceTypeRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		$authData['resource_type'] = 'invalid';
		$authData['toopher_sig'] = 'xEY+oOtJcdMsmTLp6eOy9isO/xQ=';
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

	/**
	* @expectedException		ToopherRequestException
	* @expectedExceptionMessage	The specified user has disabled Toopher authentication
	*/
	public function testProcessPostbackWithErrorCodeRaisesError()
	{
		$authData = $this->getAuthenticationRequestData();
		$authData['error_code'] = '704';
		$authData['error_message'] = 'The specified user has disabled Toopher authentication';
		$this->toopherIframe->processPostback($this->getUrlencodedData($authData), $this->getRequestToken());
	}

}
