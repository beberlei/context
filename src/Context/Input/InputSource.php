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

interface InputSource
{
    function hasData(array $options);
    function addData(array $options, array $data);
    function addDefaultOptions(OptionsResolverInterface $resolver);
}

