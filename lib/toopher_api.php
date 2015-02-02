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

class ToopherRequestException extends Exception
{
    
}

class ToopherAPI
{
    const VERSION = '1.0.6';

    protected $baseUrl;
    protected $oauthConsumer;
    protected $httpAdapter;

    function __construct($key, $secret, $baseUrl = '', $httpAdapter = NULL)
    {
        if(empty($key))
        {
            throw new InvalidArgumentException('Toopher consumer key cannot be empty');
        }
        if(empty($secret))
        {
            throw new InvalidArgumentException('Toopher consumer secret cannot be empty');
        }

        $this->oauthConsumer = new HTTP_OAuth_Consumer($key, $secret);
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : 'https://api.toopher.com/v1/';
        $this->httpAdapter = (!is_null($httpAdapter)) ? $httpAdapter : new HTTP_Request2_Adapter_Curl();
        $this->advanced = new AdvancedApiUsageFactory($key, $secret, $baseUrl, $httpAdapter, $this);
    }

    public function pair($username, $phrase_or_num = '', $kwargs = array())
    {
        $params = array('user_name' => $username);
        $params = array_merge($params, $kwargs);
        if (!empty($phrase_or_num))
        {
            if(preg_match('/\d/', $phrase_or_num, $match))
            {
                $url = 'pairings/create/sms';
                $params['phone_number'] = $phrase_or_num;
            }
            else
            {
                $url = 'pairings/create';
                $params['pairing_phrase'] = $phrase_or_num;
            }
        }
        else
        {
            $url = 'pairings/create/qr';
        }
        $result = $this->advanced->raw->post($url, $params);
        return new Pairing($result);
    }

    public function authenticate($id_or_username, $terminal, $actionName = '', $kwargs = array())
    {
        $url = 'authentication_requests/initiate';
        $uuid_pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        if(preg_match($uuid_pattern, $id_or_username, $match))
        {
            $params = array(
                'pairing_id' => $id_or_username,
                'terminal_name' => $terminal
            );
        }
        else
        {
            $params = array(
                'user_name' => $id_or_username,
                'terminal_name_extra' => $terminal
            );
        }

        if(!empty($actionName))
        {
            $params['action_name'] = $actionName;
        }
        $params = array_merge($params, $kwargs);
        $result = $this->advanced->raw->post($url, $params);
        return new AuthenticationRequest($result);
    }
}

class AdvancedApiUsageFactory
{
    function __construct($key, $secret, $baseUrl, $httpAdapter, $api)
    {
        $this->raw = new ApiRawRequester($key, $secret, $baseUrl, $httpAdapter);
        $this->pairings = new Pairings($api);
        $this->authenticationRequests = new AuthenticationRequests($api);
    }
}

class ApiRawRequester
{
    protected $oauthConsumer;
    protected $baseUrl;
    protected $httpAdapter;

    function __construct($key, $secret, $baseUrl, $httpAdapter)
    {
        if(empty($key))
        {
            throw new InvalidArgumentException('Toopher consumer key cannot be empty');
        }
        if(empty($secret))
        {
            throw new InvalidArgumentException('Toopher consumer secret cannot be empty');
        }

        $this->oauthConsumer = new HTTP_OAuth_Consumer($key, $secret);
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : 'https://api.toopher.com/v1/';
        $this->httpAdapter = (!is_null($httpAdapter)) ? $httpAdapter : new HTTP_Request2_Adapter_Curl();
    }

    public function post($endpoint, $parameters)
    {
        return $this->request('POST', $endpoint, $parameters);
    }

    public function get($endpoint)
    {
        return $this->request('GET', $endpoint);
    }

    private function request($method, $endpoint, $parameters = array())
    {
        $req = new HTTP_Request2();
        $req->setAdapter($this->httpAdapter);
        $req->setHeader(array('User-Agent' =>
            sprintf('Toopher-PHP/%s (PHP %s)', ToopherAPI::VERSION, phpversion())));
        $req->setMethod($method);
        $req->setUrl($this->baseUrl . $endpoint);
        if(!is_null($parameters))
        {
            foreach($parameters as $key => $value)
            {
                $req->addPostParameter($key, $value);
            }
        }
        $oauthRequest = new HTTP_OAuth_Consumer_Request;
        $oauthRequest->accept($req);
        $this->oauthConsumer->accept($oauthRequest);
        try {
            $result = $this->oauthConsumer->sendRequest($this->baseUrl . $endpoint, $parameters, $method);
        } catch (Exception $e) {
            error_log($e);
            throw new ToopherRequestException("Error making Toopher API request", $e->getCode(), $e);
        }

        if ($result->getStatus() != 200)
        {
            error_log(sprintf("Toopher API call returned unexpected HTTP response: %d - %s", $result->getStatus(), $result->getReasonPhrase()));
            $resultBody = $result->getBody();
            if (empty($resultBody)) {
                error_log("empty response body");
                throw new ToopherRequestException($result->getReasonPhrase(), $result->getStatus());
            }

            $err = json_decode($resultBody, true);
            if ($err === NULL) {
                $json_error = $this->json_error_to_string(json_last_error()); 
                if (!empty($json_error)) {
                    error_log(sprintf("Error parsing response body JSON: %s", $json_error));
                    error_log(sprintf("response body: %s", $result->getBody()));
                    throw new ToopherRequestException(sprintf("JSON Parsing Error: %s", $json_error));
                }
            } else {
                if(array_key_exists("error_message", $err)) {
                    throw new ToopherRequestException($err['error_message'], $err['error_code']);
                } else {
                    throw new ToopherRequestException(sprintf("%s - %s", $result->getReasonPhrase(), $resultBody), $result->getStatus());
                }
            }
        }

        $decoded = json_decode($result->getBody(), true);
        if ($decoded === NULL) {
            $json_error = $this->json_error_to_string(json_last_error()); 
            if (!empty($json_error)) {
                error_log(sprintf("Error parsing response body JSON: %s", $json_error));
                error_log(sprintf("response body: %s", $result->getBody()));
                throw new ToopherRequestException(sprintf("JSON Parsing Error: %s", $json_error));
            }
        }
        return $decoded;   
    }

    private function json_error_to_string($json_error_code) {
        switch ($json_error_code) {
            case JSON_ERROR_NONE:
                return NULL;
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}

class Pairings
{
    protected $api;

    function __construct($api)
    {
        $this->api = $api;
    }


    public function getById($pairingId)
    {
        $result = $this->api->advanced->raw->get('pairings/' . $pairingId);
        return new Pairing($result);
    }
}

class Pairing
{
    function __construct($json_response)
    {
        $this->id = $json_response['id'];
        $this->enabled = $json_response['enabled'];
        $this->userId = $json_response['user']['id'];
        $this->userName = $json_response['user']['name'];
        $this->raw = $json_response;
    }
}

class AuthenticationRequests
{
    protected $api;

    function __construct($api)
    {
        $this->api = $api;
    }


    public function getById($authenticationRequestId)
    {
        $result = $this->api->advanced->raw->get('authentication_requests/' . $authenticationRequestId);
        return new AuthenticationRequest($result);
    }
}

class AuthenticationRequest
{
    function __construct($json_response)
    {
        $this->id = $json_response['id'];
        $this->pending = $json_response['pending'];
        $this->granted = $json_response['granted'];
        $this->automated = $json_response['automated'];
        $this->reason = $json_response['reason'];
        $this->terminalId = $json_response['terminal']['id'];
        $this->terminalName = $json_response['terminal']['name'];
        $this->raw = $json_response;
    }
}

?>
