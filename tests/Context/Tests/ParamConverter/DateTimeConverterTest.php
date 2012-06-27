<?php
namespace Context\Tests\ParamConverter;

use Context\ParamConverter\DateTimeConverter;

class DateTimeConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    public function setUp()
    {
        $this->converter = new DateTimeConverter();
    }

    public function testSupports()
    {
        $this->assertTrue($this->converter->supports(null, 'DateTime'));
        $this->assertTrue($this->converter->supports(null, 'Context\Tests\ParamConverter\DateTime'));
        $this->assertFalse($this->converter->supports(null, 'stdClass'));
    }

    public function testConvert()
    {
        $datetime = $this->converter->convert('2010-01-01', 'DateTime', array());
        $this->assertInstanceOf('DateTime', $datetime);
        $this->assertEquals('2010-01-01', $datetime->format('Y-m-d'));
    }
}

class DateTime extends \DateTime
{
}
