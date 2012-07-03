<?php
namespace Context\Tests\Input;

use Context\Input\ArgvInput;

class ArgvInputTest extends \PHPUnit_Framework_TestCase
{
    public function testArgvInput()
    {
        $input = new ArgvInput();

        $this->assertTrue($input->hasData(array()));
        $data = $input->createData(array());

        $this->assertInstanceOf('Context\ParamConverter\RequestData', $data);
    }
}
