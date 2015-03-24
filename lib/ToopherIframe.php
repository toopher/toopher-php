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

class SignatureValidationError extends Exception
{
}

class ToopherIframe
{
    const VERSION = '2';
    const TTL = '300';

    function __construct($key, $secret, $baseUrl = 'https://api.toopher.com/v1/')
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Toopher consumer key cannot be empty');
        }
        if (empty($secret)) {
            throw new InvalidArgumentException('Toopher consumer secret cannot be empty');
        }

        $this->consumerSecret = $secret;
        $this->consumerKey = $key;
        $this->oauthConsumer = new OAuth($key, $secret);
        $this->baseUrl = $baseUrl;
        $this->timestampOverride = NULL;
        $this->nonceOverride = NULL;
        $this->oauthVersion = '1.0';
        $this->signatureMethod = 'HMAC-SHA1';
    }

    public function setTimestampOverride($timestampOverride)
    {
        $this->timestampOverride = $timestampOverride;
    }

    public function setNonceOverride($nonceOverride)
    {
        $this->nonceOverride = $nonceOverride;
    }

    private function getUnixTimestamp()
    {
        if (!is_null($this->timestampOverride)) {
            return $this->timestampOverride;
        } else {
            return time();
        }
    }

    public function getAuthenticationUrl($username, $resetEmail = '', $requestToken = '', $actionName = 'Log In', $requesterMetadata = '', $kwargs = array())
    {
        if (array_key_exists('ttl', $kwargs)) {
            $ttl = $kwargs['ttl'];
            unset($kwargs['ttl']);
        } else {
            $ttl = ToopherIframe::TTL;
        }

        $params = array(
            'username' => $username,
            'reset_email' => $resetEmail,
            'action_name' => $actionName,
            'session_token' => $requestToken,
            'requester_metadata' => $requesterMetadata,
        );
        $params = array_merge($params, $kwargs);
        return $this->getOauthSignedUrl($this->baseUrl . 'web/authenticate', $ttl, $params);
    }

    public function getUserManagementUrl($username, $resetEmail = '', $kwargs = array())
    {
        if (array_key_exists('ttl', $kwargs)) {
            $ttl = $kwargs['ttl'];
            unset($kwargs['ttl']);
        } else {
            $ttl = ToopherIframe::TTL;
        }

        $params = array(
            'username' => $username,
            'reset_email' => $resetEmail,
        );
        $params = array_merge($params, $kwargs);
        return $this->getOauthSignedUrl($this->baseUrl . 'web/manage_user', $ttl, $params);
    }

    public function processPostback($data, $requestToken = '', $kwargs = array())
    {
        parse_str($data["toopher_iframe_data"], $toopherData);

        if (array_key_exists('error_code', $toopherData)) {
            throw new ToopherRequestException($toopherData['error_message'], $toopherData['error_code']);
        } else {
            $this->validateData($toopherData, $requestToken, $kwargs);
            $api = new ToopherApi($this->consumerKey, $this->consumerSecret);

            switch ($toopherData['resource_type']) {
                case 'authentication_request':
                    return new AuthenticationRequest($this->createAuthenticationRequestArray($toopherData), $api);
                case 'pairing':
                    return new Pairing($this->createPairingArray($toopherData), $api);
                case 'requester_user':
                    return new User($this->createUserArray($toopherData), $api);
                default:
                    throw new ToopherRequestException('The postback resource type is not valid: ' . $toopherData['resource_type']);
            }
        }
    }

    public function isAuthenticationGranted($data, $requestToken = '', $kwargs = array())
    {
        try {
            $authenticationRequest = $this->processPostback($data, $requestToken, $kwargs);
            if (is_a($authenticationRequest, 'AuthenticationRequest')) {
                return !$authenticationRequest->pending && $authenticationRequest->granted;
            } else {
                return false;
            }
        } catch (Exception $e) {
            if ($e->getCode() == 704) {
                error_log($e->getMessage());
                return true;
            } else {
                return false;
            }
        }
    }

    private function validateData($data, $requestToken, $kwargs)
    {
        try {
            $this->checkForMissingKeys($data);
            $this->verifySessionToken($data['session_token'], $requestToken);
            $this->checkIfSignatureIsExpired($data['timestamp'], $kwargs);
            $this->validateSignature($data);
            return $data;
        } catch (Exception $e) {
            throw new SignatureValidationError ('Exception while validating toopher signature: ' . $e);
        }
    }

    private function checkForMissingKeys($data)
    {
        $missingKeys = array();
        $requiredKeys = array('toopher_sig', 'timestamp', 'session_token');
        foreach ($requiredKeys as &$key) {
            if (!array_key_exists($key, $data)) {
                $missingKeys[] = $key;
            }
        }
        if (count($missingKeys) > 0) {
            $keys = implode(',', $missingKeys);
            throw new SignatureValidationError('Missing required keys: ' . $keys);
        }
    }

    private function verifySessionToken($sessionToken, $requestToken)
    {
        if ($requestToken != '' && $sessionToken != $requestToken) {
            throw new SignatureValidationError('Session token does not match expected value');
        }
    }

    private function checkIfSignatureIsExpired($timestamp, $kwargs)
    {
        if (array_key_exists('ttl', $kwargs)) {
            $ttl = $kwargs['ttl'];
            unset($kwargs['ttl']);
        } else {
            $ttl = 300;
        }
        $ttlValid = ($this->getUnixTimestamp() - $ttl) < $timestamp;
        if (!$ttlValid) {
            throw new SignatureValidationError('TTL Expired');
        }
    }

    private function validateSignature($data)
    {
        $maybeSignature = $data['toopher_sig'];
        unset($data['toopher_sig']);
        $signatureValid = false;
        try {
            $computedSignature = $this->signature($this->consumerSecret, $data);
            $signatureValid = $maybeSignature == $computedSignature;
        } catch (Exception $e) {
            throw new SignatureValidationError('Error while calculating signature: ' . $e);
        }

        if (!$signatureValid) {
            throw new SignatureValidationError('Computed signature does not match');
        }
    }

    private function signature($secret, $parameters)
    {
        ksort($parameters);
        $params = http_build_query($parameters);
        $sig = hash_hmac('sha1', $params, $secret, true);
        return base64_encode($sig);
    }

    private function createAuthenticationRequestArray($data)
    {
        return array(
            'id' => $data['id'],
            'pending'=>$data['pending'] == 'true',
            'granted'=>$data['granted'] == 'true',
            'automated'=>$data['automated'] == 'true',
            'reason'=>$data['reason'],
            'reason_code'=>$data['reason_code'],
            'terminal'=>array(
                'id'=>$data['terminal_id'],
                'name'=>$data['terminal_name'],
                'requester_specified_id'=>$data['terminal_requester_specified_id'],
                'user'=>array(
                    'id'=>$data['pairing_user_id'],
                    'name'=>$data['user_name'],
                    'toopher_authentication_enabled'=>$data['user_toopher_authentication_enabled'] == 'true'
                )
            ),
            'user'=>array(
                'id'=>$data['pairing_user_id'],
                'name'=>$data['user_name'],
                'toopher_authentication_enabled'=>$data['user_toopher_authentication_enabled'] == 'true'
            ),
            'action'=>array(
                'id'=>$data['action_id'],
                'name'=>$data['action_name']
            )
        );
    }

    private function createPairingArray($data)
    {
        return array(
            'id' => $data['id'],
            'enabled' => $data['enabled'] == 'true',
            'pending' => $data['pending'] == 'true',
            'user' => array(
                'id' => $data['pairing_user_id'],
                'name' => $data['user_name'],
                'toopher_authentication_enabled' => $data['user_toopher_authentication_enabled']
            )
        );
    }

    private function createUserArray($data)
    {
        return array(
            'id' => $data['id'],
            'name' => $data['name'],
            'toopher_authentication_enabled' => $data['toopher_authentication_enabled'] == 'true'
        );
    }

    private function getOauthSignedUrl($url, $ttl, $queryParams)
    {
        $queryParams['v'] = ToopherIframe::VERSION;
        $queryParams['expires'] = $this->getUnixTimestamp() + $ttl;

        $oauthParams = $this->getOauthParams();
        $encodedParams = $this->encodeParamsForSignature(array_merge($queryParams, $oauthParams));
        $signature = $this->oauthConsumer->generateSignature('GET', $url, $encodedParams);
        $oauthParams['oauth_signature'] = $signature;
        return $this->buildUrl($url, $queryParams, $oauthParams);
    }

    private function getOauthParams()
    {
        $oauthParams = array(
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_signature_method' => $this->signatureMethod,
            'oauth_version' => $this->oauthVersion
        );

        if (!is_null($this->nonceOverride)) {
            $oauthParams['oauth_nonce'] = $this->nonceOverride;
        } else {
            $oauthParams['oauth_nonce'] = uniqid() . '.' . time();
        }
        if (!is_null($this->timestampOverride)) {
            $oauthParams['oauth_timestamp'] = $this->timestampOverride;
        } else {
            $oauthParams['oauth_timestamp'] = time();
        }
        return $oauthParams;
    }

    private function encodeParamsForSignature($params)
    {
        foreach ($params as $key => $value) {
            $params[$key] = oauth_urlencode($value);
        }
        return $params;
    }

    private function buildUrl($url, $queryParams, $oauthParams)
    {
        $query = http_build_query($queryParams);
        $oauthQuery = http_build_query($oauthParams);
        return $url . '?' . $query . '&' . $oauthQuery;
    }
}

?>
