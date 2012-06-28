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

namespace Context\ParamConverter;

class ObjectConverter extends AbstractParamConverter
{
    public function supports($value, Argument $argument)
    {
        return $argument->getClass() !== null && is_array($value);
    }

    public function convert($value, Argument $argument, $data)
    {
        $targetClass = $argument->getClass();
        $reflClass   = new \ReflectionClass($targetClass);

        $constructor = $reflClass->getConstructor();
        $args        = array();
        $properties  = array();

        foreach ($reflClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();

            if (isset($value[$propertyName])) {
                $properties[$propertyName] = $value[$propertyName];
            }
        }

        if ($constructor) {
            foreach ($constructor->getParameters() as $parameter) {
                $argValue = null;

                if (isset($value[ $parameter->getName()] )) {
                    $argValue = $value[$parameter->getName()];
                    unset($properties[$parameter->getName()]);
                } else if ($parameter->isOptional()) {
                    $argValue = $parameter->getDefaultValue();
                }

                $constructorArg = Argument::fromReflection($parameter);
                $args[] = $this->converters->convert($argValue, $constructorArg, null);
            }
        }

        $object = $reflClass->newInstanceArgs($args);
        foreach ($properties as $propertyName => $value) {
            $object->$propertyName = $value;
        }

        return $object;
    }

    public function getPriority()
    {
        return 0;
    }
}

