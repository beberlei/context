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

namespace Context\ParamConverter;

use Context\ParamConverter\Argument;
use Context\ParamConverter\RequestData;

class StringToArrayConverter extends AbstractParamConverter
{
    public function supports($value, Argument $parameter, RequestData $data)
    {
        return is_string($value) && $parameter->isArray();
    }

    public function convert($value, Argument $parameter, RequestData $data)
    {
        return array_map('trim', explode(",", $value));
    }

    public function getPriority()
    {
        return 0;
    }
}

