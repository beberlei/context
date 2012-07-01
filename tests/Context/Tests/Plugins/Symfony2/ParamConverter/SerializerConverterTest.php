<?php
namespace Context\Tests\Plugins\Symfony2\ParamConverter;

use Context\Plugins\Symfony2\ParamConverter\SerializerConverter;
use Context\ParamConverter\RequestData;
use Context\ParamConverter\Argument;
use Context\Tests\TestCase;

class SerializerConverterTest extends TestCase
{
    private $serializer;
    private $factory;
    private $converter;

    public function setUp()
    {
        $this->serializer = $this->mock('JMS\SerializerBundle\Serializer\SerializerInterface');
        $this->factory = $this->mock('Metadata\MetadataFactoryInterface');
        $this->converter = new SerializerConverter($this->serializer, $this->factory, 'xml');
    }

    public function testSupports()
    {
        $data     = new RequestData(array(), '<xml />');
        $function = new \ReflectionFunction(__NAMESPACE__ . '\\deserializeClass');
        $params   = $function->getParameters();
        $argument = Argument::fromReflection($params[0]);

        $this->factory->shouldReceive('getMetadataForClass')->with('stdClass')->andReturn(true);

        $this->assertTrue($this->converter->supports(null, $argument, $data));
    }

    public function testConvert()
    {
        $data     = new RequestData(array(), '<xml />');
        $function = new \ReflectionFunction(__NAMESPACE__ . '\\deserializeClass');
        $params   = $function->getParameters();
        $argument = Argument::fromReflection($params[0]);

        $this->serializer->shouldReceive('deserialize')->with('<xml />', 'stdClass', 'xml')->andReturn(new \stdClass);

        $this->assertInstanceOf('stdClass', $this->converter->convert(null, $argument, $data));
    }
}

function deserializeClass(\stdClass $foo)
{
}
