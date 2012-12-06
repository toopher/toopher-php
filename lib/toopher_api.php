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

if (!function_exists('curl_init')) {
  throw new Exception('Facebook needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Facebook needs the JSON PHP extension.');
}

class ToopherAPI
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;

    protected $http_adapter;

    function __construct($key = '', $secret = '', $baseUrl = '', $http_adapter = NULL)
    {
        $this->consumerKey = (!empty($key)) ? $key : getenv('TOOPHER_CONSUMER_KEY');
        $this->consumerSecret = (!empty($secret)) ? $secret : getenv('TOOPHER_CONSUMER_SECRET');
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : 'https://toopher-api.appspot.com/v1/';

        $this->http_adapter = (!is_null($http_adapter)) ? $http_adapter : new HTTP_Request2_Adapter_Curl();
        
        if(empty($this->consumerKey))
        {
            throw new InvalidArgumentException('Toopher consumer key not supplied (try defining $TOOPHER_CONSUMER_KEY)');
        }
        if(empty($this->consumerSecret))
        {
            throw new InvalidArgumentException('Toopher consumer secret not supplied (try defining $TOOPHER_CONSUMER_SECRET)');
        }
    }

    public function pair($pairingPhrase, $userName)
    {
    }

    public function getPairingStatus($paringId)
    {
    }

    public function authenticate($pairingId)
    {
    }

    public function getAuthenticationStatus($authenticationRequestId)
    {
    }

    private function makePairResponse($result)
    {
        return array(
            'id' => $result['id'],
            'enabled' => $result['enabled'],
            'userId' => $result['user']['id'],
            'userName' => $result['user']['name']
        );
    }

    private function makeAuthResponse($result)
    {
        retun array(
            'id' => $result['id'],
            'pending' => $result['pending'],
            'granted' => $result['granted'],
            'automated' => $result['automated'],
            'reason' => $result['reason'],
            'terminalId' => $result['terminal']['id'],
            'terminalName' => $result['terminal']['name']
        );
    }

    private function post($endpoint, $parameters)
    {
    }

    private function get($endpoint)
    {
    }

    private function request($url, $req)
    {
        $req->setAdapter($http_adapter);

    }
}

?>
