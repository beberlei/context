<?php
namespace Context\Tests;

use Context\Engine;

class EngineTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function executeWithEmptyArgumentsShouldThrowException()
    {
        $engine = new Engine();

        $this->setExpectedException('Symfony\Component\OptionsResolver\Exception\MissingOptionsException');
        $engine->execute(array());
    }

    /**
     * @test
     */
    public function executeWithContextOnlyShouldInvokeContextCallback()
    {
        $engine = new Engine();
        $mock = $this->getMock('ContextOnlyMock', array('execute'));
        $mock->expects($this->once())->method('execute');

        $engine->execute(array(
            'context' => array($mock, 'execute')
        ));
    }

    /**
     * @test
     */
    public function executeWithContextAndParamsShouldInvokeContextWithParams()
    {
        $engine = new Engine();
        $mock = $this->getMock('ContextParamsMock', array('execute'));
        $mock->expects($this->once())->method('execute')->with($this->equalTo(1), $this->equalTo("string"));

        $engine->execute(array(
            'context' => array($mock, 'execute'),
            'params'  => array(1, "string"),
        ));
    }
}
