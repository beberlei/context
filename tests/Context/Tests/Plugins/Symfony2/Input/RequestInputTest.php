<?php

namespace Context\Tests\Plugins\Symfony2\Input;

use Symfony\Component\HttpFoundation\Request;
use Context\Plugins\Symfony2\Input\RequestInput;

class RequestInputTest extends \PHPUnit_Framework_TestCase
{
    public function testHasData()
    {
        $request = new Request();
        $input   = new RequestInput();

        $this->assertTrue($input->hasData(array('request' => $request)));
        $this->assertFalse($input->hasData(array('request' => null)));
    }

    public function testCreateData()
    {
        $request = new Request(array('foo' => 'bar'), array('bar' => 'baz'));
        $input   = new RequestInput();

        $data = $input->createData(array('request' => $request));
        $this->assertInstanceOf('Context\ParamConverter\RequestData', $data);

        $this->assertFalse($data->hasRawInput());
        $this->assertTrue($data->has('foo'));
        $this->assertEquals('bar', $data->get('foo'));
    }
}
