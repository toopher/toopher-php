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

class UserTests extends PHPUnit_Framework_TestCase {

	protected function setUp()
	{
		$this->mock = new HTTP_Request2_Adapter_Mock();
	}

	protected function getToopherApi($mock = NULL)
	{
		return new ToopherApi('key', 'secret', '', $mock);
	}

	protected function getUser($api)
	{
		return new User(["id" => "1", "name" => "user", "toopher_authentication_enabled" => true], $api);
	}

	public function testUser()
	{
		$user = $this->getUser($this->getToopherApi());
		$this->assertTrue($user->id == '1', 'User id was incorrect');
		$this->assertTrue($user->name == 'user', 'User name was incorrect');
		$this->assertTrue($user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}

	public function testUserRefreshFromServer()
	{
		$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/users/1');
		$resp->appendBody('{"id":"1","name":"user changed","toopher_authentication_enabled":true}');
		$this->mock->addResponse($resp);

		$toopher = $this->getToopherApi($this->mock);
		$user = $this->getUser($toopher);

		$user->refreshFromServer();
		$this->assertTrue($user->id == '1', 'bad user id');
		$this->assertTrue($user->name == 'user changed', 'bad user name');
		$this->assertTrue($user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}

	public function testUserEnableToopherAuthentication()
	{
		$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/users/1');
		$resp->appendBody('{"id":"1","name":"user","toopher_authentication_enabled":true}');
		$this->mock->addResponse($resp);

		$toopher = $this->getToopherApi($this->mock);
		$user = new User(["id" => "1", "name" => "user", "toopher_authentication_enabled" => false], $toopher);
		$this->assertTrue($user->toopher_authentication_enabled == false, 'User should not be toopher_authentication_enabled');

		$user->enableToopherAuthentication();
		$this->assertTrue($user->toopher_authentication_enabled == true, 'toopher authentication should be enabled');
		$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getBody() == "toopher_authentication_enabled=true", "Post params should include 'toopher_authentication_enabled=true'");
		$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
	}

	public function testUserDisableToopherAuthentication()
	{
		$resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/users/1');
		$resp1->appendBody('{"id":"1","name":"user","toopher_authentication_enabled":false}');
		$this->mock->addResponse($resp1);

		$toopher = $this->getToopherApi($this->mock);
		$user = $this->getUser($toopher);
		$this->assertTrue($user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');

		$user->disableToopherAuthentication();
		$this->assertTrue($user->toopher_authentication_enabled == false, 'toopher authentication should not be enabled');
		$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getBody() == "toopher_authentication_enabled=false", "Post params should include'toopher_authentication_enabled=false");
		$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
	}

	public function testUserUpdate()
	{
		$toopher = $this->getToopherApi($this->mock);
		$user = $this->getUser($toopher);
		$user->update(["id" => "1", "name" => "user changed", "toopher_authentication_enabled" => false]);
		$this->assertTrue($user->name == 'user changed', 'User name was incorrect');
		$this->assertTrue($user->toopher_authentication_enabled == false, 'User should not be toopher_authentication_enabled');
	}
}

?>
