<?php
namespace Context\Tests\Plugins\ExceptionHandler;

use Exception;
use Context\Plugins\ExceptionHandler\ExceptionAdvice;
use Context\Plugins\ExceptionHandler\ExceptionHandler;

class ExceptionAdviceTest extends \PHPUnit_Framework_TestCase
{
    public function testDisabledExceptionHandler()
    {
        $advice = new ExceptionAdvice();
        $advice->addExceptionHandler(function () {
            throw new \RuntimeException("Handler!");
        });

        $context = $this->createContextMock();
        $context->setOptions(array('disable_exception_handler' => true));

        $this->setExpectedException('RuntimeException', 'Invocation!');
        $advice->around($context);
    }

    public function testEnabledExceptionHandler()
    {
        $advice = new ExceptionAdvice();
        $advice->addExceptionHandler(function () {
            throw new \RuntimeException("Handler!");
        });
        $context = $this->createContextMock();
        $context->setOptions(array('disable_exception_handler' => false));

        $this->setExpectedException('RuntimeException', 'Handler!');
        $advice->around($context);
    }

    public function testEnabledExceptionAdviceButNoHandlers()
    {
        $advice = new ExceptionAdvice();

        $context = $this->createContextMock();
        $context->setOptions(array('disable_exception_handler' => false));

        $this->setExpectedException('Context\Plugins\ExceptionHandler\ContextException', 'An error occured inside your application.');
        $advice->around($context);
    }

    public function testEnabledExceptionAdviceWithInterfaceHandler()
    {
        $advice = new ExceptionAdvice();
        $advice->addExceptionHandler(new CustomExceptionHandler());

        $context = $this->createContextMock();
        $context->setOptions(array('disable_exception_handler' => false));

        $this->setExpectedException('RuntimeException', 'CustomHandler!');
        $advice->around($context);

    }

    public function createContextMock()
    {
        $context = $this->getMock('Context\Invocation\ContextInvocation', array('invoke'));
        $context->expects($this->at(0))->method('invoke')->will($this->throwException(new \RuntimeException("Invocation!")));
        return $context;
    }
}

class CustomExceptionHandler implements ExceptionHandler
{
    function supports(Exception $e)
    {
        return true;
    }
    function catchException(Exception $e)
    {
        throw new \RuntimeException("CustomHandler!");
    }
}
