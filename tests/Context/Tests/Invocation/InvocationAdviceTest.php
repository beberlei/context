<?php
namespace Context\Tests\Invocation;

use Context\Invocation\InvocationAdvice;
use Context\Invocation\ContextInvocation;
use Context\ParamConverter\ObjectConverter;
use Context\ParamConverter\ConverterArgumentResolver;

class InvocationAdviceTest extends \PHPUnit_Framework_TestCase
{
    private $advice;
    private $context;

    public function setUp()
    {
        $resolver = new ConverterArgumentResolver();
        $resolver->addConverter(new ObjectConverter());
        $this->advice = new InvocationAdvice($resolver);
        $this->context = new ContextInvocation();
    }

    public function testInvoke()
    {
        $this->context->setOptions(array(
            'context' => __NAMESPACE__ . '\\id',
            'params'  => array('invoke_test'),
            'data'    => array()
        ));

        $this->assertEquals('invoke_test', $this->advice->around($this->context));
    }

    public function testInvokeWithParameterConverter()
    {
        $this->context->setOptions(array(
            'context' => __NAMESPACE__ . '\\data',
            'data'    => array('value' => array('id' => 1, 'val' => 'Hello World!')),
            'params'  => array(),
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
