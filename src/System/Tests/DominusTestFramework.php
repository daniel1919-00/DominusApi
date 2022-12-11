<?php

namespace Dominus\System\Tests;

use Exception;
use ReflectionClass;
use Dominus\System\Attributes\TestDescription;
use Dominus\System\Attributes\TestRequestParameters;
use Dominus\System\Exceptions\AutoMapPropertyMismatchException;
use Dominus\System\Exceptions\DependenciesNotMetException;
use Dominus\System\Injector;
use Dominus\System\Request;
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
use function str_starts_with;
use const DIRECTORY_SEPARATOR;
use const APP_ENV_CLI;
use const PATH_TESTS;
use const PHP_EOL;
use const SCANDIR_SORT_NONE;

final class DominusTestFramework
{
    private int $assertionsCount = 0;
    private int $totalTests = 0;
    private bool $testsOk = true;
    private int $testStartedAt = 0;

    private Request $request;
    private string $indent;
    private string $newLine;

    public function __construct()
    {
        if(APP_ENV_CLI)
        {
            $this->indent = '|    ';
            $this->newLine = PHP_EOL;
        }
        else
        {
            $this->indent = '&nbsp;&nbsp;&nbsp;&nbsp;';
            $this->newLine = '<br>';
        }

        $this->request = new Request();
    }

    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
     */
    public function run(): void
    {
        echo $this->newLine;
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
        foreach ($dirContents as $item)
        {
            if($item === '.' || $item === '..')
            {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if(is_dir($itemPath))
            {
                if(!$this->runTestSuite($itemPath, $item, $suiteName ? $indent + 1 : $indent))
                {
                    return false;
                }
            }
            else
            {
                if(!$this->runTest($itemPath, $indent + 1))
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws DependenciesNotMetException
     * @throws AutoMapPropertyMismatchException
     */
    private function runTest(string $testFile, int $indent): bool
    {
        $test = require $testFile;
        if(!is_object($test))
        {
            $this->printError("Failed to load test cases from: " . basename($testFile), $indent);
            return false;
        }

        if(!is_subclass_of($test, DominusTest::class))
        {
            $this->printError("Failed to load test cases from " . basename($testFile), $indent) . ' -> Does not extend: ' . DominusTest::class;
            return false;
        }

        try
        {
            $testRef = new ReflectionClass($test);
        }
        catch (Exception)
        {
            $this->printError("Failed to get metadata: " . $test::class, $indent);
            return false;
        }

        $testSuiteName = $testRef->getName();
        $descAttr = $testRef->getAttributes(TestDescription::class);
        if($descAttr)
        {
            $testSuiteName = $descAttr[0]->getArguments()[0];
        }

        $this->printInfo("[$testSuiteName]", $indent);

        $ok = true;
        $testCases = $testRef->getMethods();
        foreach ($testCases as $testCaseRef)
        {
            if(!$testCaseRef->isPublic())
            {
                continue;
            }

            $name = $testCaseRef->getName();
            if(str_starts_with($name, '_dominusTest_'))
            {
                continue;
            }

            ++$this->totalTests;

            $testCaseName = $name;
            $requestParams = [];

            $attributes = $testCaseRef->getAttributes();
            foreach ($attributes as $attrRef)
            {
                switch ($attrRef->getName())
                {
                    case TestDescription::class:
                        $testCaseName = $attrRef->getArguments()[0];
                        break;

                    case TestRequestParameters::class:
                        $requestParams = $attrRef->getArguments()[0];
                        break;
                }
            }

            $request = $this->request;
            $request->setParameters($requestParams);

            try {
                $test->$name(...Injector::getDependencies($testCaseRef, $request));
                $this->printOk("[OK] $testCaseName", $indent + 1);
            }
            catch (Exception $e)
            {
                $this->printError("[X] $testCaseName -> " . $e->getMessage(), $indent + 1);
                $ok = false;
                break;
            }
        }
        $this->printInfo('|_________________________________', $indent);
        $this->assertionsCount += $test->_dominusTest_getAssertionsCount();
        return $ok;
    }

    private function printInfo(string $msg, int $indent = 0): void
    {
        echo str_repeat($this->indent, $indent) . $msg . $this->newLine;
    }

    private function printOk(string $msg, int $indent = 0): void
    {
        echo str_repeat($this->indent, $indent) . $msg . $this->newLine;
    }
    private function printError(string $msg, int $indent = 0): void
    {
        echo str_repeat($this->indent, $indent) . $msg . $this->newLine;
    }

    private function printSummary(): void
    {
        echo "Time: " . $this->toTime() . ", Memory: " . $this->bytesToHumanReadable(memory_get_peak_usage(true)) . "$this->newLine";
        echo ($this->testsOk ? 'OK' : 'FAILED') . " ($this->totalTests tests, $this->assertionsCount assertions) $this->newLine $this->newLine";
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