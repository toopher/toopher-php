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

class UserTerminalTests extends PHPUnit_Framework_TestCase {
	protected function setUp()
	{
		$this->mock = new HTTP_Request2_Adapter_Mock();
	}

	protected function getToopherApi($mock = NULL)
	{
		return new ToopherApi('key', 'secret', '', $mock);
	}

	protected function getUserTerminal($api)
	{
		return new UserTerminal(["id" => "1", "name" => "terminal name", "requester_specified_id" => "requester specified id", "user" => ["id" => "1","name" => "user name", "toopher_authentication_enabled" => true]], $api);
	}

	public function testUserTerminal(){
		$userTerminal = $this->getUserTerminal($this->getToopherApi());
		$this->assertTrue($userTerminal->id == '1', 'Terminal id was incorrect');
		$this->assertTrue($userTerminal->name == 'terminal name', 'Terminal name was incorrect');
		$this->assertTrue($userTerminal->requester_specified_id == 'requester specified id', 'Terminal requester_specified_id was incorrect');
		$this->assertTrue($userTerminal->user->id == '1', 'User id was incorrect');
		$this->assertTrue($userTerminal->user->name == 'user name', 'User name was incorrect');
		$this->assertTrue($userTerminal->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
	}

	public function testUserTerminalRefreshFromServer(){
		$resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/user_terminals/1');
		$resp->appendBody('{"id":"1", "name":"terminal name changed", "requester_specified_id":"requester specified id changed", "user":{"id":"1", "name":"user name changed", "toopher_authentication_enabled":false}}');
		$this->mock->addResponse($resp);

		$toopher = $this->getToopherApi($this->mock);
		$userTerminal = $this->getUserTerminal($toopher);

		$userTerminal->refreshFromServer();
		$this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
		$this->assertTrue($userTerminal->name == 'terminal name changed', 'Terminal name was wrong');
		$this->assertTrue($userTerminal->requester_specified_id == 'requester specified id changed', 'Terminal requester_specified_id was incorrect');
		$this->assertTrue($userTerminal->user->name == 'user name changed', 'User name was incorrect');
		$this->assertTrue($userTerminal->user->toopher_authentication_enabled == false, 'User should not be toopher_authentication_enabled');
	}

	public function testUserTerminalUpdate(){
		$userTerminal = $this->getUserTerminal($this->getToopherApi());
		$userTerminal->update(["id"=>"1", "name"=>"terminal name changed", "requester_specified_id"=>"requester specified id changed", "user"=>["id"=>"1", "name"=>"user name changed", "toopher_authentication_enabled"=>false]]);
		$this->assertTrue($userTerminal->name == 'terminal name changed', 'Terminal name was wrong');
		$this->assertTrue($userTerminal->requester_specified_id == 'requester specified id changed', 'Terminal requester_specified_id was incorrect');
		$this->assertTrue($userTerminal->user->name == 'user name changed', 'User name was incorrect');
		$this->assertTrue($userTerminal->user->toopher_authentication_enabled == false, 'User should not be toopher_authentication_enabled');
	}
}

?>
