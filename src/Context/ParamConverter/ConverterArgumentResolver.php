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

class ConverterArgumentResolver implements ArgumentResolver
{
    private $converters = array();

    public function addConverter(ParamConverter $converter)
    {
        $this->converters[$converter->getPriority()][] = $converter;
    }

    public function resolve(ContextInvocation $invocation)
    {
        $options = $invocation->getOptions();
        $context = $options['context'];
        $params  = $options['params'];
        $data    = $options['data'];

        if (is_array($context)) {
            $r = new \ReflectionMethod($context[0], $context[1]);
        } else {
            $r = new \ReflectionFunction($context);
        }

        if ( ! $params) {
            foreach ($r->getParameters() as $parameter) {
                $params[$parameter->getPosition()] =
                    isset($data[$parameter->getName()]) ?
                    $data[$parameter->getName() : null;
            }
        }

        $converters = $this->all();
        foreach ($r->getParameters() as $parameter) {
            $pos      = $parameter->getPosition();
            $argument = Argument::fromReflection($parameter);
            $class    = $argument->getType();

            if ($class && $params[$pos] instanceof $class) {
                continue;
            }

            $targetType = $class ?: ($argument->isArray() ? 'array' : 'scalar');

            foreach ($converters as $converter) {
                if ($converter->supports($params[$pos], $targetType)) {
                    $value = $converter->convert($params[$pos], $targetType, $data);
                    if ($value !== null) {
                        $params[$pos] = $value;
                        break 2;
                    }
                }
            }

            if ( ! $argument->isOptional()) {
                throw new \RuntimeException("Could not resolve value for argumente named '" . $argument->getName() . "'");
            }

            $params[$pos] = $argument->getDefaultValue();
        }
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
}

