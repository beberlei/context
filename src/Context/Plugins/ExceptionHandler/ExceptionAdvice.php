<?php

namespace Context\Plugins\ExceptionHandler;

use Context\Invocation\Advice;
use Context\Invocation\ContextInvocation;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Exception;

/**
 * ExceptionHandler
 *
 * ExceptionHandler catches all exceptions from deeper invocations
 * and starts calling all registered exception handlers. If no exception
 * handler is found a generic {@see
 * Context\Plugins\ExceptionHandler\ContextException} is thrown.
 *
 * You can disable the exception handler (for debugging or during development)
 * by passing the "disable_exception_handler" option during the Context Execution.
 */
class ExceptionAdvice implements Advice
{
    /**
     * @var array
     */
    private $handlers = array();

    public function addExceptionHandler($handler)
    {
        $this->handlers[] = $handler;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'disable_exception_handler' => false,
        ));
    }

    public function around(ContextInvocation $context)
    {
        $options = $context->getOptions();

        if ($options['disable_exception_handler']) {
            return $context->invoke();
        }

        try {
            return $context->invoke();
        } catch(Exception $e) {
            return $this->catchException($e);
        }
    }

    private function catchException(Exception $e)
    {
        foreach ($this->handlers as $handler) {
            if ($handler instanceof \Closure) {
                return $handler($e);
            } else if ($handler->supports($e)) {
                return $handler->catchException($e);
            }
        }
        throw new ContextException("An error occured inside your application.", null, $e);
    }
}

