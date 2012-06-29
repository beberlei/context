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

namespace Context\Input;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Context\Invocation\Advice;
use Context\Invocation\ContextInvocation;

/**
 * Advice that handles to register additional data automatically
 * by checking a list of registered input source.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class InputAdvice implements Advice
{
    private $sources = array();

    public function addSource(InputSource $source)
    {
        $this->sources[] = $source;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        foreach ($this->sources as $source) {
            $source->addDefaultOptions($resolver);
        }
    }

    public function around(ContextInvocation $context)
    {
        $options = $context->getOptions();

        foreach ($this->sources as $source) {
            if ($source->hasData()) {
                $options['data'] = $source->addData($options['data']);
            }
        }

        $context->setOptions($options);

        return $context->invoke();
    }
}

