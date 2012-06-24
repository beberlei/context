<?php
namespace Context\Tests\ParamConverter;

use Context\Invocation\ContextInvocation;
use Context\ParamConverter\ParamsArgumentResolver;

class ParamsArgumentResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testResolve()
    {
        $context  = new ContextInvocation(array("params" => array(1, 2, 3)));
        $resolver = new ParamsArgumentResolver();

        $this->assertEquals(array(1, 2, 3), $resolver->resolve($context));
    }
}
