<?php declare(strict_types=1);

namespace sndsgd\schema;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use sndsgd\Classname;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use Symfony\Component\Console\Output\OutputInterface;

class RuleLocator
{
    private const FILE_SUFFIX = ".php";

    public function locate(
        OutputInterface $output,
        DefinedRules $definedRules,
        array $searchPaths,
        array $excludePaths = [],
    ): int {
        $output->writeln("", OutputInterface::VERBOSITY_DEBUG);
        $ret = 0;
        foreach ($searchPaths as $path) {
            $ret += self::search(
                $output,
                $definedRules,
                $excludePaths,
                $path,
            );
        }

        return $ret;
    }

    private function search(
        OutputInterface $output,
        DefinedRules $definedRules,
        array $excludePaths,
        string $path,
    ): int {
        $output->writeln(" searching $path", OutputInterface::VERBOSITY_DEBUG);
        $ret = 0;

        $dir = new RecursiveDirectoryIterator($path);
        $files = new RecursiveCallbackFilterIterator(
            $dir,
            static function ($current, $key, $iterator) use ($excludePaths) {
                // don't recurse into the directories we want to ignore
                if ($iterator->hasChildren()) {
                    return !isset($excludePaths[$current->getRealPath()]);
                }

                // only include files that end with the desired extension
                return (
                    $current->isFile()
                    && str_ends_with($current->getBasename(), self::FILE_SUFFIX)
                );
            },
        );

        foreach (new RecursiveIteratorIterator($files) as $file) {
            $php = file_get_contents($file->getRealpath());
            $classname = Classname::fromContents($php);
            if (!$classname || !class_exists($classname)) {
                continue;
            }

            // all rules from sndsgd\schema are pre defined so you can exclude
            // the vendor directory to improve location speed.
            if (in_array($classname, DefinedRules::SNDSGD_SCHEMA_RULES, true)) {
                continue;
            }

            $rc = new ReflectionClass($classname);
            if ($rc->implementsInterface(Rule::class)) {
                $output->writeln("  adding $classname", OutputInterface::VERBOSITY_DEBUG);
                $definedRules->addRule($classname);
                $ret++;
            }
        }

        return $ret;
    }
}
