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

class DateTimeConverter implements ParamConverter
{
    public function supports($value, Argument $argument)
    {
        $targetType = $argument->getClass();
        return $targetType === 'DateTime' || is_subclass_of($targetType, 'DateTime');
    }

    public function convert($value, Argument $argument, $data)
    {
        $targetType = $argument->getClass();
        return new $targetType($value);
    }

    public function getPriority()
    {
        return 3;
    }
}

