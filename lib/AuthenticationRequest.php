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

class AuthenticationRequest
{
    protected $api;

    function __construct($jsonResponse, $api)
    {
        $this->api = $api;
        $this->id = $jsonResponse['id'];
        $this->pending = $jsonResponse['pending'];
        $this->granted = $jsonResponse['granted'];
        $this->automated = $jsonResponse['automated'];
        $this->reason_code = $jsonResponse['reason_code'];
        $this->reason = $jsonResponse['reason'];
        $this->terminal = new UserTerminal($jsonResponse['terminal'], $api);
        $this->user = new User($jsonResponse['user'], $api);
        $this->action = new Action($jsonResponse['action']);
        $this->raw_response = $jsonResponse;
    }

    public function refreshFromServer()
    {
        $url = 'authentication_requests/' . $this->id;
        $result = $this->api->advanced->raw->get($url);
        $this->update($result);
    }

    public function grantWithOtp($otp, $kwargs = array())
    {
        $url = 'authentication_requests/' . $this->id . '/otp_auth';
        $params = array('otp' => $otp);
        $params = array_merge($params, $kwargs);
        $result = $this->api->advanced->raw->post($url, $params);
        $this->update($result);
    }

    private function update($jsonResponse)
    {
        $this->pending = $jsonResponse['pending'];
        $this->granted = $jsonResponse['granted'];
        $this->automated = $jsonResponse['automated'];
        $this->reason_code = $jsonResponse['reason_code'];
        $this->reason = $jsonResponse['reason'];
        $this->terminal->update($jsonResponse['terminal']);
        $this->user->update($jsonResponse['user']);
        $this->action->update($jsonResponse['action']);
        $this->raw_respones = $jsonResponse;
    }
}

?>
