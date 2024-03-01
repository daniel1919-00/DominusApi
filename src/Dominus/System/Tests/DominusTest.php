<?php

namespace Dominus\System\Tests;

use Dominus\System\Exceptions\TestFailedAssertionException;

class DominusTest
{
    public int $assertions = 0;

    /**
     * @throws TestFailedAssertionException
     */
    protected function assert(bool $assertion, string $description = ''): void
    {
        ++$this->assertions;
        if(!$assertion)
        {
            throw new TestFailedAssertionException($description ?: 'Assertion Failed!');
        }
    }
}