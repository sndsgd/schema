<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use ReflectionClass;
use sndsgd\Classname;
use sndsgd\schema\exceptions\DuplicateTypeException;
use sndsgd\schema\exceptions\UndefinedTypeException;
use sndsgd\schema\renderers\RenderHelper;
use sndsgd\schema\RuleList;
use sndsgd\schema\rules\AnyTypeRule;
use sndsgd\schema\rules\ArrayRule;
use sndsgd\schema\rules\BooleanRule;
use sndsgd\schema\rules\FloatRule;
use sndsgd\schema\rules\IntegerRule;
use sndsgd\schema\rules\ObjectRule;
use sndsgd\schema\rules\StringRule;
use sndsgd\schema\TypeHelper;
use sndsgd\schema\types\AnyType;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\MapType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\OneOfType;
use sndsgd\schema\types\ScalarType;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An object used to track defined types
 */
class DefinedTypes implements Countable
{
    private static $instance;
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    // TODO allow passing in something here so we can add types without
    // scanning the filesystem.
    public static function create(): DefinedTypes
    {
        $definedTypes = new DefinedTypes();
        foreach (self::getBaseTypes() as $type) {
            $definedTypes->addType($type);
        }

        return $definedTypes;
    }

    private static array $baseTypes = [];

    public static function getBaseTypes(): array
    {
        if (self::$baseTypes === []) {

            // use the string type as the default for the base array
            // and map sub types
            $stringType = new ScalarType(
                ScalarType::BASE_STRING_CLASSNAME,
                "a string",
                new RuleList(new StringRule()),
                "",
            );

            $baseTypes = [
                $stringType,
                new ScalarType(
                    ScalarType::BASE_BOOLEAN_CLASSNAME,
                    "a boolean",
                    new RuleList(new BooleanRule()),
                    "",
                ),
                new ScalarType(
                    ScalarType::BASE_INTEGER_CLASSNAME,
                    "an integer",
                    new RuleList(new IntegerRule()),
                    "",
                ),
                new ScalarType(
                    ScalarType::BASE_FLOAT_CLASSNAME,
                    "a float",
                    new RuleList(new FloatRule()),
                    "",
                ),
                new ArrayType(
                    ArrayType::BASE_CLASSNAME,
                    "an array",
                    new RuleList(new ArrayRule()),
                    $stringType,
                ),
                new ObjectType(
                    ObjectType::BASE_CLASSNAME,
                    "an object",
                    new RuleList(new ObjectRule()),
                    new PropertyList(),
                    [],
                    [],
                ),
                new MapType(
                    MapType::BASE_CLASSNAME,
                    "an object with key validation",
                    new RuleList(new ObjectRule()),
                    $stringType,
                    $stringType,
                ),
                new OneOfType(
                    OneOfType::BASE_CLASSNAME,
                    "a union type wrapper",
                    "",
                ),
                new OneOfObjectType(
                    OneOfObjectType::BASE_CLASSNAME,
                    "a object union type wrapper",
                    "",
                    [],
                ),
                new AnyType(
                    AnyType::BASE_CLASSNAME,
                    "a type for any value",
                    new RuleList(new AnyTypeRule()),
                ),
            ];

            foreach ($baseTypes as $type) {
                self::$baseTypes[$type->getName()] = $type;
            }
        }

        return self::$baseTypes;
    }

    public static function isBaseType(string $typeName): bool
    {
        $typeName = TypeHelper::resolveFullTypeName($typeName);
        return isset(self::getBaseTypes()[$typeName]);
    }

    private $types = [];

    private function __construct()
    {
        // Require the use of ::create() to create instances of this object
    }

    public function __debugInfo()
    {
        $ret = [];
        foreach ($this->types as $type) {
            $ret[] = $type->getName();
        }
        return $ret;
    }

    public function count(): int
    {
        return count($this->types);
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function addType(Type $type): void
    {
        $name = $type->getName();
        if (isset($this->types[$name])) {
            throw new DuplicateTypeException(
                "type '$name' is already defined",
            );
        }

        $this->types[$name] = $type;
    }

    public function hasType(string $typeName): bool
    {
        $typeName = TypeHelper::resolveFullTypeName($typeName);
        return isset($this->types[$typeName]);
    }

    public function getType(string $typeName): Type
    {
        $typeName = TypeHelper::resolveFullTypeName($typeName);
        if (!$this->hasType($typeName)) {
            throw new UndefinedTypeException(
                "failed to retrieve undefined type '$typeName'",
            );
        }

        return $this->types[$typeName];
    }

    public function renderClasses(
        CodePathResolver $codePathResolver,
        ?OutputInterface $output = null,
    ): void {
        ksort($this->types);

        $output && $output->write("\n", false, OutputInterface::VERBOSITY_VERBOSE);

        foreach ($this->types as $type) {
            $classname = Classname::toString($type->getName());

            // if the class already exists, we only want to write it if
            // it's path is in our app directory and _not_ in the app vendor
            // directory.
            if (class_exists($classname)) {
                $rc = new ReflectionClass($classname);
                if (!$codePathResolver->isPathRenderable($rc->getFileName())) {
                    $output && $output->writeln(
                        sprintf("skipping render for '%s'... ", $type->getName()),
                        OutputInterface::VERBOSITY_DEBUG,
                    );
                    continue;
                }
            }

            $php = RenderHelper::renderType($type);
            if ($php === "") {
                continue;
            }

            $path = $codePathResolver->getPath($classname);
            $output && $output->writeln(
                sprintf("rendering '%s'... ", $type->getName()),
                OutputInterface::VERBOSITY_DEBUG,
            );

            $dir = dirname($path);
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                die("failed to create dir\n");
            }

            file_put_contents($path, $php);
        }
    }
}
