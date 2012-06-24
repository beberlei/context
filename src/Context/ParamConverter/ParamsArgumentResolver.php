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

use Context\Invocation\ContextInvocation;

/**
 * No conversion, just return the explicitly passed params.
 */
class ParamsArgumentResolver implements ArgumentResolver
{
    public function resolve(ContextInvocation $invocation)
    {
        $options = $invocation->getOptions();
        return $options['params'];
    }
}

