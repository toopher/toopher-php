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

class ToopherApiTests extends PHPUnit_Framework_TestCase {

    protected function setUp()
    {
        $this->mock = new HTTP_Request2_Adapter_Mock();
    }

    protected function getToopherApi($mock = NULL)
    {
        return new ToopherApi('key', 'secret', '', $mock);
    }

    public function compareToDefaultPairing($pairing)
    {
        $this->assertTrue($pairing->id == '1', 'Pairing id was incorrect');
        $this->assertTrue($pairing->enabled == true, 'Pairing should be enabled');
        $this->assertTrue($pairing->pending == false, 'Pairing should not be pending');
        $this->assertTrue($pairing->user->id == '1', 'User id was incorrect');
        $this->assertTrue($pairing->user->name == 'user', 'User name was wrong');
        $this->assertTrue($pairing->user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
    }

    public function compareToDefaultAuthenticationRequest($authRequest, $id = '1')
    {
        $this->assertTrue($authRequest->id == $id, 'Authentiation request id was incorrect');
        $this->assertTrue($authRequest->pending == false, 'Authentication request should not be pending');
        $this->assertTrue($authRequest->granted == true, 'Authentication request should be granted');
        $this->assertTrue($authRequest->automated == true, 'Authentiation request should be automated');
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

    public function compareToDefaultUser($user)
    {
        $this->assertTrue($user->id == '1', 'User id was incorrect');
        $this->assertTrue($user->name == 'user', 'User name was incorrect');
        $this->assertTrue($user->toopher_authentication_enabled == true, 'User should be toopher_authentication_enabled');
    }

    public function compareToDefaultUserTerminal($userTerminal)
    {
        $this->assertTrue($userTerminal->id == '1', 'wrong terminal id');
        $this->assertTrue($userTerminal->name == 'terminal name', 'wrong terminal name');
        $this->assertTrue($userTerminal->requester_specified_id == 'requester specified id', 'wrong requester specified id');
        $this->assertTrue($userTerminal->user->id == '1', 'bad user id');
        $this->assertTrue($userTerminal->user->name == 'user name', 'bad user name');
        $this->assertTrue($userTerminal->user->toopher_authentication_enabled == true, 'toopher authentication should be enabled');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toopher consumer key cannot be empty
     */
    public function testEmptyKeyThrowsException()
    {
        $toopher = new ToopherApi('', 'secret');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Toopher consumer secret cannot be empty
     */
    public function testEmptySecretThrowsException()
    {
        $toopher = new ToopherApi('key', '');
    }

    public function testCanCreateToopherApiWithArguments()
    {
        $toopher = new ToopherApi('key', 'secret');
    }

    public function testToopherVersionStringExists()
    {
        $this->assertNotEmpty(ToopherApi::VERSION, 'no version string');
        list($major, $minor, $patch) = explode('.', ToopherApi::VERSION);
        $this->assertGreaterThanOrEqual(1, (int)$major);
        $this->assertGreaterThanOrEqual(0, (int)$minor);
        $this->assertGreaterThanOrEqual(0, (int)$patch);
    }

    public function testPairReturnsPairing()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/pairings/create');
        $resp->appendBody('{"id":"1","enabled":true,"pending":false,"user":{"id":"1","name":"user", "toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $pairing = $toopher->pair('user', 'immediate_pair');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultPairing($pairing);
    }

    public function testPairSmsReturnsPairing()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/pairings/create/sms');
        $resp->appendBody('{"id":"1", "enabled":true, "pending":false, "user":{"id":"1", "name":"user", "toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $pairing = $toopher->pair('user', '555-555-5555');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultPairing($pairing);
    }

    public function testPairQrReturnsPairing()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/pairings/create/qr');
        $resp->appendBody('{"id":"1", "enabled":true, "pending":false, "user":{"id":"1", "name":"user", "toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $pairing = $toopher->pair('user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultPairing($pairing);
    }

    public function testAuthenticateWithPairingIdReturnsAuthenticationRequest()
    {
        $id = Uuid::uuid4()->toString();
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp->appendBody('{"id":"' . $id . '","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->authenticate($id);
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultAuthenticationRequest($authRequest, $id);
    }

    public function testAuthenticateWithPairingIdOptionalArgsAndExtrasReturnsAuthenticationRequest()
    {
        $extras = array('foo' => 'bar');
        $id = Uuid::uuid4()->toString();
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp->appendBody('{"id":"' . $id . '","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->authenticate($id, 'term name', '1', 'it is a test', $extras);
        $parameters = $toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getParameters();
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->assertTrue($parameters['pairing_id'] == $id, sprintf("Last called parameters should include key-value pair: 'pairing_id'=> %s", $id));
        $this->assertTrue($parameters['action_name'] == 'it is a test', "Last called parameters should include key-value pair: 'action_name'=>'it is a test'");
        $this->assertTrue($parameters['terminal_name'] == 'term name', "Last called parameters should include key-value pair: 'terminal_name'=>'term name'");
        $this->assertTrue($parameters['requester_specified_terminal_id'] == '1', "Last called parameters should include key-value pair: 'requester_specified_terminal_id'=>'1'");
        $this->assertTrue($parameters['foo'] == 'bar', "Last called parameters should include key-value pair: 'foo'=>'bar'");
        $this->compareToDefaultAuthenticationRequest($authRequest, $id);
    }

    public function testAuthenticateWithUsernameReturnsAuthenticationRequest()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->authenticate('user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultAuthenticationRequest($authRequest);
    }

    public function testAuthentiateWithUsernameOptionalArgsAndExtrasReturnsAuthenticationRequest()
    {
        $extras = array('foo' => 'bar');
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->authenticate('user', 'term name', '1', 'it is a test', $extras);
        $parameters = $toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getParameters();
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->assertTrue($parameters['user_name'] == 'user', "Last called parameters should include key-value pair: 'user_name'=>'user'");
        $this->assertTrue($parameters['action_name'] == 'it is a test', "Last called parameters should include key-value pair: 'action_name'=>'it is a test'");
        $this->assertTrue($parameters['terminal_name'] == 'term name', "Last called parameters should include key-value pair: 'terminal_name'=>'term name'");
        $this->assertTrue($parameters['requester_specified_terminal_id'] == '1', "Last called parameters should include key-value pair: 'requester_specified_terminal_id'=>'1'");
        $this->assertTrue($parameters['foo'] == 'bar', "Last called parameters should include key-value pair: 'foo'=>'bar'");
        $this->compareToDefaultAuthenticationRequest($authRequest);
    }

    public function testRawPost()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $params = array('pairing_id' => '1', 'terminal_name' => 'term name');
        $authRequest = $toopher->advanced->raw->post('authentication_requests/initiate', $params);
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->assertTrue($authRequest['id'] == '1', 'Authentication request id was incorrect');
        $this->assertTrue($authRequest['pending'] == false, 'Authentication request should not be pending');
        $this->assertTrue($authRequest['granted'] == true, 'Authentication request should be granted');
        $this->assertTrue($authRequest['automated'] == true, 'Authentication request should be automated');
        $this->assertTrue($authRequest['reason_code'] == '1', 'Authentication request reason code was incorrect');
        $this->assertTrue($authRequest['reason'] == 'some reason', 'Authentication request reason was incorrect');
        $this->assertTrue($authRequest['terminal'] == array('id'=>'1', 'name'=>'term name', 'requester_specified_id'=>'1', 'user'=>array('id'=>'1', 'name'=>'user', 'toopher_authentication_enabled'=>true)), 'Terminal data was incorrect');
        $this->assertTrue($authRequest['user'] == array('id'=>'1', 'name'=>'user', 'toopher_authentication_enabled'=>true), 'User data was incorrect');
        $this->assertTrue($authRequest['action'] == array('id'=>'1', 'name'=>'test'), 'Action data was incorrect');
    }

    public function testRawGet()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->advanced->raw->get('authentication_requests/1');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->assertTrue($authRequest['id'] == '1', 'Authentication request id was incorrect');
        $this->assertTrue($authRequest['pending'] == false, 'Authentication request should not be pending');
        $this->assertTrue($authRequest['granted'] == true, 'Authentication request should be granted');
        $this->assertTrue($authRequest['automated'] == true, 'Authentication request should be automated');
        $this->assertTrue($authRequest['reason_code'] == '1', 'Authentication request reason code was incorrect');
        $this->assertTrue($authRequest['reason'] == 'some reason', 'Authentication request reason was incorrect');
        $this->assertTrue($authRequest['terminal'] == array('id'=>'1', 'name'=>'term name', 'requester_specified_id'=>'1', 'user'=>array('id'=>'1', 'name'=>'user', 'toopher_authentication_enabled'=>true)), 'Terminal data was incorrect');
        $this->assertTrue($authRequest['user'] == array('id'=>'1', 'name'=>'user', 'toopher_authentication_enabled'=>true), 'User data was incorrect');
        $this->assertTrue($authRequest['action'] == array('id'=>'1', 'name'=>'test'), 'Action data was incorrect');
    }

    public function testPairingsGetByIdReturnsPairing()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/pairings/1');
        $resp->appendBody('{"id":"1","enabled":true, "pending":false, "user":{"id":"1","name":"user", "toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $pairing = $toopher->advanced->pairings->getById('1');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultPairing($pairing);
    }

    public function testAuthenticationRequestsGetByIdReturnsAuthenticationRequest()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason_code":"1","reason":"some reason","terminal":{"id":"1","name":"term name","requester_specified_id":"1","user":{"id":"1","name":"user", "toopher_authentication_enabled":true}},"user":{"id":"1","name":"user", "toopher_authentication_enabled":true},"action":{"id":"1","name":"test"}}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $authRequest = $toopher->advanced->authenticationRequests->getById('1');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultAuthenticationRequest($authRequest);
    }

    public function testUsersGetByIdReturnsUser()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users/1');
        $resp->appendBody('{"id":"1","name":"user","toopher_authentication_enabled":true}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->getById('1');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultUser($user);
    }

    public function testUsersGetByNameReturnsUser()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users');
        $resp->appendBody('[{"id":"1","name":"user","toopher_authentication_enabled":true}]');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->getByName('user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultUser($user);
    }

    /**
    * @expectedException ToopherRequestException
    * @expectedExceptionMessage Multiple users with name
    */
    public function testUsersGetByNameWithMultipleUsersRaisesToopherRequestException()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users');
        $resp->appendBody('[{"id":"1","name":"user","toopher_authentication_enabled":true}, {"id":"2","name":"user","toopher_authentication_enabled":true}]');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->getByName('user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultUser($user);
    }

    /**
    * @expectedException ToopherRequestException
    * @expectedExceptionMessage No users with name
    */
    public function testUsersGetByNameWithNoUsersRaisesToopherRequestException()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users');
        $resp->appendBody('[]');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->getByName('user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultUser($user);
    }

    public function testUsersCreateReturnsUser()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users/create');
        $resp->appendBody('{"id":"1","name":"user","toopher_authentication_enabled":true}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->create('paired user');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultUser($user);
    }

    public function testUsersCreateWithExtrasReturnsUser()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/users/create');
        $resp->appendBody('{"id":"1","name":"user","toopher_authentication_enabled":true}');
        $this->mock->addResponse($resp);

        $toopher = $this->getToopherApi($this->mock);
        $user = $toopher->advanced->users->create('paired user', array('foo'=>'bar'));
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultUser($user);
    }

    public function testUserTerminalsGetByIdReturnsUserTerminal()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/user_terminals/1');
        $resp->appendBody('{"id":"1", "name":"terminal name", "requester_specified_id": "requester specified id", "user":{"id":"1","name":"user name","toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);

        $toopher = new ToopherApi('key', 'secret', '', $this->mock);
        $userTerminal = $toopher->advanced->userTerminals->getById('1');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'GET', "Last called method should be 'GET'");
        $this->compareToDefaultUserTerminal($userTerminal);
    }

    public function testUserTerminalCreateReturnsUserTerminal()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/user_terminals/create');
        $resp->appendBody('{"id":"1", "name":"terminal name", "requester_specified_id": "requester specified id", "user":{"id":"1","name":"user name","toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);

        $toopher = new ToopherApi('key', 'secret', '', $this->mock);
        $userTerminal = $toopher->advanced->userTerminals->create('name', 'terminal one', 'requester specified id');
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultUserTerminal($userTerminal);
    }

    public function testUserTerminalCreateWithExtrasReturnsUserTerminal()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 200 OK', false, 'https://api.toopher.com/v1/user_terminals/create');
        $resp->appendBody('{"id":"1", "name":"terminal name", "requester_specified_id": "requester specified id", "user":{"id":"1","name":"user name","toopher_authentication_enabled":true}}');
        $this->mock->addResponse($resp);

        $toopher = new ToopherApi('key', 'secret', '', $this->mock);
        $userTerminal = $toopher->advanced->userTerminals->create('name', 'terminal one', 'requester specified id', array('foo'=>'bar'));
        $this->assertTrue($toopher->advanced->raw->getOauthConsumer()->getLastRequest()->getMethod() == 'POST', "Last called method should be 'POST'");
        $this->compareToDefaultUserTerminal($userTerminal);
    }

    /**
     * @expectedException ToopherRequestException
     * @expectedExceptionMessage Not a valid OAuth signed request
     */
    public function test401RaisesToopherRequestEception()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 401 Unauthorized', false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp->appendBody('{"error_code":401, "error_message":"Not a valid OAuth signed request"}');
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }

    /**
     * @expectedException ToopherRequestException
     */
    public function test403WithEmptyBodyRaisesToopherRequestException()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 403 Forbidden', false, 'https://api.toopher.com/v1/authentication_requests/1');
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }

    /**
     * @expectedException ToopherRequestException
     */
    public function test403WithUnprintableBodyRaisesToopherRequestException()
    {
        $resp = new HTTP_Request2_Response('HTTP/1.1 403 Forbidden', false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp->appendBody(sprintf("{'error_code':403, 'error_message':'%c'}", chr(5)));
        $this->mock->addResponse($resp);
        $toopher = $this->getToopherApi($this->mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }
}

?>
