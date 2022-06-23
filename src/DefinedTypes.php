<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use LogicException;
use sndsgd\schema\exceptions\DuplicateTypeException;
use sndsgd\schema\exceptions\UndefinedTypeException;
use sndsgd\schema\helpers\TypeHelper;
use sndsgd\schema\RuleList;
use sndsgd\schema\types\AnyType;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\MapType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\OneOfType;
use sndsgd\schema\types\ScalarType;
use sndsgd\yaml\Callback as YamlCallback;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An object used to track defined types
 */
class DefinedTypes implements Countable, YamlCallback
{
    /**
     * @see sndsgd\yaml\Callback
     */
    public static function getYamlCallbackTag(): string
    {
        return "!type";
    }

    /**
     * @see sndsgd\yaml\Callback
     */
    public static function executeYamlCallback(
        string $tag,
        $value,
        int $flags,
        \sndsgd\yaml\ParserContext $context
    ) {
        if (!is_scalar($value)) {
            throw new LogicException(
                "failed to convert non scalar to min value",
            );
        }

        $definedTypes = $context->getDefinedTypes();
        if (!$definedTypes->hasType($value)) {
            throw new LogicException(
                "failed to retrieve undefined type '$value'",
            );
        }

        return $definedTypes->getType($value);
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
                new RuleList(new \sndsgd\schema\rules\StringRule()),
                "",
                "",
            );

            $baseTypes = [
                $stringType,
                new ScalarType(
                    ScalarType::BASE_BOOLEAN_CLASSNAME,
                    "a boolean",
                    new RuleList(new \sndsgd\schema\rules\BooleanRule()),
                    "",
                ),
                new ScalarType(
                    ScalarType::BASE_INTEGER_CLASSNAME,
                    "an integer",
                    new RuleList(new \sndsgd\schema\rules\IntegerRule()),
                    "",
                    0,
                ),
                new ScalarType(
                    ScalarType::BASE_FLOAT_CLASSNAME,
                    "a float",
                    new RuleList(new \sndsgd\schema\rules\FloatRule()),
                    "",
                    0.0,
                ),
                new ArrayType(
                    ArrayType::BASE_CLASSNAME,
                    "an array",
                    new RuleList(new \sndsgd\schema\rules\ArrayRule()),
                    $stringType,
                ),
                new ObjectType(
                    ObjectType::BASE_CLASSNAME,
                    "an object",
                    new RuleList(new \sndsgd\schema\rules\ObjectRule()),
                    new PropertyList(),
                    [],
                    [],
                ),
                new MapType(
                    MapType::BASE_CLASSNAME,
                    "an object with key validation",
                    new RuleList(new \sndsgd\schema\rules\ObjectRule()),
                    $stringType,
                    $stringType,
                ),
                new OneOfType(
                    OneOfType::BASE_CLASSNAME,
                    "a union type wrapper",
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
                    new RuleList(new \sndsgd\schema\rules\AnyTypeRule()),
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

    private $types = [];

    /**
     * Require the use of ::create() to create instances of this object
     */
    private function __construct()
    {
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
        string $basedir,
        ?OutputInterface $output = null
    ): void {
        ksort($this->types);

        foreach ($this->types as $type) {
            if ($type instanceof ObjectType) {
                $renderer = new \sndsgd\schema\renderers\ObjectTypeRenderer($type);
            } elseif ($type instanceof ScalarType) {
                $renderer = new \sndsgd\schema\renderers\ScalarTypeRenderer($type);
            } elseif ($type instanceof ArrayType) {
                $renderer = new \sndsgd\schema\renderers\ArrayTypeRenderer($type);
            } elseif ($type instanceof MapType) {
                $renderer = new \sndsgd\schema\renderers\MapTypeRenderer($type);
            } elseif ($type instanceof OneOfType) {
                if ($type->getName() === OneOfType::BASE_CLASSNAME) {
                    continue;
                }
                $renderer = new \sndsgd\schema\renderers\OneOfTypeRenderer($type);
            } elseif ($type instanceof OneOfObjectType) {
                if ($type->getName() === OneOfObjectType::BASE_CLASSNAME) {
                    continue;
                }
                $renderer = new \sndsgd\schema\renderers\OneOfObjectTypeRenderer($type);
            } elseif ($type instanceof AnyType) {
                $renderer = new \sndsgd\schema\renderers\AnyTypeRenderer($type);
            } else {
                throw new \Exception("failed to process type instance\n" . print_r($type, true));
            }

            $output && $output->writeln(
                sprintf("rendering '%s'... ", $type->getName()),
                OutputInterface::VERBOSITY_DEBUG,
            );

            $php = $renderer->render();
            $path = \sndsgd\schema\renderers\RenderHelper::getTypePsr4Path($basedir, $type);
            $dir = dirname($path);
            if (!file_exists($dir) && !mkdir($dir, 0777, true)) {
                die("failed to create dir\n");
            }

            // echo "$path\n";
            file_put_contents($path, $php);
        }
    }

    public function getYamlCallbackClasses(): array
    {
        return [];
    }
}
