<?php

namespace Dominus\System\Tests;

use Exception;
use ReflectionClass;
use Dominus\System\Attributes\TestName;
use Dominus\System\Attributes\TestRequestParameters;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Injector;
use Dominus\System\Request;
use ReflectionMethod;
use function basename;
use function floor;
use function hrtime;
use function is_dir;
use function is_object;
use function is_subclass_of;
use function log;
use function memory_get_peak_usage;
use function pow;
use function round;
use function scandir;
use function sprintf;
use function str_repeat;
use const DIRECTORY_SEPARATOR;
use const PATH_TESTS;
use const PHP_EOL;
use const SCANDIR_SORT_NONE;

final class DominusTestFramework
{
    const INDENT = '|   ';

    private int $assertionsCount = 0;
    private int $totalTests = 0;
    private bool $testsOk = true;
    private int $testStartedAt = 0;

    private Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
     */
    public function run(): void
    {
        echo PHP_EOL;
        $this->printInfo("Running tests...");
        $this->testStartedAt = hrtime(true);
        $this->testsOk = $this->runTestSuite(PATH_TESTS);
        $this->printSummary();
    }

    /**
     * @throws AutoMapPropertyMismatchException
     * @throws DependenciesNotMetException
     */
    private function runTestSuite(string $path, string $suiteName = '', int $indent = 0): bool
    {
        if($suiteName)
        {
            $this->printInfo("[$suiteName]", $indent);
        }

        $dirContents = scandir($path, SCANDIR_SORT_NONE);
        $testsOk = true;
        foreach ($dirContents as $item)
        {
            if($item[0] === '.')
            {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if(is_dir($itemPath))
            {
                $testsOk = $this->runTestSuite($itemPath, $item, $suiteName ? $indent + 1 : $indent);
            }
            else if(strtolower(pathinfo($itemPath, PATHINFO_EXTENSION)) === 'php')
            {
                $testsOk = $this->runTests($itemPath, $indent + 1);
            }
        }

        return $testsOk;
    }

    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
     */
    private function runTests(string $testFile, int $indent): bool
    {
        $test = require $testFile;
        if(!is_object($test))
        {
            $this->printError('Failed to load test cases from [' . basename($testFile) . '] -> Make sure you return the instance of your test suite!', $indent);
            return false;
        }

        if(!is_subclass_of($test, DominusTest::class))
        {
            $this->printError('Failed to load test cases from: [' . basename($testFile), $indent) . '] -> Does not extend [' . DominusTest::class . ']!';
            return false;
        }

        try
        {
            $testRef = new ReflectionClass($test);
        }
        catch (Exception)
        {
            $this->printError('Failed to get metadata [' . $test::class . ']', $indent);
            return false;
        }

        $testSuiteName = $testRef->getName();
        $descAttr = $testRef->getAttributes(TestName::class);
        if($descAttr)
        {
            $testSuiteName = $descAttr[0]->getArguments()[0];
        }

        $this->printInfo("[$testSuiteName]", $indent);

        $ok = true;
        $testCases = $testRef->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($testCases as $testCaseRef)
        {
            $testCaseMethodName = $testCaseRef->getName();
            ++$this->totalTests;

            $testCaseName = $testCaseMethodName;
            $requestParams = [];

            $attributes = $testCaseRef->getAttributes();
            foreach ($attributes as $attrRef)
            {
                switch ($attrRef->getName())
                {
                    case TestName::class:
                        $testCaseName = $attrRef->getArguments()[0];
                        break;

                    case TestRequestParameters::class:
                        $requestParams = $attrRef->getArguments()[0];
                        break;
                }
            }

            $request = $this->request;
            $request->setParameters($requestParams);

            try
            {
                $test->$testCaseMethodName(...Injector::getDependencies($testCaseRef, $request));
                $this->printOk("[\xE2\x9C\x94] $testCaseName", $indent + 1);
            }
            catch (Exception $e)
            {
                $this->printError("[\xE2\x9C\x98] $testCaseName -> " . $e->getMessage(), $indent + 1);
                $ok = false;
            }
        }

        $this->printInfo('|_________________________________', $indent);
        $this->assertionsCount += $test->assertions;
        return $ok;
    }

    private function printInfo(string $msg, int $indent = 0): void
    {
        echo str_repeat(self::INDENT, $indent) . $msg . PHP_EOL;
    }

    private function printOk(string $msg, int $indent = 0): void
    {
        echo str_repeat(self::INDENT, $indent) . "\033[32m" . $msg . " \033[0m" . PHP_EOL;
    }

    private function printError(string $msg, int $indent = 0): void
    {
        echo str_repeat(self::INDENT, $indent) . "\033[31m" . $msg . " \033[0m" . PHP_EOL;
    }

    private function printSummary(): void
    {
        echo PHP_EOL;
        echo "Time: " . $this->toTime() . ", Memory: " . $this->bytesToHumanReadable(memory_get_peak_usage(true)) . PHP_EOL;
        echo ($this->testsOk ? 'OK' : 'FAILED') . " ($this->totalTests tests, $this->assertionsCount assertions) " . PHP_EOL . PHP_EOL;
    }

    private function toTime(): string
    {
        $ms = (hrtime(true) - $this->testStartedAt) / 1e+6;
        if($ms >= 1000)
        {
            return sprintf('%05.2fs', $ms / 1000);
        }
        return sprintf('%05.2fms', $ms);
    }

    private function bytesToHumanReadable(int $bytes): string
    {
        if ($bytes == 0)
        {
            return "0.00 B";
        }

        $s = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $e = floor(log($bytes, 1024));

        return round($bytes/pow(1024, $e), 2).$s[$e];
    }
}