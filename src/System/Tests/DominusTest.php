<?php

namespace Dominus\System\Tests;

use Dominus\System\Exceptions\TestFailedAssertionException;

class DominusTest
{
    private int $assertions = 0;

    public function _dominusTest_getAssertionsCount(): int
    {
        return $this->assertions;
    }

    /**
     * @throws TestFailedAssertionException
     */
    protected function assert(bool $assertion, string $description = ''): void
    {
        ++$this->assertions;
        if(!$assertion)
        {
            throw new TestFailedAssertionException("Failed assertion" . ($description ? ": $description" : ''));
        }
    }
}