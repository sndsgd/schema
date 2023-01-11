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
use sndsgd\schema\exceptions\ErrorListException;
use Throwable;
use TypeError;
use UnexpectedValueException;

class TypeLocator
{
    public function locate(
        TypeHelper $typeHelper,
        Parser $parser,
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths = [],
    ): void {
        $errors = new ErrorList();

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
            foreach ($yamlDocs as $name => $yamlDoc) {
                $output->writeln(
                    sprintf("processing %s", $yamlDoc->getDebugPath()),
                    OutputInterface::VERBOSITY_DEBUG,
                );

                $dependencies = $typeHelper->getDependenciesForDoc($yamlDoc);
                foreach ($dependencies as $typeName) {
                    if (!$definedTypes->hasType($typeName)) {
                        $output->writeln(
                            sprintf(
                                "  '%s' depends on undiscovered type '%s'",
                                $yamlDoc->getName(),
                                $yamlDoc->getDebugPath(),
                                $typeName,
                            ),
                            OutputInterface::VERBOSITY_DEBUG,
                        );

                        // move the thing to the end to be processed later
                        unset($yamlDocs[$name]);
                        $yamlDocs[$name] = $yamlDoc;

                        continue 2;
                    }
                }

                try {
                    $type = $typeHelper->createTypeFromDoc($yamlDoc->doc);
                    $output->writeln(
                        "  adding type '{$type->getName()}'",
                        OutputInterface::VERBOSITY_DEBUG,
                    );
                    $definedTypes->addType($type);
                } catch (Throwable | TypeError $ex) {
                    $errors->addError(
                        $yamlDoc->getDebugPath(),
                        $ex->getMessage(),
                    );
                }

                unset($yamlDocs[$name]);
            }
        } while ($queueLen > 0 && $queueLen !== count($yamlDocs));

        // if there are any docs remaining, we're missing the types
        // that they depend on
        if (count($yamlDocs)) {
            foreach ($yamlDocs as $yamlDoc) {
                $dependencies = $typeHelper->getDependenciesForDoc($yamlDoc);
                foreach ($dependencies as $typeName) {
                    if (!$definedTypes->hasType($typeName)) {
                        $errors->addError(
                            $yamlDoc->getDebugPath(),
                            sprintf(
                                "failed to locate type '%s'",
                                $typeName,
                                $yamlDoc->getName(),
                            ),
                        );
                    }
                }
            }
        }

        if (count($errors)) {
            throw $errors->createException();
        }
    }

    private static function locateYamlDocs(
        ErrorList $errors,
        Parser $parser,
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths,
    ): array {
        $output->writeln("", OutputInterface::VERBOSITY_DEBUG);

        $tmp = [];
        foreach ($searchPaths as $path) {
            $path = realpath($path);
            $output->writeln(
                sprintf("searching '%s'", $path),
                OutputInterface::VERBOSITY_DEBUG,
            );

            $tmp[] = self::searchPath(
                $errors,
                $parser,
                $output,
                $path,
                $excludePaths,
            );
        }

        $ret = [];
        foreach (array_merge(...$tmp) as $yamlDoc) {
            $name = $yamlDoc->getName();
            if (!isset($ret[$name])) {
                $ret[$name] = $yamlDoc;
                continue;
            }

            $errors->addError(
                $yamlDoc->getDebugPath(),
                sprintf(
                    "duplicate type '%s' in %s; already found in '%s'",
                    $name,
                    $yamlDoc->getDebugPath(),
                    $ret[$name]->getDebugPath(),
                ),
            );
        }

        // if we've encountered any errors so far, we should fail here.
        // otherwise we may encounter unknown situations as we process types.
        // if (count($errors)) {
        //     throw $errors->createException();
        // }

        return $ret;
    }

    public static function searchPath(
        ErrorList $errors,
        Parser $parser,
        OutputInterface $output,
        string $path,
        array $excludePaths,
    ): array {
        if (!is_dir($path)) {
            throw new UnexpectedValueException(
                "failed to search non directory '$path'",
            );
        }

        $ret = [];

        // iterate over all type files, parse all the docs in the file, and add
        // the resulting types to our list
        foreach (self::createFsIterator($path, $excludePaths) as $file) {
            $output->writeln(
                "reading '$file'",
                OutputInterface::VERBOSITY_DEBUG,
            );

            $filePath = $file->getPathName();
            $relpath = substr($filePath, strlen($path) + 1);

            // yaml_parse_file() does not work with vfsstream
            $yaml = file_get_contents($filePath);
            try {
                $yamlDocs = $parser->parse($yaml, 0);
            } catch (Throwable $ex) {
                $errors->addError($relpath, $ex->getMessage());
                continue;
            }

            foreach ($yamlDocs as $index => $rawYamlDoc) {
                if (!is_array($rawYamlDoc) || $rawYamlDoc === []) {
                    continue;
                }

                try {
                    $doc = YamlDoc::create($path, $filePath, $index, $rawYamlDoc);
                } catch (Throwable $ex) {
                    $errors->addError(
                        YamlDoc::renderDebugMessage($path, $filePath, $index),
                        $ex->getMessage(),
                    );
                    continue;
                }

                if ($doc === null) {
                    $output->writeln(
                        sprintf("skipping '%s:#%s'", $relpath, $index),
                        OutputInterface::VERBOSITY_DEBUG,
                    );
                    continue;
                }

                $ret[] = $doc;
            }
        }

        return $ret;
    }

    private static function createFsIterator(
        string $searchDir,
        array $excludePaths,
    ): RecursiveIteratorIterator {
        return new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($searchDir),
                static function ($current, $key, $iterator) use ($excludePaths) {
                    // don't recurse into the directories we want to ignore
                    if ($iterator->hasChildren()) {
                        return !isset($excludePaths[$current->getBasename()]);
                    }

                    // only include files that end with the desired extension
                    return (
                        $current->isFile()
                        && str_ends_with($current->getBasename(), ".yaml")
                    );
                },
            )
        );
    }
}
