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

class PairingTests extends PHPUnit_Framework_TestCase {

	protected function setUp()
	{
		$this->mock = new HTTP_Request2_Adapter_Mock();
	}

	protected function getToopherApi($mock = NULL)
	{
		return new ToopherApi('key', 'secret', '', $mock);
	}

	protected function getPairing($api)
	{
		return new Pairing(["id" => "1","enabled" => true, "pending" => false, "user" => ["id" => "1","name" => "user", "toopher_authentication_enabled" => "true"]], $api);
	}

	public function testPairing(){
		$pairing = $this->getPairing($this->getToopherApi());
		$this->assertTrue($pairing->id == '1', 'Pairing id was incorrect');
		$this->assertTrue($pairing->enabled == true, 'Pairing should be enabled');
		$this->assertTrue($pairing->pending == false, 'Pairing should not be pending');
		$this->assertTrue($pairing->user->id == '1', 'User id was incorrect');
		$this->assertTrue($pairing->user->name == 'user', 'User name was incorrect');
		$this->assertTrue($pairing->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}


	public function testPairingRefreshFromServer(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1');
			$resp->appendBody('{"id":"1","enabled":false,"pending":true,"user":{"id":"1","name":"user name changed", "toopher_authentication_enabled":false}}');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$pairing = $this->getPairing($toopher);

			$pairing->refreshFromServer();
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
			$this->assertTrue($pairing->enabled == false, 'Pairing should not be enabled');
			$this->assertTrue($pairing->pending == true, 'Pairing should be pending');
			$this->assertTrue($pairing->user->name == 'user name changed', 'User name was incorrect');
			$this->assertTrue($pairing->user->toopher_authentication_enabled == false, 'User should not be toopher_authentication_enabled');
	}

	public function testGetPairingResetLink(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1/generate_reset_link');
			$resp->appendBody('{"url":"http://api.toopher.test/v1/pairings/1/reset?reset_authorization=abcde"}');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$pairing = $this->getPairing($toopher);

			$resetLink = $pairing->getResetLink();
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
			$this->assertTrue($resetLink == "http://api.toopher.test/v1/pairings/1/reset?reset_authorization=abcde", 'Pairing reset link was incorrect');
	}

	public function testEmailPairingResetLink(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1/send_reset_link');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$pairing = $this->getPairing($toopher);

			try {
				$pairing->emailResetLink('jdoe@example.com');
				$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
			}
			catch(Exception $e) {
				$this->fail('Unexpected exception has been raised: ' . $e);
			}
	}

	public function testPairingGetQrCodeImage(){
			$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/qr/pairings/1');
			$resp->appendBody('{}');
			$this->mock->addResponse($resp);

			$toopher = $this->getToopherApi($this->mock);
			$pairing = $this->getPairing($toopher);

			$qr_image = $pairing->getQrCodeImage();
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
			$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getUrl() == 'https://api.toopher.com/v1/qr/pairings/1', "Last called url should be 'https://api.toopher.com/v1/qr/pairings/1'");
	}
}

?>
