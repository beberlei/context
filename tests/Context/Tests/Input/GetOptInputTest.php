<?php
namespace Context\Tests\Input;

use Context\Input\GetOptInput;

class GetOptInputTest extends \PHPUnit_Framework_TestCase
{
    public function testGetOptInput()
    {
        $input = new GetOptInput();

        $this->assertFalse($input->hasData(array('shortOptions' => '', 'longOptions' => array())));
        $this->assertTrue($input->hasData(array('shortOptions' => 'a', 'longOptions' => array())));

        $data = $input->createData(array('shortOptions' => 'a', 'longOptions' => array('configuration')));

        $this->assertInstanceOf('Context\ParamConverter\RequestData', $data);
    }
}

