<?php declare(strict_types=1);

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

    $definedRules = \sndsgd\schema\DefinedRules::create();
    $ruleLocator = new \sndsgd\schema\RuleLocator();
    $ruleLocator->locate($definedRules, $searchPaths, $excludePaths);

    $definedTypes = \sndsgd\schema\DefinedTypes::create();

    $parserContext = new \sndsgd\schema\YamlParserContext($definedTypes);
    $parserCallbacks = array_merge(
        [\sndsgd\yaml\callbacks\SecondsCallback::class],
        $definedRules->getYamlCallbackClasses(),
        $definedTypes->getYamlCallbackClasses(),
    );
    $parser = new \sndsgd\yaml\Parser($parserContext, ...$parserCallbacks);

    $typeLocator = new \sndsgd\schema\TypeLocator();
    $typeLocator->locate(
        new \sndsgd\schema\helpers\TypeHelper($definedTypes, $definedRules),
        $parser,
        new \Symfony\Component\Console\Output\BufferedOutput(),
        $searchPaths,
        $excludePaths,
    );

    $definedTypes->renderClasses(BUILD_DIR);

    unlink($schemaPath);
    rmdir($schemaDir);

    $filterCallback = new \RecursiveCallbackFilterIterator(
        new \RecursiveDirectoryIterator(BUILD_DIR, \FilesystemIterator::SKIP_DOTS),
        static function ($current, $key, $iterator) {
            return (
                !$current->isFile()
                || \sndsgd\Str::endsWith($current->getBasename(), ".php")
            );
        },
    );

    foreach (new \RecursiveIteratorIterator($filterCallback) as $file) {
        $path = $file->getPathname();
        if (!isset($required[$path])) {
            $required[$path] = true;
            require $path;
        }
    }

    $docs = yaml_parse($yaml, -1);
    $lastDoc = end($docs);
    return $created[$hash] = \sndsgd\Classname::toString($lastDoc["name"]);
}
