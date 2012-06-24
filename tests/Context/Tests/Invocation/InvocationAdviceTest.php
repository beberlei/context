<?php
namespace Context\Tests\Invocation;

use Context\Invocation\InvocationAdvice;
use Context\Invocation\ContextInvocation;

class InvocationAdviceTest extends \PHPUnit_Framework_TestCase
{
    private $advice;
    private $context;

    public function setUp()
    {
        $this->advice = new InvocationAdvice();
        $this->context = new ContextInvocation();
    }

    public function testInvoke()
    {
        $this->context->setOptions(array(
            'context' => __NAMESPACE__ . '\\id',
            'params' => array('invoke_test'),
        ));

        $this->assertEquals('invoke_test', $this->advice->around($this->context));
    }

    public function testInvokeWithParameterConverter()
    {
        $this->context->setOptions(array(
            'context' => __NAMESPACE__ . '\\data',
            'data'    => array('value' => array('id' => 1, 'val' => 'Hello World!')),
            'params' => array(),
        ));

        $ret = $this->advice->around($this->context);
    }
}

function id($value)
{
    return $value;
}

class Data
{
    public $id;
    public $val;
}

function data(Data $value)
{
    return $value;
}
