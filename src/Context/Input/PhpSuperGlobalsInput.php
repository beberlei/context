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

    public function createData(array $options)
    {
        $content = file_get_contents("php://stdin");

        if (isset($_SERVER['HTTP_CONTENT_TYPE']) &&
            in_array($_SERVER['REQUEST_METHOD'], array('PUT', 'PATCH', 'DELETE')) &&
            $_SERVER['HTTP_CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {

            parse_str($content, $_POST);
        }

        $parameters = array_merge($_GET, $_POST);

        return new RequestData($parameters, $content);
    }

    public function addDefaultOptions(OptionsResolverInterface $resolver)
    {
    }
}

