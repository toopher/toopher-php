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

class AuthenticationRequestTests extends PHPUnit_Framework_TestCase {
	protected function setUp()
	{
		$this->mock = new HTTP_Request2_Adapter_Mock();
	}

	protected function getToopherApi($mock = NULL)
	{
		return new ToopherApi('key', 'secret', '', $mock);
	}

	protected function getAuthenticationRequest($api)
	{
		return new AuthenticationRequest(["id"=>"1","pending"=>true,"granted"=>false,"automated"=>false,"reason_code"=>"1","reason"=>"some reason","terminal"=>["id"=>"1","name"=>"term name","requester_specified_id"=>"1","user"=>["id"=>"1","name"=>"user","toopher_authentication_enabled"=>"true"]],"user"=>["id"=>"1","name"=>"user", "toopher_authentication_enabled"=>"true"],"action"=>["id"=>"1","name"=>"test"]], $api);
	}

	public function testAuthenticationRequest()
	{
		$authRequest = $this->getAuthenticationRequest($this->getToopherApi());
		$this->assertTrue($authRequest->id == '1', 'Authentication request id was incorrect');
		$this->assertTrue($authRequest->pending == true, 'Authentication request should be pending');
		$this->assertTrue($authRequest->granted == false, 'Authentication request should not be granted');
		$this->assertTrue($authRequest->automated == false, 'Authentication request should not be automated');
		$this->assertTrue($authRequest->reason_code == '1', 'Authentication request reason code was incorrect');
		$this->assertTrue($authRequest->reason == 'some reason', 'Authentication request reason was incorrect');
		$this->assertTrue($authRequest->terminal->id == '1', 'Terminal id was incorrect');
		$this->assertTrue($authRequest->terminal->name == 'term name', 'Terminal name was incorrect');
		$this->assertTrue($authRequest->terminal->requester_specified_id == '1', 'Terminal requester_specified_id was incorrect');
		$this->assertTrue($authRequest->user->id == '1', 'User id was incorrect');
		$this->assertTrue($authRequest->user->name == 'user', 'User name was incorrect');
		$this->assertTrue($authRequest->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
		$this->assertTrue($authRequest->action->id == '1', 'Action id was incorrect');
		$this->assertTrue($authRequest->action->name == 'test', 'Action name was incorrect');
	}

	public function testAuthenticationRequestRefreshFromServer(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/1');
			$resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some other reason","terminal":{"id":"1","name":"term name changed","requester_specified_id":"1","user":{"id":"1","name":"user changed", "toopher_authentication_enabled":"true"}},"user":{"id":"1","name":"user changed", "toopher_authentication_enabled":"true"},"action":{"id":"1","name":"test changed"}}');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$authRequest = $this->getAuthenticationRequest($toopher);

			$authRequest->refreshFromServer();
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
			$this->assertTrue($authRequest->pending == false, 'Authentication request should not be pending');
			$this->assertTrue($authRequest->granted == true, 'Authentication request should be granted');
			$this->assertTrue($authRequest->automated == true, 'Authentication request should be automated');
			$this->assertTrue($authRequest->reason == 'some other reason', 'Authentication request reason was incorrect');
			$this->assertTrue($authRequest->terminal->name == 'term name changed', 'Terminal name was incorrect');
			$this->assertTrue($authRequest->user->name == 'user changed', 'User name was incorrect');
			$this->assertTrue($authRequest->action->name == 'test changed', 'Action name was incorrect');
	}

	public function testGrantAuthenticationRequestWithOtp(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/1/otp_auth');
			$resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"},"action":{"id":"1","name":"test"}}');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$authRequest = $this->getAuthenticationRequest($toopher);

			$authRequest->grantWithOtp('otp');
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
			$this->assertTrue($authRequest->pending == false, 'wrong auth pending');
			$this->assertTrue($authRequest->granted == true, 'wrong auth granted');
			$this->assertTrue($authRequest->automated == true, 'wrong auth automated');
	}
}

?>
