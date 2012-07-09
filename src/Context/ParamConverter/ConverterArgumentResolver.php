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

use Context\Invocation\ContextInvocation;

/**
 * Argument Resolver that uses converters to transform request
 * to model-request objects.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ConverterArgumentResolver implements ArgumentResolver
{
    private $converters = array();

    public function __construct()
    {
        $this->converters = new ConverterBag();
    }

    public function addConverter(ParamConverter $converter)
    {
        $this->converters->add($converter);
        $converter->setConverterBag($this->converters);
    }

    public function resolve(ContextInvocation $invocation)
    {
        $options    = $invocation->getOptions();
        $context    = $options['context'];
        $params     = $options['params'];
        $data       = $options['data'];
        $args       = $options['arguments'];
        $interfaces = $options['interfaces'];

        if (is_string($data)) {
            $data = new RequestData(array(), $data);
        } else if (is_array($data)) {
            $data = new RequestData($data, null);
        }

        if (is_array($context)) {
            $r = new \ReflectionMethod($context[0], $context[1]);
        } else {
            $r = new \ReflectionFunction($context);
        }

        if ( ! $params) {
            foreach ($r->getParameters() as $parameter) {
                $params[$parameter->getPosition()] = $data->has($parameter->getName())
                    ? $data->get($parameter->getName())
                    : ($data->has($parameter->getPosition())
                        ? $data->get($parameter->getPosition())
                        : null
                    );
            }
        }

        foreach ($r->getParameters() as $parameter) {
            $pos      = $parameter->getPosition();
            $argument = isset($args[$pos])
                ? $args[$pos]
                : Argument::fromReflection($parameter);
            $class    = $argument->getClass();

            if (isset($interfaces[$class])) {
                $argument->setClass($interfaces[$class]);
            }

            if ($class && $params[$pos] instanceof $class) {
                continue;
            }

            $params[$pos] = $this->converters->convert($params[$pos], $argument, $data);

            if ( $params[$pos] === null) {
                if ( ! $argument->isOptional()) {
                    throw new \RuntimeException("Could not resolve value for argumente named '" . $argument->getName() . "'");
                }

                $params[$pos] = $argument->getDefaultValue();
            }
        }

        return $params;
    }

}

