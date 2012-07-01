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

namespace Context\Plugins\Symfony2\Input;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Context\Input\InputSource;
use Context\ParamConverter\RequestData;

class RequestInput implements InputSource
{
    public function hasData(array $options)
    {
        return ($options['request'] instanceof Request);
    }

    public function createData(array $options)
    {
        return new RequestData(array_merge(
            $options['request']->query->all(),
            $options['request']->request->all(),
            $options['request']->attributes->all()
        ), $options['request']->getContent(), array(
            'format' => $options['request']->attributes->get('_format'),
        ));
    }

    public function addDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->replaceDefaults(array('request' => null));
    }
}

