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

namespace Context\Plugins\Doctrine2;

use Doctrine\Common\Persistence\ObjectManager;

class EntityConverter extends AbstractPersistenceConverter
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $manager)
    {
        $this->objectManager = $manager;
    }

    protected function isPersistentClass($className)
    {
        return ! $this->objectManager->getMetadataFactory()->isTransient($className);
    }

    protected function find($className, $id)
    {
        return $this->objectManager->find($className, $id);
    }
}

