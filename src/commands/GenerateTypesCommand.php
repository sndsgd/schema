<?php declare(strict_types=1);

namespace sndsgd\schema\commands;

use sndsgd\schema\DefinedRules;
use sndsgd\schema\DefinedTypes;
use sndsgd\schema\exceptions\ErrorListException;
use sndsgd\schema\TypeHelper;
use sndsgd\schema\RuleLocator;
use sndsgd\schema\TypeLocator;
use sndsgd\yaml\callbacks\SecondsCallback;
use sndsgd\yaml\Parser;
use sndsgd\yaml\ParserContext;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * The console command that is used to generate model config classes
 */
class GenerateTypesCommand extends Command
{
    public const NAME = "generate";

    private DefinedRules $definedRules;
    private RuleLocator $ruleLocator;
    private DefinedTypes $definedTypes;
    private TypeLocator $typeLocator;

    public function __construct(
        $name = null,
        ?DefinedRules $definedRules = null,
        ?RuleLocator $ruleLocator = null,
        ?DefinedTypes $definedTypes = null,
        ?TypeLocator $typeLocator = null,
    ) {
        parent::__construct($name);

        $this->definedRules = $definedRules ?? DefinedRules::create();
        $this->ruleLocator = $ruleLocator ?? new RuleLocator();
        $this->definedTypes = $definedTypes ?? DefinedTypes::create();
        $this->typeLocator = $typeLocator ?? new TypeLocator();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME);
        $this->setDescription("Generate type classes from type.yaml files");

        $this->addArgument(
            "search-path",
            InputArgument::IS_ARRAY,
            "One or more directories to search for type.yaml files",
        );

        $this->addOption(
            "exclude-path",
            "",
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            "One or more paths to exclude from search",
        );

        $this->addOption(
            "render-path",
            "",
            InputOption::VALUE_REQUIRED,
            "The path to render generated classes in",
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchPaths = self::verifySearchPaths($input, $output);
        if ($searchPaths === null) {
            return 1;
        }

        $excludePaths = self::verifyExcludePaths($input, $output);
        if ($excludePaths === null) {
            return 1;
        }

        $renderPath = self::verifyRenderPath($input, $output);
        if ($renderPath === "") {
            return 1;
        }

        if (!$this->locateRules($output, $searchPaths, $excludePaths)) {
            return 1;
        }

        if (!$this->locateTypes($output, $searchPaths, $excludePaths)) {
            return 1;
        }

        if (!$this->renderTypes($output, $renderPath)) {
            return 1;
        }

        $output->writeln("");
        $output->writeln(
            sprintf(
                "successfully processed %s %s",
                number_format(count($this->definedTypes)),
                count($this->definedTypes) === 1 ? "type" : "types",
            ),
        );

        return 0;
    }

    private static function verifySearchPaths(
        InputInterface $input,
        OutputInterface $output,
    ): ?array {
        $searchPaths = [];
        $errors = 0;
        foreach ($input->getArgument("search-path") as $path) {
            $realpath = realpath($path);
            if ($realpath === false) {
                $output->writeln("<fg=red>error</> invalid search path '$path'; path does not exist");
                $errors++;
                continue;
            }

            if (!is_dir($realpath) || !is_readable($realpath)) {
                $output->writeln("<fg=red>error</> invalid search path '$path'; entity is not a readable directory");
                $errors++;
                continue;
            }

            $output->writeln(
                "verified search path '$realpath'",
                OutputInterface::VERBOSITY_DEBUG,
            );

            $searchPaths[] = $realpath;
        }

        return $errors === 0 ? $searchPaths : null;
    }

    private static function verifyExcludePaths(
        InputInterface $input,
        OutputInterface $output,
    ): ?array {
        $excludePaths = [];
        $errors = 0;
        foreach ($input->getOption("exclude-path") as $path) {
            $realpath = realpath($path);
            if ($realpath === false) {
                $output->writeln("<fg=red>error</> invalid exclude path '$path'; path does not exist");
                $errors++;
                continue;
            }

            $output->writeln(
                "verified exclude path '$realpath'",
                OutputInterface::VERBOSITY_DEBUG,
            );

            $excludePaths[] = $realpath;
        }

        return $errors === 0 ? $excludePaths : null;
    }

    private static function verifyRenderPath(
        InputInterface $input,
        OutputInterface $output,
    ): string {
        $path = $input->getOption("render-path");
        if ($path === null) {
            $output->writeln("<fg=red>error</> missing required option '--render-path'");
            return "";
        }

        $realpath = realpath($path);
        if ($realpath === false) {
            $output->writeln("<fg=red>error</> invalid render path '$path'; path does not exist");
            return "";
        }

        $output->writeln(
            "verified render path '$realpath'",
            OutputInterface::VERBOSITY_DEBUG,
        );

        return $realpath;
    }

    private function locateRules(
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths = [],
    ): bool {
        $output->write("searching for rules... ");
        try {
            $this->ruleLocator->locate(
                $output,
                $this->definedRules,
                $searchPaths,
                $excludePaths,
            );
        } catch (ErrorListException $ex) {
            self::handleErrorListException($ex, $output);
            return false;
        } catch (Throwable $ex) {
            $output->writeln("failed!");
            throw $ex;
        }

        $output->writeln("<fg=green>done</>");
        return true;
    }

    private function getYamlCallbackClasses(): array
    {
        return array_merge(
            [SecondsCallback::class],
            $this->definedRules->getYamlCallbackClasses(),
        );
    }

    private function locateTypes(
        OutputInterface $output,
        array $searchPaths,
        array $excludePaths = [],
    ): bool {
        $output->write("searching for types... ");
        try {
            $this->typeLocator->locate(
                new TypeHelper($this->definedTypes, $this->definedRules),
                new Parser(
                    new ParserContext(),
                    ...$this->getYamlCallbackClasses(),
                ),
                $output,
                $searchPaths,
                $excludePaths,
            );
        } catch (ErrorListException $ex) {
            self::handleErrorListException($ex, $output);
            return false;
        } catch (Throwable $ex) {
            $output->writeln("failed!");
            throw $ex;
        }

        $output->writeln("<fg=green>done</>");
        return true;
    }

    private function renderTypes(
        OutputInterface $output,
        string $renderPath,
    ): bool {
        $output->write("rendering types... ");
        $this->definedTypes->renderClasses($renderPath, $output);
        $output->writeln("<fg=green>done</>");

        return true;
    }

    private static function handleErrorListException(
        ErrorListException $ex,
        OutputInterface $output,
    ): void {
        $output->writeln("<fg=red>failed</>");
        $output->writeln("");
        $output->writeln("address the following issues and try again:");
        foreach ($ex->getErrorList()->getErrors() as $path => $errors) {
            $output->writeln(sprintf("  <fg=yellow>%s</>", $path));
            foreach ($errors as $error) {
                $output->writeln(sprintf("    → %s", $error));
            }
        }
    }
}
