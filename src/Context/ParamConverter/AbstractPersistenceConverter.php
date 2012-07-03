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
 * Abstract Persistence ParamConverter.
 *
 * Allows you to pass in an object to your service method, as if it was just
 * retrieved from the database based on a classname + id lookup. This is
 * sometimes helpful when you want to avoid injecting or calling another
 * service that allows you access to this object.
 *
 * The converter will look for parameters named like the argument or named "id"
 * (in this order) and delegate a call to your persistence layer to find
 * an object. The {@see find()} method should return null if no object was
 * found.
 */
abstract class AbstractPersistenceConverter extends AbstractParamConverter
{
    abstract protected function isPersistentClass($className);

    abstract protected function find($className, $id);

    public function supports($value, Argument $parameter, RequestData $data)
    {
        return $parameter->getClass()
            && $this->isPersistentClass($parameter->getClass())
            && ($data->has('id') || $data->has($parameter->getName()));
    }

    public function convert($value, Argument $parameter, RequestData $data)
    {
        $id = $data->has($parameter->getName())
            ? $data->get($parameter->getName())
            : $data->get('id');

        return $this->find($parameter->getClass(), $id);
    }

    public function getPriority()
    {
        return 2;
    }
}

