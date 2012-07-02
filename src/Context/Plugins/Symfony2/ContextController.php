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

namespace Context\Plugins\Symfony2;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Context\Engine;

use Context\ParamConverter\ConverterArgumentResolver;
use Context\ParamConverter\DateTimeConverter;
use Context\ParamConverter\ObjectConverter;
use Context\ParamConverter\InstanceConverter;

use Context\Plugins\Symfony2\Input\RequestInput;
use Context\Plugins\Symfony2\ParamConverter\SerializerConverter;
use Context\Plugins\Symfony2\FormAdvice;

use Context\Plugins\Doctrine2\TransactionAdvice;

use Exception;

/**
 * Context Controller
 */
abstract class ContextController extends Controller
{
    /**
     * Execute a context
     *
     * @param array $options
     * @return mixed
     */
    public function context(array $options)
    {
        $resolver = new ConverterArgumentResolver();
        $resolver->addConverter(new DateTimeConverter());
        $resolver->addConverter(new ObjectConverter());
        $resolver->addConverter(new InstanceConverter());
        $resolver->addConverter(new SerializerConverter($this->container->get('serializer')));

        $engine = new Engine($resolver);
        $engine->addExceptionHandler(array($this, 'onError'));
        $engine->addInputSource(new RequestInput());
        $engine->addAdvice(new TransactionAdvice($this->container->get('doctrine.orm.default_entity_manager')));
        $engine->addAdvice(new FormAdvice($this->container->get('form.factory')));
        $engine->setDefaultOptions(array(
            'request' => $this->getRequest()
        ));

        return $engine->execute($options);
    }

    public function onError(Exception $e)
    {
    }
}

