<?php
namespace Context\Tests\ParamConverter;

use Context\ParamConverter\DateTimeConverter;
use Context\ParamConverter\Argument;
use Context\ParamConverter\RequestData;

class DateTimeConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    public function setUp()
    {
        $this->converter = new DateTimeConverter();
    }

    public function testSupports()
    {
        $rd = new RequestData;
        $this->assertTrue($this->converter->supports(null, new Argument('a', 'DateTime', false, false, null), $rd));
        $this->assertTrue($this->converter->supports(null, new Argument('a', 'Context\Tests\ParamConverter\DateTime', false, false, null), $rd));
        $this->assertFalse($this->converter->supports(null, new Argument('a', 'stdClass', false, false, null), $rd));
    }

    public function testConvert()
    {
        $datetime = $this->converter->convert('2010-01-01', new Argument('a', 'DateTime', false, false, null), new RequestData);
        $this->assertInstanceOf('DateTime', $datetime);
        $this->assertEquals('2010-01-01', $datetime->format('Y-m-d'));
    }
}

class DateTime extends \DateTime
{
}
