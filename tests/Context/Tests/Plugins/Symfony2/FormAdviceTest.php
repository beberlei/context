<?php
namespace Context\Tests\Plugins\Symfony2;

use Context\Invocation\ContextInvocation;
use Context\Invocation\InvocationAdvice;
use Context\Tests\TestCase;
use Context\Plugins\Symfony2\FormAdvice;
use Context\ParamConverter\RequestData;
use Symfony\Component\HttpFoundation\Request;

class FormAdviceTest extends TestCase
{
    public function testAround()
    {
        $factory = $this->mock('Symfony\Component\Form\FormFactoryInterface');
        $advice  = new FormAdvice($factory);

        $request = new Request(array(), array(), array(), array(), array(), array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ));

        $this->assertEquals('application/x-www-form-urlencoded', $request->server->get('Content-Type'));

        $type = $this->mock('Symfony\Component\Form\FormTypeInterface');
        $form = $this->mock('Symfony\Component\Form\Form');
        $form->shouldReceive('bindRequest')->with($request);
        $form->shouldReceive('getData')->andReturn(new \stdClass);
        $form->shouldReceive('isValid')->andReturn(true);

        $factory->shouldReceive('createForm')
                ->andReturn($form);

        $context = new ContextInvocation();
        $context->setOptions(array(
            'form_type'    => $type,
            'request'      => $request,
            'form_data'    => null,
            'form_options' => array(),
            'success'      => function() {},
            'invalid'      => function() {},
            'params'       => array(),
            'data'         => new RequestData,
            'context'      => function() {}
        ));
        $advice->around($context);
    }
}
