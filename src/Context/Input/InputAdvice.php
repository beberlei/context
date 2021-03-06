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
use Context\ParamConverter\RequestData;

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

        if (empty($options['data'])) {
            $options['data'] = $this->extractDataFromSources($options);
        }

        $context->setOptions($options);

        return $context->invoke();
    }

    private function extractDataFromSources($options)
    {
        foreach ($this->sources as $source) {
            if ($source->hasData($options)) {
                return $source->createData($options);
            }
        }

        return new RequestData(array(), null);
    }
}

