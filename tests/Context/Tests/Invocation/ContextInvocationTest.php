<?php
namespace Context\Tests\Invocation;
use Context\Invocation\ContextInvocation;

class ContextInvocationTest extends \PHPUnit_Framework_TestCase
{
    public function testOptions()
    {
        $invocation = new ContextInvocation(array("foo" => "bar"));

        $this->assertEquals(array("foo" => "bar"), $invocation->getOptions());

        $invocation->setOptions(array("bar" => "baz"));
        $this->assertEquals(array("bar" => "baz"), $invocation->getOptions());
    }

    public function testAdviceStackEmpty()
    {
        $invocation = new ContextInvocation();
        $this->setExpectedException("Context\Exception\RuntimeException");
        $invocation->invoke();
    }

    public function testAdviceStack()
    {
        $advice1 = $this->getMock('Context\Invocation\Advice');
        $advice1->expects($this->once())->method('around')->will($this->returnValue("foo"));
        $advice2 = $this->getMock('Context\Invocation\Advice');
        $advice2->expects($this->once())->method('around')->will($this->returnValue("bar"));

        $invocation = new ContextInvocation(array(), array($advice1, $advice2));

        $this->assertEquals("foo", $invocation->invoke());
        $this->assertEquals("bar", $invocation->invoke());
    }
}
