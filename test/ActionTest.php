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

class ActionTests extends PHPUnit_Framework_TestCase {

	public function testActionCreatesAction()
	{
		$action = new Action(['id' => '1', 'name' => 'action']);
		$this->assertTrue($action->id == '1', 'Action id was incorrect');
		$this->assertTrue($action->name == 'action', 'Action name was incorrect');
	}

	public function testActionUpdateChangesAction()
	{
		$action = new Action(['id' => '1', 'name' => 'action changed']);
		$action->update(['id'=>'1', 'name'=>'action changed']);
		$this->assertTrue($action->id == '1', 'Action id was incorrect');
		$this->assertTrue($action->name == 'action changed', 'Action name was incorrect');
	}

	/**
	* @expectedException		ToopherRequestException
	* @expectedExceptionMessage	Could not parse action from response
	*/
	public function testActionMissingKeyFails()
	{
		$action = new Action(['name' => 'action changed']);
	}

	/**
	* @expectedException		ToopherRequestException
	* @expectedExceptionMessage	Could not parse action from response
	*/
	public function testActionUpdateMissingKeyFails()
	{
		$action = new Action(['id' => '1', 'name' => 'action changed']);
		$action->update(['id'=>'1']);
	}
}

?>
