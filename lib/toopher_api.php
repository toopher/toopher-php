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

require_once("common.inc.php");

if (!function_exists('curl_init')) {
  throw new Exception('Facebook needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Facebook needs the JSON PHP extension.');
}

class ToopherAPI
{
    protected $baseUrl;
    protected $oauthConsumer;
    protected $hmacMethod;
    protected $httpAdapter;
    protected $oauthParameters;

    function __construct($key, $secret, $baseUrl = '', $httpAdapter = NULL, $oauthParameters = NULL)
    {
        if(empty($key))
        {
            throw new InvalidArgumentException('Toopher consumer key cannot be empty');
        }
        if(empty($secret))
        {
            throw new InvalidArgumentException('Toopher consumer secret cannot be empty');
        }

        $this->oauthConsumer = new OAuthConsumer($key, $secret, NULL);
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : 'https://toopher-api.appspot.com/v1/';
        $this->httpAdapter = (!is_null($httpAdapter)) ? $httpAdapter : new HTTP_Request2_Adapter_Curl();
        $this->hmacMethod = new OAuthSignatureMethod_HMAC_SHA1();
        $this->oauthParameters = $oauthParameters;
    }

    public function pair($pairingPhrase, $userName)
    {
        return $this->makePairResponse($this->post('pairings/create', array(
            'pairing_phrase' => $pairingPhrase,
            'user_name' => $userName
        )));
    }

    public function getPairingStatus($pairingId)
    {
        return $this->makePairResponse($this->get('pairings/' . $pairingId));
    }

    public function authenticate($pairingId, $terminalName, $actionName = '')
    {
        $params = array(
            'pairing_id' => $pairingId,
            'terminal_name' => $terminalName
        );
        if(!empty($actionName))
        {
            $params['action_name'] = $actionName;
        }
        return $this->makeAuthResponse($this->post('authentication_requests/initiate', $params));
    }

    public function getAuthenticationStatus($authenticationRequestId)
    {
        return $this->makeAuthResponse($this->get('authentication_requests/' . $authenticationRequestId));
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
        return array(
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
        return $this->request('POST', $endpoint, $parameters);
    }

    private function get($endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    private function request($method, $endpoint, $parameters = NULL)
    {
        $oauthReq = new OAuthRequest($method, $this->baseUrl . $endpoint, $parameters);
        $oauthReq->sign_request($this->hmacMethod, $this->oauthConsumer, $this->oauthParameters);
        $req = new HTTP_Request2();
        $req->setMethod($method);
        $req->setUrl($this->baseUrl . $endpoint);
        if(!is_null($parameters))
        {
            foreach($parameters as $key => $value)
            {
                $req->addPostParameter($key, $value);
            }
        }
        $req->setHeader('Authorization', $oauthReq->to_header());
        $req->setAdapter($this->httpAdapter);
        print("dumping request to $endpoint\n");
        print_r($req);
        return json_decode($req->send()->getBody(), true);
    }
}

?>
