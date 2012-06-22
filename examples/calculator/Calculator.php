<?php
class Calculator
{
    public function add($x, $y)
    {
        if ( ! is_numeric($x) || ! is_numeric($y)) {
            throw new \InvalidArgumentException("Input values are not numeric.");
        }

        return $x + $y;
    }

    public function square($x)
    {
        if ( ! is_numeric($x)) {
            throw new \InvalidArgumentException("Input values are not numeric.");
        }

        return $x * $x;
    }

    public function statistics(array $numbers)
    {
        if (count($numbers) > count(array_filter($numbers, 'is_numeric'))) {
            throw new \InvalidArgumentException("Input values are not numeric.");
        }

        $average           = array_sum($numbers) / count($numbers);
        $square            = array_map(array($this, 'square'), $numbers);
        $variance          = (array_sum($square) / count($square)) - $this->square($average);
        $standardDeviation = sqrt($variance);

        return array(
            'numbers'           => $numbers,
            'average'           => $average,
            'variance'          => $variance,
            'standardDeviation' => $standardDeviation,
        );
    }
}
