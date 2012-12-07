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

class ToopherAPITests extends PHPUnit_Framework_TestCase {

    protected static $oldKey;
    protected static $oldSecret;
    public static function setUpBeforeClass(){
        self::$oldKey = getenv('TOOPHER_CONSUMER_KEY');
        self::$oldSecret = getenv('TOOPHER_CONSUMER_SECRET');
        putenv('TOOPHER_CONSUMER_KEY=');
        putenv('TOOPHER_CONSUMER_SECRET=');
    }
    public static function tearDownAfterClass(){
        putenv("TOOPHER_CONSUMER_KEY=" . self::$oldKey);
        putenv("TOOPHER_CONSUMER_SECRET=" . self::$oldSecret);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testEmptyKeyEmptySecretThrowsException() {
        $toopher = new ToopherAPI();
    }

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

    public function testCanCreateToopherApiWithEnvironmentVars() {
        putenv("TOOPHER_CONSUMER_KEY=key");
        putenv("TOOPHER_CONSUMER_SECRET=secret");
        $toopher = new ToopherAPI();
        putenv("TOOPHER_CONSUMER_KEY=");
        putenv("TOOPHER_CONSUMER_SECRET=");
    }

    public function testCreatePair(){
        $mock = new HTTP_Request2_Adapter_Mock();
        $resp = new HTTP_Request2_Response("HTTP/1.1 200 OK", false, 'https://toopher-api.appspot.com/v1/pairings/create');
        $resp->appendBody('{"id":"1","enabled":true,"user":{"id":"1","name":"user"}}');
        $mock->addResponse($resp);
        $toopher = new ToopherAPI('key', 'secret', '', $mock);
        $pairing = $toopher->pair('immediate_pair', 'user');
        $this->assertTrue($pairing['id'] == '1', 'bad pairing id');
        $this->assertTrue($pairing['enabled'] == true, 'pairing not enabled');
        $this->assertTrue($pairing['userId'] == '1', 'bad user id');
        $this->assertTrue($pairing['userName'] == 'user', 'bad user name');
    }
}

?>
