<?php
/**
 * Context
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Context\Plugins\Symfony2\ParamConverter;

use Context\ParamConverter\AbstractParamConverter;
use Context\ParamConverter\Argument;
use Context\ParamConverter\RequestData;

use JMS\SerializerBundle\Serializer\SerializerInterface;
use Metadata\MetadataFactoryInterface;

/**
 * Converter to be used with JMS Serializer Bundle. It makes the assumption
 * that the raw content of the RequestData contains the serialized version
 * of the object. This means implictly that you can only map ONE object with
 * this converter, as the raw content data source only exists once.
 *
 * Priority-wise this param converter runs AFTER the object converter, so
 * that accepting multiple objects is possible, if only one of them is from
 * the raw content.
 */
class SerializerConverter extends AbstractParamConverter
{
    /**
     * @var \JMS\SerializerBundle\Serializer\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Metadata\MetadataFactoryInterface
     */
    private $metadataFactory;

    private $defaultFormat;

    public function __construct(SerializerInterface $serializer, MetadataFactoryInterface $factory, $defaultFormat = 'xml')
    {
        $this->serializer      = $serializer;
        $this->metadataFactory = $factory;
        $this->defaultFormat   = $defaultFormat;
    }

    public function supports($value, Argument $parameter, RequestData $data)
    {
        return $data->hasRawInput()
            && $parameter->getClass()
            && $this->metadataFactory->getMetadataForClass($parameter->getClass()) !== null;
    }

    public function convert($value, Argument $parameter, RequestData $data)
    {
        return $this->serializer->deserialize(
            $data->getRawInput(),
            $parameter->getClass(),
            $data->getMetadata('format', $this->defaultFormat)
        );
    }

    public function getPriority()
    {
        return 0;
    }
}

