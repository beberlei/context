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

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityManager;

use Context\Invocation\ContextInvocation;
use Context\Invocation\Advice;

/**
 * Transaction advice wraps around execution
 */
class TransactionAdvice implements Advice
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'tx' => null,
        ));
    }

    public function around(ContextInvocation $context)
    {
        if ( ! $context->getOption('tx')) {
            return $context->invoke();
        }

        $this->entityManager->beginTransaction();

        try {
            $response = $context->invoke();

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $reponse;

        } catch(\Exception $e) {
            $this->entityManager->rollBack();
            throw $e;
        }
    }
}

