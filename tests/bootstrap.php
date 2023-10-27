<?php declare(strict_types=1);

use sndsgd\Classname;
use sndsgd\schema\CodePathResolver;
use sndsgd\schema\DefinedRules;
use sndsgd\schema\DefinedTypes;
use sndsgd\schema\RuleLocator;
use sndsgd\schema\TypeHelper;
use sndsgd\schema\TypeLocator;
use sndsgd\Str;
use sndsgd\yaml\callbacks\SecondsCallback;
use sndsgd\yaml\Parser;
use sndsgd\yaml\ParserContext;
use Symfony\Component\Console\Output\BufferedOutput;

const BUILD_DIR = __DIR__ . "/../build/tests";

require __DIR__ . "/../vendor/autoload.php";

if (!file_exists(BUILD_DIR) && !mkdir(BUILD_DIR, 0777, true)) {
    fwrite(STDERR, "failed to create build directory\n");
    exit(1);
}

function createTestTypes(string $yaml): string
{
    static $created = [];
    static $required = [];

    $hash = sha1($yaml);
    if (isset($created[$hash])) {
        return $created[$hash];
    }

    $schemaDir = BUILD_DIR . "/$hash";
    $schemaPath = $schemaDir . "/$hash.type.yaml";

    if (!file_exists($schemaDir) && !mkdir($schemaDir, 0777, true)) {
        throw new Exception("failed to create directory '$schemaDir'");
    }

    file_put_contents($schemaPath, $yaml);

    $searchPaths = [$schemaDir];
    $excludePaths = [];

    $output = new BufferedOutput();

    $definedRules = DefinedRules::create();
    $ruleLocator = new RuleLocator();
    $ruleLocator->locate(
        $output,
        $definedRules,
        $searchPaths,
        $excludePaths,
    );

    $definedTypes = DefinedTypes::create();

    $parserContext = new ParserContext();
    $parserCallbacks = array_merge(
        [SecondsCallback::class],
        $definedRules->getYamlCallbackClasses(),
    );
    $parser = new Parser($parserContext, ...$parserCallbacks);

    $typeLocator = new TypeLocator();
    $typeLocator->locate(
        new TypeHelper($definedTypes, $definedRules),
        $parser,
        $output,
        $searchPaths,
        $excludePaths,
    );

    $pathResolver = new CodePathResolver(dirname(BUILD_DIR), [], BUILD_DIR);
    $definedTypes->renderClasses($pathResolver);

    unlink($schemaPath);
    rmdir($schemaDir);

    $filterCallback = new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator(BUILD_DIR, FilesystemIterator::SKIP_DOTS),
        static function ($current, $key, $iterator) {
            return (
                !$current->isFile()
                || Str::endsWith($current->getBasename(), ".php")
            );
        },
    );

    foreach (new RecursiveIteratorIterator($filterCallback) as $file) {
        $path = $file->getPathname();
        if (!isset($required[$path])) {
            $required[$path] = true;
            require $path;
        }
    }

    $docs = yaml_parse($yaml, -1);
    $lastDoc = end($docs);
    return $created[$hash] = Classname::toString($lastDoc["name"]);
}
