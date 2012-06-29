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
use Context\Input\InputSource;

class SerializerInput implements InputSource
{
    private $serializer;

    public function hasData(array $options)
    {
    }

    public function addData(array $options, array $data)
    {
        return array_merge(
            $data,
            $options['request']->query->all(),
            $options['request']->request->all(),
            $options['request']->attributes->all()
        );
    }

    public function addDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->replaceDefaults(array('request' => null));
    }
}

