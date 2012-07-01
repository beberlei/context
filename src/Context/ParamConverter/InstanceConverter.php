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

/**
 * Maps already existing instances in the request data to
 * arguments on the context method.
 */
class InstanceConverter extends AbstractParamConverter
{
    public function supports($value, Argument $parameter, RequestData $data)
    {
        return $value === null && $parameter->getClass() !== null;
    }

    public function convert($value, Argument $parameter, RequestData $data)
    {
        $checkClass = $parameter->getClass();

        foreach ($data->all() as $requestValue) {
            if ($requestValue instanceof $checkClass) {
                return $requestValue;
            }
        }

        return null;
    }

    public function getPriority()
    {
        return 0;
    }
}

