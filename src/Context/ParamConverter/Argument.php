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

use ReflectionParameter;

/**
 * Argument for Context
 */
class Argument
{
    private $name;
    private $class;
    private $isArray;
    private $isOptional;
    private $defaultValue;

    static public function fromReflection(ReflectionParameter $param)
    {
        return new self(
            $param->getName(),
            $param->getClass() ? $param->getClass()->getName() : null,
            $param->isArray(),
            $param->isOptional(),
            $param->isOptional() ? $param->getDefaultValue() : null
        );
    }

    public function __construct($name, $class, $isArray = false, $isOptional = false, $defaultValue = null)
    {
        $this->name         = $name;
        $this->class        = $class;
        $this->isArray      = $isArray;
        $this->isOptional   = $isOptional;
        $this->defaultValue = $defaultValue;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function isArray()
    {
        return $this->isArray;
    }

    public function isOptional()
    {
        return $this->isOptional;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}

