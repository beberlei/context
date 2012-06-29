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

/**
 * Merge $_GET and $_POST superglobals into pool of data.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class PhpSuperGlobalsInput implements InputSource
{
    public function hasData(array $options)
    {
        return (php_sapi_name() !== "cli");
    }

    public function addData(array $options, array $data)
    {
        return array_merge($data, $_GET, $_POST);
    }

    public function addDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
}

