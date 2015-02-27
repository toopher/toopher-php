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

class User
{
    protected $api;

    function __construct($jsonResponse, $api)
    {
        $this->api = $api;
        $this->id = $jsonResponse['id'];
        $this->name = $jsonResponse['name'];
        $this->toopher_authentication_enabled = $jsonResponse['toopher_authentication_enabled'];
        $this->raw_response = $jsonResponse;
    }

    public function refreshFromServer()
    {
        $url = 'users/' . $this->id;
        $result = $this->api->advanced->raw->get($url);
        $this->update($result);
    }

    public function enableToopherAuthentication()
    {
        $url = 'users/' . $this->id;
        $result = $this->api->advanced->raw->post($url, array('toopher_authentication_enabled' => 'true'));
        $this->update($result);
    }

    public function disableToopherAuthentication()
    {
        $url = 'users/' . $this->id;
        $result = $this->api->advanced->raw->post($url, array('toopher_authentication_enabled' => 'false'));
        $this->update($result);
    }

    public function update($jsonResponse)
    {
        $this->name = $jsonResponse['name'];
        $this->toopher_authentication_enabled = $jsonResponse['toopher_authentication_enabled'];
        $this->raw_response = $jsonResponse;
    }
}

?>
