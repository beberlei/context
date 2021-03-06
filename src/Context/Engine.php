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

use Context\Input\InputAdvice;
use Context\Input\InputSource;

use Context\ParamConverter\ArgumentResolver;
use Context\ParamConverter\ConverterArgumentResolver;
use Context\ParamConverter\ParamConverter;

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

    private $inputAdvice;

    private $exceptionAdvice;

    /**
     * @var ArgumentResolver
     */
    private $argumentResolver;

    private $defaultOptions = array();

    public function __construct(ArgumentResolver $resolver = null, InputAdvice $inputAdvice = null, ExceptionAdvice $exAdvice = null)
    {
        $this->inputAdvice     = $inputAdvice ?: new InputAdvice();
        $this->exceptionAdvice = $exAdvice ?: new ExceptionAdvice();
        $this->resolver        = $resolver ?: new ConverterArgumentResolver();
    }

    /**
     * Set default options to be merged into every invocations options.
     *
     * @param array $options
     */
    public function setDefaultOptions(array $options)
    {
        $this->defaultOptions = $options;
    }

    /**
     * Add default options to be merged into every invocations options.
     */
    public function addDefaultOptions(array $options)
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Add a new input source
     *
     * @param InputSource $source
     */
    public function addInputSource(InputSource $source)
    {
        $this->inputAdvice->addSource($source);
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
     * @param ParamConverter $converter
     */
    public function addParamConverter(ParamConverter $converter)
    {
        $this->resolver->addConverter($converter);
    }

    /**
     * Wrap service
     *
     * @param object $service
     * @param array $options
     *
     * @return object
     */
    public function wrap($service, array $options)
    {
        return new Proxy($this, $service, $options);
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
        $advices = array_merge(
            array($this->inputAdvice, $this->exceptionAdvice),
            $this->advices
        );

        $options = array_merge($this->defaultOptions, $options);
        $options = $this->resolveOptions($options, $advices);

        return new Invocation\ContextInvocation($options, $advices, $this->resolver);
    }

    private function resolveOptions(array $options, array $advices)
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('context'));
        $resolver->setDefaults(array(
            'params'     => array(),
            'data'       => array(),
            'interfaces' => array(),
            'arguments'  => array(),
        ));
        foreach ($advices as $advice) {
            $advice->setDefaultOptions($resolver);
        }
        return $resolver->resolve($options);
    }
}

