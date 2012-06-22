<?php

require_once "Calculator.php";

class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    private $calculator;

    public function setUp()
    {
        $this->calculator = new Calculator();
    }

    static public function dataAdd()
    {
        return array(
            array(0, 0, 0),
            array(1, 1, 2),
            array(10, 10, 20),
            array(0, 2, 2),
            array(10, 100, 110),
            array(1, -1, 0),
        );
    }

    /**
     * @dataProvider dataAdd
     */
    public function testAdd($x, $y, $z)
    {
        $this->assertEquals($z, $this->calculator->add($x, $y));
    }

    static public function dataSquare()
    {
        return array(
            array(0, 0),
            array(1, 1),
            array(2, 4),
            array(4, 16),
            array(-4, 16),
            array(5, 25),
        );
    }

    /**
     * @dataProvider dataSquare
     */
    public function testSquare($x, $x2)
    {
        $this->assertEquals($x2, $this->calculator->square($x));
    }

    public function testStatisticsSixSidedDie()
    {
        $stats = $this->calculator->statistics(array(1, 2, 3, 4, 5, 6));

        $this->assertEquals(3.5, $stats['average']);
        $this->assertEquals(2.9166666667, $stats['variance']);
        $this->assertEquals(sqrt(2.916666667), $stats['standardDeviation']);
    }
}
