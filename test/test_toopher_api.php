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

require_once("bootstrap.php");
use Rhumsaa\Uuid\Uuid;

class ToopherAPITests extends PHPUnit_Framework_TestCase {

    protected $oauthParams = array('oauth_nonce' => 'nonce', 'oauth_timestamp' => '0');

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyKeyThrowsException() {
        $toopher = new ToopherAPI('', 'secret');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptySecretThrowsException() {
        $toopher = new ToopherAPI('key', '');
    }

    public function testCanCreateToopherApiWithArguments() {
        $toopher = new ToopherAPI('key', 'secret');
    }

    public function testCreatePair(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create');
        $resp->appendBody('{"id":"1","enabled":true,"pending":false,"user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"}}');
        $mock->addResponse($resp);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('user', 'immediate_pair');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');
    }

    public function testCreateSmsPair(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create/sms');
        $resp->appendBody('{"id":"1", "enabled":true, "pending":false, "user":{"id":"1", "name":"user", "toopher_authentication_enabled":"true"}}');
        $mock->addResponse($resp);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('user', '555-555-5555');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');
    }

    public function testCreateQrPair(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create/qr');
        $resp->appendBody('{"id":"1", "enabled":true, "pending":false, "user":{"id":"1", "name":"user", "toopher_authentication_enabled":"true"}}');
        $mock->addResponse($resp);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('user');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');
    }

    public function testGetPairingStatus(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1');
        $resp1->appendBody('{"id":"1","enabled":true, "pending":false, "user":{"id":"1","name":"paired user", "toopher_authentication_enabled":"true"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1');
        $resp2->appendBody('{"id":"2","enabled":false, "pending":false, "user":{"id":"2","name":"unpaired user", "toopher_authentication_enabled":"true"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);

        $pairing = $toopher->advanced->pairings->getById('1');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'paired user', 'bad user name');

        $pairing = $toopher->advanced->pairings->getById('2');
        $this->assertTrue($pairing->id == '2', 'bad pairing id');
        $this->assertTrue($pairing->enabled == false, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '2', 'bad user id');
        $this->assertTrue($pairing->user->name == 'unpaired user', 'bad user name');
    }

    public function testPairingRefreshFromServer(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create');
        $resp1->appendBody('{"id":"1","enabled":false, "pending":false, "user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1');
        $resp2->appendBody('{"id":"1","enabled":true,"pending":false,"user":{"id":"1","name":"user name changed", "toopher_authentication_enabled":"true"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);

        $pairing = $toopher->pair('user', 'pairing phrase');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == false, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');

        $pairing->refreshFromServer();
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user name changed', 'bad user name');
    }

    public function testGetPairingResetLink(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create');
        $resp1->appendBody('{"id":"1","enabled":true, "pending":false, "user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1/generate_reset_link');
        $resp2->appendBody('{"url":"http://api.toopher.test/v1/pairings/1/reset?reset_authorization=abcde"}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('user', 'immediate_pair');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');

        $resetLink = $pairing->getResetLink();
        $this->assertTrue($resetLink == "http://api.toopher.test/v1/pairings/1/reset?reset_authorization=abcde");
    }

    public function testEmailPairingResetLink(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/create');
        $resp1->appendBody('{"id":"1","enabled":true, "pending":false, "user":{"id":"1","name":"user", "toopher_authentication_enabled":"true"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/pairings/1/send_reset_link');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('user', 'immediate_pair');
        $this->assertTrue($pairing->id == '1', 'bad pairing id');
        $this->assertTrue($pairing->enabled == true, 'pairing not enabled');
        $this->assertTrue($pairing->user->id == '1', 'bad user id');
        $this->assertTrue($pairing->user->name == 'user', 'bad user name');

        try {
            $pairing->emailResetLink('email@domain.com');
        }
        catch(Exception $e) {
            $this->fail('Unexpected exception has been raised: ' . $e);
        }
    }

    public function testCreateAuthenticationWithNoAction(){
        $id = Uuid::uuid4()->toString();
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp1->appendBody('{"id":"' . $id . '","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $mock->addResponse($resp1);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth_request = $toopher->authenticate($id, 'term name');
        $this->assertTrue($auth_request->id == $id, 'wrong auth id');
        $this->assertTrue($auth_request->pending == false, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == true, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == true, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name', 'wrong auth terminal name');
    }

    public function testGetAuthenticationStatus(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp1->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/2');
        $resp2->appendBody('{"id":"2","pending":true,"granted":false,"automated":false,"reason":"some other reason","terminal":{"id":"2","name":"another term name"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth_request = $toopher->advanced->authenticationRequests->getById('1');
        $this->assertTrue($auth_request->id == '1', 'wrong auth id');
        $this->assertTrue($auth_request->pending == false, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == true, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == true, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name', 'wrong auth terminal name');

        $auth_request = $toopher->advanced->authenticationRequests->getById('2');
        $this->assertTrue($auth_request->id == '2', 'wrong auth id');
        $this->assertTrue($auth_request->pending == true, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == false, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == false, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some other reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '2', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'another term name', 'wrong auth terminal name');
    }

    public function testAuthenticationRequestRefreshFromServer(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp1->appendBody('{"id":"1","pending":true,"granted":false,"automated":false,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp2->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason":"some other reason","terminal":{"id":"1","name":"term name changed"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth_request = $toopher->authenticate('user', 'term name extra');
        $this->assertTrue($auth_request->id == '1', 'wrong auth id');
        $this->assertTrue($auth_request->pending == true, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == false, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == false, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name', 'wrong auth terminal name');

        $auth_request->refreshFromServer();
        $this->assertTrue($auth_request->id == '1', 'wrong auth id');
        $this->assertTrue($auth_request->pending == false, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == true, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == true, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some other reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name changed', 'wrong auth terminal name');
    }

    public function testGrantAuthenticationRequestWithOtp(){
        $id = Uuid::uuid4()->toString();
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp1->appendBody('{"id":"' . $id . '","pending":true,"granted":false,"automated":false,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp2->appendBody('{"id":"' . $id . '","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth_request = $toopher->authenticate($id, 'term name');
        $this->assertTrue($auth_request->id == $id, 'wrong auth id');
        $this->assertTrue($auth_request->pending == true, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == false, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == false, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name', 'wrong auth terminal name');

        $auth_request->grant_with_otp('otp');
        $this->assertTrue($auth_request->id == $id, 'wrong auth id');
        $this->assertTrue($auth_request->pending == false, 'wrong auth pending');
        $this->assertTrue($auth_request->granted == true, 'wrong auth granted');
        $this->assertTrue($auth_request->automated == true, 'wrong auth automated');
        $this->assertTrue($auth_request->reason == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request->terminalId == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request->terminalName == 'term name', 'wrong auth terminal name');
    }

    public function testRawPost(){
        $id = Uuid::uuid4()->toString();
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/initiate');
        $resp1->appendBody('{"id":"' . $id . '","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $mock->addResponse($resp1);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $params = array('pairing_id' => $id, 'terminal_name' => 'term name');
        $auth_request = $toopher->advanced->raw->post('authentication_requests/initiate', $params);
        $this->assertTrue($auth_request['id'] == $id, 'wrong auth id');
        $this->assertTrue($auth_request['pending'] == false, 'wrong auth pending');
        $this->assertTrue($auth_request['granted'] == true, 'wrong auth granted');
        $this->assertTrue($auth_request['automated'] == true, 'wrong auth automated');
        $this->assertTrue($auth_request['reason'] == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request['terminal']['id'] == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request['terminal']['name'] == 'term name', 'wrong auth terminal name');
    }


    public function testRawGet(){
        $id1 = Uuid::uuid4()->toString();
        $id2 = Uuid::uuid4()->toString();
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/' . $id1);
        $resp1->appendBody('{"id":"' . $id1 . '","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://api.toopher.com/v1/authentication_requests/' . $id2);
        $resp2->appendBody('{"id":"' . $id2 . '","pending":true,"granted":false,"automated":false,"reason":"some other reason","terminal":{"id":"2","name":"another term name"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth_request = $toopher->advanced->raw->get('authentication_requests/' . $id1);
        $this->assertTrue($auth_request['id'] == $id1, 'wrong auth id');
        $this->assertTrue($auth_request['pending'] == false, 'wrong auth pending');
        $this->assertTrue($auth_request['granted'] == true, 'wrong auth granted');
        $this->assertTrue($auth_request['automated'] == true, 'wrong auth automated');
        $this->assertTrue($auth_request['reason'] == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth_request['terminal']['id'] == '1', 'wrong auth terminal id');
        $this->assertTrue($auth_request['terminal']['name'] == 'term name', 'wrong auth terminal name');

        $auth_request = $toopher->advanced->raw->get('authentication_requests/' . $id2);
        $this->assertTrue($auth_request['id'] == $id2, 'wrong auth id');
        $this->assertTrue($auth_request['pending'] == true, 'wrong auth pending');
        $this->assertTrue($auth_request['granted'] == false, 'wrong auth granted');
        $this->assertTrue($auth_request['automated'] == false, 'wrong auth automated');
        $this->assertTrue($auth_request['reason'] == 'some other reason', 'wrong auth reason');
        $this->assertTrue($auth_request['terminal']['id'] == '2', 'wrong auth terminal id');
        $this->assertTrue($auth_request['terminal']['name'] == 'another term name', 'wrong auth terminal name');
    }

    public function testUser(){
      $toopher = new ToopherAPI('key', 'secret');
      $user = new User(["id" => "1", "name" => "user", "toopher_authentication_enabled" => true], $toopher);
      $this->assertTrue($user->id == '1', 'bad user id');
      $this->assertTrue($user->name == 'user', 'bad user name');
      $this->assertTrue($user->toopher_authentication_enabled == true, 'toopher authentication not enabled');
    }

    public function testUserTerminal(){
      $toopher = new ToopherAPI('key', 'secret');
      $user_terminal = new UserTerminal(["id" => "1", "name" => "user", "requester_specified_id" => "1", "user" => ["id" => "1","name" => "user", "toopher_authentication_enabled" => true]], $toopher);
      $this->assertTrue($user_terminal->id == '1', 'bad user terminal id');
      $this->assertTrue($user_terminal->name == 'user', 'bad user terminal name');
      $this->assertTrue($user_terminal->requester_specified_id == '1', 'bad user terminal requester specified is');
      $this->assertTrue($user_terminal->user->id == '1', 'bad user id');
      $this->assertTrue($user_terminal->user->name == 'user', 'bad user name');
      $this->assertTrue($user_terminal->user->toopher_authentication_enabled == true, 'toopher authentication not enabled');
    }


    /**
     * @expectedException ToopherRequestException
     */
    public function testToopherRequestException(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 401 Unauthorized", false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp1->appendBody('{"error_code":401, "error_message":"Not a valid OAuth signed request"}');
        $mock->addResponse($resp1);


        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }

    public function testToopherVersionStringExists() {
        $this->assertNotEmpty(ToopherAPI::VERSION, 'no version string');
        list($major, $minor, $patch) = explode('.', ToopherAPI::VERSION);
        $this->assertGreaterThanOrEqual(1, (int)$major);
        $this->assertGreaterThanOrEqual(0, (int)$minor);
        $this->assertGreaterThanOrEqual(0, (int)$patch);
    }

    /**
     * @expectedException ToopherRequestException
     */
    public function test400WithEmptyBodyRaisesToopherRequestException(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 403 Forbidden", false, 'https://api.toopher.com/v1/authentication_requests/1');
        $mock->addResponse($resp1);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }

    /**
     * @expectedException ToopherRequestException
     */
    public function test400WithUnprintableBodyRaisesToopherRequestException(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 403 Forbidden", false, 'https://api.toopher.com/v1/authentication_requests/1');
        $resp1->appendBody(sprintf('{"error_code":403, "error_message":"%c"}', chr(5)));
        $mock->addResponse($resp1);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->advanced->authenticationRequests->getById('1');
    }
}

?>
