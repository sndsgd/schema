<?php declare(strict_types=1);

namespace sndsgd\schema;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use sndsgd\schema\exceptions\InvalidTypeDefinitionException;
use sndsgd\schema\helpers\TypeHelper;
use sndsgd\Str;
use sndsgd\yaml\exceptions\ParserException;
use sndsgd\yaml\Parser;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TypeError;
use UnexpectedValueException;

class TypeLocator
{
    private const TYPE_FILE_SUFFIX = ".type.yaml";

    public function locate(
        TypeHelper $typeHelper,
        Parser $parser,
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths = [],
    ): void {
        $errors = new ValidationErrorList();

        // iterate the search paths and process any type.yaml files
        // the result here is an indexed array of [path, docIndex, rawDoc]
        // note that the results are not in dependency order!
        $yamlDocs = self::locateYamlDocs(
            $errors,
            $parser,
            $output,
            $searchPaths,
            $excludePaths,
        );

        $definedTypes = $typeHelper->getDefinedTypes();

        // repeatedly process the list of all yaml docs until none remain
        do {
            $queueLen = count($yamlDocs);
            $missingDependencies = [];
            foreach ($yamlDocs as $name => [$filePath, $docIndex, $rawDoc]) {
                $relpath = sprintf(
                    "%s#%d",
                    self::getRelativePathForErrors($searchPaths, $filePath),
                    $docIndex,
                );

                // if we already have an error for the doc we don't need to
                // attempt to process it further.
                if ($errors->hasError($relpath)) {
                    unset($yamlDocs[$name]);
                    continue;
                }

                $output->writeln(
                    "processing '$relpath'",
                    OutputInterface::VERBOSITY_DEBUG,
                );

                $docType = $rawDoc["type"] ?? "";
                if ($docType === "") {
                    $errors->addError($relpath, "missing required property 'type'");
                    unset($yamlDocs[$name]);
                    continue;
                }

                if (!is_string($docType)) {
                    $errors->addError(
                        $relpath,
                        "invalid type for property 'type'; expecting a string",
                    );
                    unset($yamlDocs[$name]);
                    continue;
                }

                // if we don't have the type defined in the raw doc yet, move
                // the current doc to the end of the list to be processed after
                // all other types are added.
                if (!$definedTypes->hasType($docType)) {
                    $output->writeln(
                        "'$relpath' awaiting '$docType'",
                        OutputInterface::VERBOSITY_DEBUG,
                    );
                    unset($yamlDocs[$name]);
                    $yamlDocs[$name] = [$filePath, $docIndex, $rawDoc];
                    continue;
                }

                try {
                    $dependencies = $typeHelper->getDependenciesFromDoc($rawDoc);
                } catch (InvalidTypeDefinitionException $ex) {
                    $errors->addError($relpath, $ex->getMessage());
                    unset($yamlDocs[$name]);
                    continue;
                }

                foreach ($dependencies as $typeName) {
                    if (!$definedTypes->hasType($typeName)) {
                        $output->writeln(
                            "'$relpath' depends on '$typeName'",
                            OutputInterface::VERBOSITY_DEBUG,
                        );

                        $missingDependencies[$typeName] = true;
                        continue 2;
                    }
                }

                try {
                    $type = $typeHelper->createTypeFromDoc($rawDoc);
                    $output->writeln(
                        "adding type '{$type->getName()}'",
                        OutputInterface::VERBOSITY_DEBUG,
                    );
                    $definedTypes->addType($type);
                } catch (Throwable | TypeError $ex) {
                    $errors->addError($relpath, $ex->getMessage());
                }

                unset($yamlDocs[$name]);
            }

            if ($queueLen > 0 && $queueLen === count($yamlDocs)) {
                foreach ($yamlDocs as [$filePath, $docIndex, $rawDoc]) {
                    $missing = [];
                    foreach ($typeHelper->getDependenciesFromDoc($rawDoc) as $typeName) {
                        if (isset($missingDependencies[$typeName])) {
                            $missing[$typeName] = $typeName;
                        }
                    }

                    $errors->addError(
                        self::getRelativePathForErrors($searchPaths, $filePath) . "#" . $docIndex,
                        sprintf(
                            "failed to process '%s' due to missing dependencies [%s]",
                            $rawDoc["name"] ?? "unknown",
                            implode(",", $missing),
                        ),
                    );
                }

                throw $errors->createException();
            }
        } while ($yamlDocs);

        if (count($errors)) {
            throw $errors->createException();
        }
    }

    private static function locateYamlDocs(
        ValidationErrorList $errors,
        Parser $parser,
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths,
    ): array {
        $yamlDocs = [];

        $output->writeln("", OutputInterface::VERBOSITY_DEBUG);

        foreach ($searchPaths as $path) {
            $output->writeln(
                "searching '$path'",
                OutputInterface::VERBOSITY_DEBUG,
            );

            $yamlDocs[] = self::searchPath(
                $errors,
                $parser,
                $output,
                $path,
                $searchPaths,
                $excludePaths,
            );
        }

        // if we've encountered any errors so far, we should fail here.
        // otherwise we may encounter unknown situations as we process types.
        if (count($errors)) {
            throw $errors->createException();
        }

        $ret = [];
        foreach (array_merge(...$yamlDocs) as [$filePath, $index, $yamlDoc]) {
            $name = $yamlDoc["name"] ?? "unknown";
            $ret[$name] = [$filePath, $index, $yamlDoc];
        }

        return $ret;
    }

    /**
     * Retrieve the shortest relative path for a file that results in an error
     */
    private static function getRelativePathForErrors(
        array $searchPaths,
        string $foundPath,
    ): string {
        $match = "";
        foreach ($searchPaths as $path) {
            $path = dirname($path);
            if (Str::beginsWith($foundPath, $path)) {
                if (strlen($path) > strlen($match)) {
                    $match = $path;
                }
            }
        }

        return substr($foundPath, strlen($match) + 1);
    }

    public static function searchPath(
        ValidationErrorList $errors,
        Parser $parser,
        OutputInterface $output,
        string $path,
        array $searchPaths,
        array $excludePaths,
    ): array {
        if (!is_dir($path)) {
            throw new UnexpectedValueException(
                "failed to search non directory '$path'",
            );
        }

        $dir = new RecursiveDirectoryIterator($path);
        $files = new RecursiveCallbackFilterIterator(
            $dir,
            static function ($current, $key, $iterator) use ($excludePaths) {
                // don't recurse into the directories we want to ignore
                if ($iterator->hasChildren()) {
                    return !isset($excludePaths[$current->getBasename()]);
                }

                // only include files that end with the desired extension
                return (
                    $current->isFile() &&
                    Str::endsWith($current->getBasename(), self::TYPE_FILE_SUFFIX)
                );
            },
        );

        $ret = [];

        // iterate over all type files, parse all the docs in the file, and add
        // the resulting types to our list
        foreach (new RecursiveIteratorIterator($files) as $file) {
            $output->writeln(
                "reading '$file'",
                OutputInterface::VERBOSITY_DEBUG,
            );

            $filePath = $file->getPathName();
            // yaml_parse_file() does not work with vfsstream
            $yaml = file_get_contents($filePath);
            try {
                $yamlDocs = $parser->parse($yaml, 0);
            } catch (ParserException $ex) {
                $errors->addError(
                    self::getRelativePathForErrors($searchPaths, $filePath),
                    $ex->getMessage(),
                );
                continue;
            }

            foreach ($yamlDocs as $index => $yamlDoc) {
                if ($yamlDoc === []) {
                    continue;
                }

                $ret[] = [$filePath, $index, $yamlDoc];
            }
        }

        return $ret;
    }
}
