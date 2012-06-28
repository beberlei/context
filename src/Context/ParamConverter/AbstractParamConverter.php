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

abstract class AbstractParamConverter implements ParamConverter
{
    protected $converters;

    abstract public function supports($value, Argument $parameter);
    abstract public function convert($value, Argument $parameter, $data);
    abstract public function getPriority();

    public function setConverterBag(ConverterBag $converterBag)
    {
        $this->converters = $converterBag;
    }
}

