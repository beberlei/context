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
 * Grabs services from a registry and injects them as parameters to the model.
 */
abstract class ServiceRegistryConverter extends AbstractParamConverter
{
    abstract protected function isService($className);
    abstract protected function getService($className);

    public function supports($value, Argument $parameter, RequestData $data)
    {
        return $value === null && $parameter->getClass() && $this->isService($parameter->getClass());
    }

    public function convert($value, Argument $parameter, RequestData $data)
    {
        return $this->getService($parameter->getClass());
    }

    public function getPriority()
    {
        return 0;
    }
}

