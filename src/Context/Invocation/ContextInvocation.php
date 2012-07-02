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

namespace Context\Invocation;

use Context\Exception\RuntimeException;
use Context\ParamConverter\ArgumentResolver;
use Context\ParamConverter\ParamsArgumentResolver;

/**
 * Wraps the invocation and all advices that should be applied
 * on the invocation.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class ContextInvocation
{
    private $options;
    private $advices;
    private $resolver;

    public function __construct(array $options = array(), array $advices = array(), ArgumentResolver $resolver = null)
    {
        $this->options  = $options;
        $this->advices  = $advices;
        $this->resolver = $resolver ?: new ParamsArgumentResolver();
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function invoke()
    {
        $advice = array_shift($this->advices);

        if ( ! $advice) {
            $params = $this->resolver->resolve($this);
            return call_user_func_array($this->options['context'], $params);
        }

        return $advice->around($this, $this->options);
    }
}

