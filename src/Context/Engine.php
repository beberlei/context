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

namespace Context;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * The context engine, translating between
 * application and model request/response objects.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Engine
{
    /**
     * @var Advice[]
     */
    private $advices = array();

    public function __construct()
    {
        $this->advices[] = new Invocation\InvocationAdvice();
    }

    /**
     * Execute a context from within the Engine.
     *
     * @param array $options
     * @return mixed
     */
    public function execute(array $options)
    {
        $options = $this->resolveOptions($options);
        return $this->createContextInvocation($options)->invoke();
    }

    private function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        foreach ($this->advices as $advice) {
            $advice->setDefaultOptions($resolver);
        }
        return $resolver->resolve($options);
    }

    private function createContextInvocation($options)
    {
        return new Invocation\ContextInvocation($options, $this->advices);
    }
}

