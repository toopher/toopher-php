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

class ToopherAPITests extends PHPUnit_Framework_TestCase {

    protected $oauthParams = ['oauth_nonce' => 'nonce', 'oauth_timestamp' => '0'];

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
        $resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/pairings/create');
        $resp->appendBody('{"id":"1","enabled":true,"user":{"id":"1","name":"user"}}');
        $mock->addResponse($resp);
        $toopher = new ToopherAPI('key', 'secret', '', $mock, $this->oauthParams);
        $pairing = $toopher->pair('immediate_pair', 'user');
        $this->assertTrue($pairing['id'] == '1', 'bad pairing id');
        $this->assertTrue($pairing['enabled'] == true, 'pairing not enabled');
        $this->assertTrue($pairing['userId'] == '1', 'bad user id');
        $this->assertTrue($pairing['userName'] == 'user', 'bad user name');
    }

    public function testGetPairingStatus(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/pairings/1');
        $resp1->appendBody('{"id":"1","enabled":true,"user":{"id":"1","name":"paired user"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/pairings/1');
        $resp2->appendBody('{"id":"2","enabled":false,"user":{"id":"2","name":"unpaired user"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        
        $pairing = $toopher->getPairingStatus('1');
        $this->assertTrue($pairing['id'] == '1', 'bad pairing id');
        $this->assertTrue($pairing['enabled'] == true, 'pairing not enabled');
        $this->assertTrue($pairing['userId'] == '1', 'bad user id');
        $this->assertTrue($pairing['userName'] == 'paired user', 'bad user name');

        $pairing = $toopher->getPairingStatus('2');
        $this->assertTrue($pairing['id'] == '2', 'bad pairing id');
        $this->assertTrue($pairing['enabled'] == false, 'pairing not enabled');
        $this->assertTrue($pairing['userId'] == '2', 'bad user id');
        $this->assertTrue($pairing['userName'] == 'unpaired user', 'bad user name');
    }

    public function testCreateAuthenticationWithNoAction(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/authentication_requests/initiate');
        $resp1->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $mock->addResponse($resp1);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->authenticate('1', 'term name');
        $this->assertTrue($auth['id'] == '1', 'wrong auth id');
        $this->assertTrue($auth['pending'] == false, 'wrong auth pending');
        $this->assertTrue($auth['granted'] == true, 'wrong auth granted');
        $this->assertTrue($auth['automated'] == true, 'wrong auth automated');
        $this->assertTrue($auth['reason'] == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth['terminalId'] == '1', 'wrong auth terminal id');
        $this->assertTrue($auth['terminalName'] == 'term name', 'wrong auth terminal name');
    }

    public function testGetAuthenticationStatus(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/authentication_requests/1');
        $resp1->appendBody('{"id":"1","pending":false,"granted":true,"automated":true,"reason":"some reason","terminal":{"id":"1","name":"term name"}}');
        $resp2 = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/authentication_requests/2');
        $resp2->appendBody('{"id":"2","pending":true,"granted":false,"automated":false,"reason":"some other reason","terminal":{"id":"2","name":"another term name"}}');
        $mock->addResponse($resp1);
        $mock->addResponse($resp2);

        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->getAuthenticationStatus('1');
        $this->assertTrue($auth['id'] == '1', 'wrong auth id');
        $this->assertTrue($auth['pending'] == false, 'wrong auth pending');
        $this->assertTrue($auth['granted'] == true, 'wrong auth granted');
        $this->assertTrue($auth['automated'] == true, 'wrong auth automated');
        $this->assertTrue($auth['reason'] == 'some reason', 'wrong auth reason');
        $this->assertTrue($auth['terminalId'] == '1', 'wrong auth terminal id');
        $this->assertTrue($auth['terminalName'] == 'term name', 'wrong auth terminal name');

        $auth = $toopher->getAuthenticationStatus('2');
        $this->assertTrue($auth['id'] == '2', 'wrong auth id');
        $this->assertTrue($auth['pending'] == true, 'wrong auth pending');
        $this->assertTrue($auth['granted'] == false, 'wrong auth granted');
        $this->assertTrue($auth['automated'] == false, 'wrong auth automated');
        $this->assertTrue($auth['reason'] == 'some other reason', 'wrong auth reason');
        $this->assertTrue($auth['terminalId'] == '2', 'wrong auth terminal id');
        $this->assertTrue($auth['terminalName'] == 'another term name', 'wrong auth terminal name'); 
    }

    /**
     * @expectedException ToopherRequestException
     */
    public function testToopherRequestException(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp1 = new HTTP_Request2_Response("HTTP/1.1 401 Unauthorized", false, 'https://toopher-api.appspot.com/v1/authentication_requests/1');
        $resp1->appendBody('{"error_code":401, "error_message":"Not a valid OAuth signed request"}');
        $mock->addResponse($resp1);


        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $auth = $toopher->getAuthenticationStatus('1');
    }
}

?>
