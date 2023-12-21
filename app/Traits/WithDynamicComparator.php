<?php

namespace App\Traits;

trait WithDynamicComparator
{
    private array $operatorToMethod = [
        '=='  => 'equal',
        '===' => 'identical',
        '!='  => 'notEqual',
        '!==' => 'notIdentical',
        '>'   => 'greaterThan',
        '<'   => 'lessThan',
        '>='  => 'greaterThanOrEqualTo',
        '<='  => 'lessThanOrEqualTo',
    ];


    protected function is ($value_a, $operation, $value_b): bool
    {
        if ($method = $this->operatorToMethod[$operation]) {
            return $this->$method($value_a, $value_b);
        }
        throw new \LogicException("Undefined operator for comparison.");
    }

    private function equal ($value_a, $value_b): bool
    {
        return $value_a == $value_b;
    }

    private function identical ($value_a, $value_b): bool
    {
        return $value_a === $value_b;
    }

    private function notEqual ($value_a, $value_b): bool
    {
        return $value_a != $value_b;
    }

    private function notIdentical ($value_a, $value_b): bool
    {
        return $value_a !== $value_b;
    }

    private function greaterThan ($value_a, $value_b): bool
    {
        return $value_a > $value_b;
    }

    private function lessThan ($value_a, $value_b): bool
    {
        return $value_a < $value_b;
    }

    private function greaterThanOrEqualTo ($value_a, $value_b): bool
    {
        return $value_a >= $value_b;
    }

    private function lessThanOrEqualTo ($value_a, $value_b): bool
    {
        return $value_a <= $value_b;
    }

}