<?php

namespace Context\Tests\ParamConverter;

use Context\ParamConverter\ConverterArgumentResolver;
use Context\Invocation\ContextInvocation;

class ConverterArgumentResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolveEmpty()
    {
        $invocation = new ContextInvocation();
        $invocation->setOptions(array(
            'context' => array(new ConverterService(), 'execute'),
            'params'  => array(),
            'data'    => array(),
        ));

        $resolver = new ConverterArgumentResolver();
        $params   = $resolver->resolve($invocation);

        $this->assertEquals(array(), $params);
    }

    public function testResolveConvert()
    {
        $invocation = new ContextInvocation();
        $invocation->setOptions(array(
            'context' => array(new ConverterService(), 'withParam'),
            'params'  => array(),
            'data'    => array(),
        ));

        $converter = $this->getMock('Context\ParamConverter\ParamConverter');
        $converter->expects($this->at(0))->method('getPriority')->will($this->returnValue(1));
        $converter->expects($this->at(1))->method('supports')->will($this->returnValue(true));
        $converter->expects($this->at(2))->method('convert')->will($this->returnValue('bar'));

        $resolver = new ConverterArgumentResolver();
        $resolver->addConverter($converter);
        $params   = $resolver->resolve($invocation);

        $this->assertEquals(array('bar'), $params);
    }

    public function testResolvePriorityDecides()
    {
        $invocation = new ContextInvocation();
        $invocation->setOptions(array(
            'context' => array(new ConverterService(), 'withParam'),
            'params'  => array(),
            'data'    => array(),
        ));

        $converter1 = $this->getMock('Context\ParamConverter\ParamConverter');
        $converter1->expects($this->at(0))->method('getPriority')->will($this->returnValue(1));
        $converter1->expects($this->never())->method('supports');
        $converter1->expects($this->never())->method('convert');

        $converter2 = $this->getMock('Context\ParamConverter\ParamConverter');
        $converter2->expects($this->at(0))->method('getPriority')->will($this->returnValue(2));
        $converter2->expects($this->at(1))->method('supports')->will($this->returnValue(true));
        $converter2->expects($this->at(2))->method('convert')->will($this->returnValue('baz'));

        $resolver = new ConverterArgumentResolver();
        $resolver->addConverter($converter1);
        $resolver->addConverter($converter2);

        $params   = $resolver->resolve($invocation);

        $this->assertEquals(array('baz'), $params);

    }
}

class ConverterService
{
    public function execute()
    {
    }

    public function withParam($foo)
    {
    }
}
