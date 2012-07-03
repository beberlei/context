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

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Context\Invocation\Advice;
use Context\Invocation\ContextInvocation;

/**
 * Symfony Form Advice. If a formType isset and a form related content-type
 * is detected in the request, binding and validation takes place.
 * If the validation fails the "invalid" option is replaced as the "context"
 * instead and executed. If the validation succeeds the context is executed
 * and then the "success" option is executed.
 *
 * Three additional data values are set by this advice:
 *
 * - 'form' contains the Symfony Form instance
 * - 'formData' contains the data bound to the form
 * - 'formResponse' contains the response from the context. Available in the
 *   'success' callback.
 */
class FormAdvice implements Advice
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $formFactory;

    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function around(ContextInvocation $invocation)
    {
        $options = $invocation->getOptions();

        if ( ! ($options['form_type'] instanceof FormTypeInterface)) {
            return $invocation->invoke();
        }

        $contentType = $options['request']->server->get('Content-Type');
        if ($contentType != 'application/x-www-form-urlencoded' &&
            strpos($contentType, 'multipart/form-data') !== 0) {

            return $invocation->invoke();
        }

        $data = $options['data'];
        $form = $this->formFactory->createForm($options['form_type'], $options['form_data'], $options['form_options']);

        $form->bindRequest($options['request']);
        $data->set('form', $form);
        $data->set('form_data', $form->getData());

        if ( ! $form->isValid()) {
            $invocation->setOption('context', $options['invalid']);

            return $invocation->invoke();
        }

        return $invocation->invoke();
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'form_type'    => null,
            'form_options' => array(),
            'form_data'    => null,
            'invalid'      => null,
            'request'      => null,
        ));
    }
}

