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

class Pairing
{
    protected $api;

    function __construct($jsonResponse, $api)
    {
        $this->api = $api;
        $this->id = $jsonResponse['id'];
        $this->enabled = $jsonResponse['enabled'];
        $this->pending = $jsonResponse['pending'];
        $this->user = new User($jsonResponse['user'], $api);
        $this->raw_response = $jsonResponse;
    }

    public function refreshFromServer()
    {
        $url = 'pairings/' . $this->id;
        $result = $this->api->advanced->raw->get($url);
        $this->update($result);
    }

    public function getResetLink($kwargs = array())
    {
        if(!array_key_exists('security_question', $kwargs)) {
            $kwargs['security_question'] = NULL;
        }
        if(!array_key_exists('security_answer', $kwargs)) {
            $kwargs['security_answer'] = NULL;
        }
        $url = 'pairings/' . $this->id . '/generate_reset_link';
        $result = $this->api->advanced->raw->post($url, $kwargs);
        return $result['url'];
    }

    public function emailResetLink($email, $kwargs = array())
    {
        $params = array('reset_email' => $email);
        $params = array_merge($params, $kwargs);
        $url = 'pairings/' . $this->id . '/send_reset_link';
        $this->api->advanced->raw->post($url, $params);
    }

    public function getQrCodeImage()
    {
        $url = 'qr/pairings/' . $this->id;
        return $this->api->advanced->raw->get_raw($url);
    }

    private function update($jsonResponse)
    {
        $this->enabled = $jsonResponse['enabled'];
        $this->pending = $jsonResponse['pending'];
        $this->user->update($jsonResponse['user']);
        $this->raw_response = $jsonResponse;
    }
}

?>
