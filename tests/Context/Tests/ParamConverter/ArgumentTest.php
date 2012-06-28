<?php

namespace Context\Tests\ParamConverter;

use Context\ParamConverter\Argument;

class ArgumentTest extends \PHPUnit_Framework_TestCase
{
    public function testFromReflectionClass()
    {
        $argument = Argument::fromReflection($this->getFirstReflectionParameter(__NAMESPACE__ . '\\argument'));

        $this->assertEquals('arg', $argument->getName());
        $this->assertEquals('stdClass', $argument->getClass());
        $this->assertFalse($argument->isArray());
        $this->assertFalse($argument->isOptional());
    }

    public function testFromReflectionArray()
    {
        $argument = Argument::fromReflection($this->getFirstReflectionParameter(__NAMESPACE__ . '\\argumentArray'));

        $this->assertEquals('foo', $argument->getName());
        $this->assertNull($argument->getClass());
        $this->assertTrue($argument->isArray());
        $this->assertFalse($argument->isOptional());
    }

    public function testFromReflectionOptionalDefaultValue()
    {
        $argument = Argument::fromReflection($this->getFirstReflectionParameter(__NAMESPACE__ . '\\argumentOptional'));

        $this->assertTrue($argument->isOptional());
        $this->assertEquals(1234, $argument->getDefaultValue());
    }

    private function getFirstReflectionParameter($funcName)
    {
        $reflFunction = new \ReflectionFunction($funcName);
        $reflParameters = $reflFunction->getParameters();

        return $reflParameters[0];
    }
}

function argument(\stdClass $arg) {}
function argumentArray(array $foo) {}
function argumentOptional($opt = 1234) {}

