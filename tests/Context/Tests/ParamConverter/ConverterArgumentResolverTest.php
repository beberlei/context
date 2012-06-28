<?php

namespace Context\Tests\ParamConverter;

use Context\ParamConverter\ConverterArgumentResolver;
use Context\Invocation\ContextInvocation;
use Context\Tests\TestCase;

class ConverterArgumentResolverTest extends TestCase
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

        $converter = $this->mock('Context\ParamConverter\ParamConverter');
        $converter->shouldReceive('setConverterBag');
        $converter->shouldReceive('getPriority')->andReturn(1);
        $converter->shouldReceive('supports')->andReturn(true);
        $converter->shouldReceive('convert')->andReturn('bar');

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

        $converter1 = $this->mock('Context\ParamConverter\ParamConverter');
        $converter1->shouldReceive('setConverterBag');
        $converter1->shouldReceive('getPriority')->andReturn(1);

        $converter2 = $this->mock('Context\ParamConverter\ParamConverter');
        $converter2->shouldReceive('setConverterBag');
        $converter2->shouldReceive('getPriority')->andReturn(2);
        $converter2->shouldReceive('supports')->andReturn(true);
        $converter2->shouldReceive('convert')->andReturn('baz');

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
