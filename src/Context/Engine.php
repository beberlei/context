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

use Context\Invocation\InvocationAdvice;
use Context\Invocation\Advice;
use Context\Plugins\ExceptionHandler\ExceptionAdvice;

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
        $this->advices[] = new ExceptionAdvice();
    }

    /**
     * Add advice to the invocation advice stack.
     *
     * This advice will be appended to the stack of advices.
     *
     * @param Advice $advice
     */
    public function addAdvice(Advice $advice)
    {
        $this->advices[] = $advice;
    }

    /**
     * Add exception handler to ExceptionAdvice if found.
     *
     * @param Closure|ExceptionHandler $handler
     */
    public function addExceptionHandler($handler)
    {
        foreach ($this->advices as $advice) {
            if ($advice instanceof ExceptionAdvice) {
                $advice->addExceptionHandler($handler);
                break;
            }
        }
    }

    /**
     * Execute a context from within the Engine.
     *
     * @param array $options
     * @return mixed
     */
    public function execute(array $options)
    {
        return $this->createContextInvocation($options)->invoke();
    }

    private function createContextInvocation($options)
    {
        $advices = array_merge($this->advices, array(new InvocationAdvice()));
        $options = $this->resolveOptions($options, $advices);

        return new Invocation\ContextInvocation($options, $advices);
    }

    private function resolveOptions(array $options, array $advices)
    {
        $resolver = new OptionsResolver();
        foreach ($advices as $advice) {
            $advice->setDefaultOptions($resolver);
        }
        return $resolver->resolve($options);
    }
}

