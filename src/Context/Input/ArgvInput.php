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
use Context\ParamConverter\RequestData;

class ArgvInput implements InputSource
{
    public function hasData(array $options)
    {
        return php_sapi_name() === 'cli' && isset($_SERVER['argv']);
    }

    public function createData(array $options)
    {
        return new RequestData(array_slice($_SERVER['argv'], 1), null);
    }

    public function addDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
}

