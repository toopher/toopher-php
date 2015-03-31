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

class ToopherApi
{
    const VERSION = '2.0.0';
    const DEFAULT_BASE_URL = 'https://api.toopher.com/v1/';

    protected $baseUrl;
    protected $oauthConsumer;
    protected $httpAdapter;

    function __construct($key, $secret, $baseUrl = '', $httpAdapter = NULL)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Toopher consumer key cannot be empty');
        }
        if (empty($secret)) {
            throw new InvalidArgumentException('Toopher consumer secret cannot be empty');
        }

        $this->oauthConsumer = new HTTP_OAuth_Consumer($key, $secret);
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : ToopherApi::DEFAULT_BASE_URL;
        $this->httpAdapter = (!is_null($httpAdapter)) ? $httpAdapter : new HTTP_Request2_Adapter_Curl();
        $this->advanced = new AdvancedApiUsageFactory($key, $secret, $baseUrl, $httpAdapter, $this);
    }

    public function pair($username, $phraseOrNumber = NULL, $kwargs = array())
    {
        $params = array('user_name' => $username);
        if (!empty($phraseOrNumber)) {
            if (preg_match('/\d/', $phraseOrNumber, $match)) {
                $url = 'pairings/create/sms';
                $params['phone_number'] = $phraseOrNumber;
            } else {
                $url = 'pairings/create';
                $params['pairing_phrase'] = $phraseOrNumber;
            }
        } else {
            $url = 'pairings/create/qr';
        }
        $params = array_merge($params, $kwargs);
        $result = $this->advanced->raw->post($url, $params);
        return new Pairing($result, $this);
    }

    public function authenticate($pairingIdOrUsername, $terminalName = NULL, $requesterSpecifiedId = NULL, $actionName = NULL, $kwargs = array())
    {
        $url = 'authentication_requests/initiate';
        $uuidPattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        if (preg_match($uuidPattern, $pairingIdOrUsername, $match)) {
            $params = array('pairing_id' => $pairingIdOrUsername);
        } else {
            $params = array('user_name' => $pairingIdOrUsername);
        }
        if (!empty($actionName)) {
            $params['action_name'] = $actionName;
        }
        if (!empty($terminalName)) {
            $params['terminal_name'] = $terminalName;
        }
        if (!empty($requesterSpecifiedId)) {
            $params['requester_specified_terminal_id'] = $requesterSpecifiedId;
        }
        $params = array_merge($params, $kwargs);
        $result = $this->advanced->raw->post($url, $params);
        return new AuthenticationRequest($result, $this);
    }
}

abstract class ToopherObjectFactory
{
    protected $api;

    function __construct($api)
    {
        $this->api = $api;
    }
}

class AdvancedApiUsageFactory
{
    function __construct($key, $secret, $baseUrl, $httpAdapter, $api)
    {
        $this->raw = new ApiRawRequester($key, $secret, $baseUrl, $httpAdapter);
        $this->pairings = new Pairings($api);
        $this->authenticationRequests = new AuthenticationRequests($api);
        $this->users = new Users($api);
        $this->userTerminals = new UserTerminals($api);
    }
}

class ApiRawRequester
{
    protected $oauthConsumer;
    protected $baseUrl;
    protected $httpAdapter;

    function __construct($key, $secret, $baseUrl, $httpAdapter)
    {
        $this->oauthConsumer = new HTTP_OAuth_Consumer($key, $secret);
        $this->baseUrl = (!empty($baseUrl)) ? $baseUrl : ToopherApi::DEFAULT_BASE_URL;
        $this->httpAdapter = (!is_null($httpAdapter)) ? $httpAdapter : new HTTP_Request2_Adapter_Curl();
    }

    public function getOauthConsumer()
    {
        return $this->oauthConsumer;
    }

    public function post($endpoint, $parameters)
    {
        return $this->request('POST', $endpoint, $parameters);
    }

    public function get($endpoint, $parameters = array())
    {
        return $this->request('GET', $endpoint, $parameters);
    }

    public function get_raw($endpoint)
    {
        return $this->request('GET', $endpoint, array(), true);
    }

    private function request($method, $endpoint, $parameters = array(), $rawRequest = false)
    {
        $req = new HTTP_Request2();
        $req->setAdapter($this->httpAdapter);
        $req->setHeader(array('User-Agent' => sprintf('Toopher-PHP/%s (PHP %s)', ToopherApi::VERSION, phpversion())));
        $req->setMethod($method);
        $req->setUrl($this->baseUrl . $endpoint);
        if (!is_null($parameters)) {
            foreach($parameters as $key => $value) {
                $req->addPostParameter($key, $value);
            }
        }
        $oauthRequest = new HTTP_OAuth_Consumer_Request;
        $oauthRequest->accept($req);
        $this->oauthConsumer->accept($oauthRequest);
        try {
            $result = $this->oauthConsumer->sendRequest($this->baseUrl . $endpoint, $parameters, $method);
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new ToopherRequestException('Error making Toopher API request', $e->getCode(), $e);
        }

        $resultBody = $result->getBody();
        if ($result->getStatus() >= 400) {
            error_log(sprintf('Toopher API call returned unexpected HTTP response: %d - %s', $result->getStatus(), $result->getReasonPhrase()));
            if (empty($resultBody)) {
                error_log('Empty response body');
                throw new ToopherRequestException($result->getReasonPhrase(), $result->getStatus());
            }

            $err = json_decode($resultBody, true);
            if ($err == NULL) {
                $jsonError = $this->json_error_to_string(json_last_error());
                if (!empty($jsonError))
                {
                    error_log(sprintf('Error parsing response body JSON: %s', $jsonError));
                    error_log(sprintf('Response body: %s', $result->getBody()));
                    throw new ToopherRequestException(sprintf('JSON Parsing Error: %s', $jsonError));
                }
            }

            if(array_key_exists('error_message', $err))
            {
                throw new ToopherRequestException($err['error_message'], $err['error_code']);
            } else {
                throw new ToopherRequestException(sprintf('%s - %s', $result->getReasonPhrase(), $resultBody), $result->getStatus());
            }
        }

        if ($rawRequest) {
            return $resultBody;
        } else {
            $decoded = json_decode($resultBody, true);
            if ($decoded === NULL) {
                $jsonError = $this->json_error_to_string(json_last_error());
                if (!empty($jsonError)) {
                    error_log(sprintf('Error parsing response body JSON: %s', $jsonError));
                    error_log(sprintf('Response body: %s', $result->getBody()));
                    throw new ToopherRequestException(sprintf('JSON Parsing Error: %s', $jsonError));
                }
            }
        return $decoded;
        }
    }

    private function json_error_to_string($jsonErrorCode)
    {
        switch ($jsonErrorCode) {
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

class Pairings extends ToopherObjectFactory
{
    public function getById($pairingId)
    {
        $url = 'pairings/' . $pairingId;
        $result = $this->api->advanced->raw->get($url);
        return new Pairing($result, $this->api);
    }
}

class AuthenticationRequests extends ToopherObjectFactory
{
    public function getById($authenticationRequestId)
    {
        $url = 'authentication_requests/' . $authenticationRequestId;
        $result = $this->api->advanced->raw->get($url);
        return new AuthenticationRequest($result, $this->api);
    }
}

class Users extends ToopherObjectFactory
{
    public function getById($userId)
    {
        $url = 'users/' . $userId;
        $result = $this->api->advanced->raw->get($url);
        return new User($result, $this->api);
    }

    public function getByName($username)
    {
        $url = 'users';
        $params = array('user_name' => $username);
        $users = $this->api->advanced->raw->get($url, $params);
        if (sizeof($users) > 1) {
            throw new ToopherRequestException(sprintf('Multiple users with name = %s', $username));
        } elseif (empty($users)) {
            throw new ToopherRequestException(sprintf('No users with name = %s', $username));
        }
        return new User(array_shift($users), $this->api);
    }

    public function create($username, $kwargs = array())
    {
        $url = 'users/create';
        $kwargs ['name'] = $username;
        $result = $this->api->advanced->raw->post($url, $kwargs);
        return new User($result, $this->api);
    }
}

class UserTerminals extends ToopherObjectFactory
{
    public function getById($userTerminalId)
    {
        $url = 'user_terminals/' . $userTerminalId;
        $result = $this->api->advanced->raw->get($url);
        return new UserTerminal($result, $this->api);
    }

    public function create($username, $terminalName, $requesterSpecifiedId, $kwargs = array())
    {
        $url = 'user_terminals/create';
        $params = array(
            'user_name' => $username,
            'name' => $terminalName,
            'name_extra' => $requesterSpecifiedId
        );
        $params = array_merge($params, $kwargs);
        $result = $this->api->advanced->raw->post($url, $params);
        return new UserTerminal($result, $this->api);
    }
}

?>
