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

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Context\ParamConverter\ArgumentResolver;
use Context\ParamConverter\ParamsArgumentResolver;

/**
 * Advice that invokes the context with parameters.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class InvocationAdvice implements Advice
{
    /**
     * @var ArgumentResolver
     */
    private $resolver;

    public function __construct(ArgumentResolver $resolver = null)
    {
        $this->resolver = $resolver ?: new ParamsArgumentResolver();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('context'));
        $resolver->setDefaults(array(
            'params' => array(),
            'data'   => array(),
        ));
        return $resolver;
    }

    public function around(ContextInvocation $context)
    {
        $options = $context->getOptions();
        $params  = $this->resolver->resolve($context);

        return call_user_func_array($options['context'], $params);
    }
}

