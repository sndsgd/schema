<?php declare(strict_types=1);

use sndsgd\Classname;
use sndsgd\schema\DefinedRules;
use sndsgd\schema\DefinedTypes;
use sndsgd\schema\helpers\TypeHelper;
use sndsgd\schema\RuleLocator;
use sndsgd\schema\TypeLocator;
use sndsgd\schema\YamlParserContext;
use sndsgd\Str;
use sndsgd\yaml\callbacks\SecondsCallback;
use sndsgd\yaml\Parser;
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

    mkdir($schemaDir);
    file_put_contents($schemaPath, $yaml);

    $searchPaths = [$schemaDir];
    $excludePaths = [];

    $definedRules = DefinedRules::create();
    $ruleLocator = new RuleLocator();
    $ruleLocator->locate($definedRules, $searchPaths, $excludePaths);

    $definedTypes = DefinedTypes::create();

    $parserContext = new YamlParserContext($definedTypes);
    $parserCallbacks = array_merge(
        [SecondsCallback::class],
        $definedRules->getYamlCallbackClasses(),
        $definedTypes->getYamlCallbackClasses(),
    );
    $parser = new Parser($parserContext, ...$parserCallbacks);

    $typeLocator = new TypeLocator();
    $typeLocator->locate(
        new TypeHelper($definedTypes, $definedRules),
        $parser,
        new BufferedOutput(),
        $searchPaths,
        $excludePaths,
    );

    $definedTypes->renderClasses(BUILD_DIR);

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
