<?php declare(strict_types=1);

namespace sndsgd\schema;

use Exception;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use sndsgd\schema\ValidationFailure;
use sndsgd\Str;
use sndsgd\yaml\exceptions\ParserException;

/**
 * @coversNothing
 */
class FixtureTest extends \PHPUnit\Framework\TestCase
{
    private static array $data;

    private static function getSearchPath(): string
    {
        return realpath(__DIR__ . "/../..");
    }

    private static function getSearchIterator(
        string $searchPath
    ): RecursiveIteratorIterator {
        return new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($searchPath),
                static function ($current, $key, $iterator) {
                    return (
                        $iterator->hasChildren()
                        || (
                            $current->isFile()
                            && substr($current->getBasename(), -10) === ".test.yaml"
                        )
                    );
                },
            ),
        );
    }

    private static function splitFixtureContents(
        string $path,
        string $relpath
    ): array {
        $contents = file_get_contents($path);
        try {
            $docs = (new \sndsgd\yaml\Parser())->parse($contents, 0);
        } catch (ParserException $ex) {
            throw new Exception(
                $ex->getMessage() . " in '$relpath'",
                0,
                $ex,
            );
        }

        if (count($docs) < 3) {
            throw new Exception(
                "failed to load '$relpath'; "
                . "fixtures most contain at least three YAML documents",
            );
        }

        // use the original YAML for the definition so callbacks can be used
        $yaml = Str::before($contents, "---", true);
        $yaml = Str::before($yaml, "---", true);

        return [$yaml, array_pop($docs), array_pop($docs)];
    }

    private static function getTests(): array
    {
        if (isset(self::$data)) {
            return self::$data;
        }

        self::$data = [];

        $searchPath = self::getSearchPath();
        $searchPathLength = strlen($searchPath) + 1;

        foreach (self::getSearchIterator($searchPath) as $file) {
            $relpath = substr($file->getPathname(), $searchPathLength);
            [$yaml, $failureData, $successData] = self::splitFixtureContents(
                $file->getPathname(),
                $relpath,
            );
            $classname = createTestTypes($yaml);
            if (!$classname) {
                throw new Exception("no classname found in '$relpath'");
            }

            self::$data[$relpath] = [$classname, $failureData ?? [], $successData];
        }

        return self::$data;
    }

    public function provideFailure(): array
    {
        $ret = [];
        foreach (self::getTests() as $relpath => [$classname, , $tests]) {
            foreach ($tests as $index => $testData) {
                $ret["$relpath:failures:#" . ($index + 1)] = [
                    $classname,
                    array_values(\sndsgd\Arr::without($testData, "expect")),
                    $testData["expect"],
                ];
            }
        }

        return $ret;
    }

    /**
     * @dataProvider provideFailure
     */
    public function testFailure(
        string $class,
        array $args,
        array $expectErrors
    ): void {
        // initialize these to keep static analysis from complaining
        $instance = null;
        $ex = null;

        try {
            $instance = new $class(...$args);
        } catch (ValidationFailure $ex) {
            // do nothing; inspect the errors below
        }

        $this->assertNull($instance);
        $this->assertSame($expectErrors, $ex->getValidationErrors()->toArray());
    }

    public function provideSuccess(): array
    {
        $ret = [];
        foreach (self::getTests() as $relpath => [$classname, $tests]) {
            foreach ($tests as $index => $testData) {
                if (!isset($testData["expect"])) {
                    $testData["expect"] = $testData["value"];
                }

                $ret["$relpath:success:#" . ($index + 1)] = [
                    $classname,
                    array_values(\sndsgd\Arr::without($testData, "expect")),
                    $testData["expect"],
                ];
            }
        }

        return $ret;
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess(
        string $class,
        array $args,
        $expect
    ): void {
        try {
            $instance = new $class(...$args);
        } catch (\sndsgd\schema\ValidationFailure $ex) {
            $this->fail(
                "validation failed:\n" .
                yaml_emit($ex->getValidationErrors()->toArray()),
            );
        }

        $this->assertSame($expect, $instance->jsonSerialize());
    }
}
