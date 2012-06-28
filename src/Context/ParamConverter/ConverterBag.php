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

class ConverterBag
{
    private $converters = array();

    public function add(ParamConverter $converter)
    {
        $this->converters[$converter->getPriority()][] = $converter;
    }

    /**
     * Returns all registered param converters.
     *
     * @return array An array of param converters
     */
    public function all()
    {
        krsort($this->converters);
        $converters = array();

        foreach ($this->converters as $all) {
            $converters = array_merge($converters, $all);
        }

        return $converters;
    }

    public function convert($value, Argument $argument, $data)
    {
        foreach ($this->all() as $converter) {
            if ($converter->supports($value, $argument)) {
                $convertedValue = $converter->convert($value, $argument, $data);
                if ($convertedValue !== null) {
                    return $convertedValue;
                }
            }
        }

        return $value;
    }
}

