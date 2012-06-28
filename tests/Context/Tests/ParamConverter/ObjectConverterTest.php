<?php
namespace Context\Tests\ParamConverter;

use Context\ParamConverter\Argument;
use Context\ParamConverter\ObjectConverter;
use Context\ParamConverter\ConverterBag;

class ObjectConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    public function setUp()
    {
        $this->converter = new ObjectConverter();
        $converters = new ConverterBag();
        $converters->add($this->converter);
        $this->converter->setConverterBag($converters);
    }

    public function testConvertMapsToObjectConstructor()
    {
        $argument = new Argument("foo", __NAMESPACE__ . "\ConvertObject1");

        $convertedValue  = $this->converter->convert(array("foo" => 1, "bar" => 2), $argument, null);

        $this->assertInstanceOf(__NAMESPACE__ . '\ConvertObject1', $convertedValue);
        $this->assertEquals(-1, $convertedValue->foo);
        $this->assertEquals(-2, $convertedValue->bar);
    }

    public function testConvertMapsToPublicFields()
    {
        $argument = new Argument("foo", __NAMESPACE__ . "\ConvertObject2");

        $convertedValue = $this->converter->convert(array("foo" => 1, "bar" => 2), $argument, null);

        $this->assertInstanceOf(__NAMESPACE__ . '\ConvertObject2', $convertedValue);
        $this->assertEquals(1, $convertedValue->foo);
        $this->assertEquals(2, $convertedValue->bar);
    }

    public function testConvertMapsRecursively()
    {
        $argument = new Argument("foo", __NAMESPACE__ . "\ConvertObject3");

        $convertedValue = $this->converter->convert(array("obj2" => array("foo" => 1, "bar" => 2)), $argument, null);

        $this->assertInstanceOf(__NAMESPACE__ . '\ConvertObject3', $convertedValue);
        $this->assertInstanceOf(__NAMESPACE__ . '\ConvertObject2', $convertedValue->obj2);
        $this->assertEquals(1, $convertedValue->obj2->foo);
        $this->assertEquals(2, $convertedValue->obj2->bar);
    }
}

class ConvertObject1
{
    public $foo;
    public $bar;
    public function __construct($foo, $bar)
    {
        $this->foo = $foo * -1;
        $this->bar = $bar * -1;
    }
}

class ConvertObject2
{
    public $foo;
    public $bar;
}

class ConvertObject3
{
    public $obj2;
    public function __construct(ConvertObject2 $obj2)
    {
        $this->obj2 = $obj2;
    }
}
