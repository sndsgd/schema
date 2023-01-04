<?php declare(strict_types=1);

namespace sndsgd\schema;

use sndsgd\schema\exceptions\DuplicateRuleException;
use sndsgd\schema\exceptions\UndefinedRuleException;
use sndsgd\Str;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\DefinedRules
 */
class DefinedRulesTest extends \PHPUnit\Framework\TestCase
{
    // verify all concrete implementations of sndsgd\schema\Rule are
    // defined in the SNDSGD_SCHEMA_RULES constant
    public function testSndsgdSchemaRulesConstant(): void
    {
        $srcdir = \realpath(__DIR__ . "/../../src");
        $dir = new \RecursiveDirectoryIterator($srcdir);
        foreach (new \RecursiveIteratorIterator($dir) as $entity) {
            if ($entity->isFile() && $entity->getExtension() === "php") {
                require_once $entity->getRealPath();
            }
        }

        $definedClasses = \array_flip(DefinedRules::SNDSGD_SCHEMA_RULES);
        $constName = DefinedRules::class . "::SNDSGD_SCHEMA_RULES";

        foreach (\get_declared_classes() as $class) {
            if (!in_array(Rule::class, class_implements($class), true)) {
                continue;
            }

            // only test for rules in the schema dir
            if (!Str::beginsWith($class, "sndsgd\\schema\\")) {
                continue;
            }

            $this->assertTrue(
                isset($definedClasses[$class]),
                "the rule '$class' must be defined in '$constName'",
            );
        }
    }

    public function testCount(): void
    {
        $this->assertSame(
            count(DefinedRules::SNDSGD_SCHEMA_RULES),
            count(DefinedRules::create()),
        );
    }

    public function testAddRuleUndefinedClass(): void
    {
        $rules = DefinedRules::create();
        $class = "ThisClassDoesNotExist";
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "failed to add rule; class '$class' does not exist",
        );
        $rules->addRule($class);
    }

    public function testAddRuleWithoutProperImplementation()
    {
        $rules = DefinedRules::create();
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            "failed to add rule; class 'stdClass' does not implement",
        );
        $rules->addRule(\stdClass::class);
    }

    public function testAddRuleDuplicate(): void
    {
        $this->expectException(DuplicateRuleException::class);
        $this->expectExceptionMessage(
            "the rule 'array' is already defined by 'sndsgd\\schema\\rules\\ArrayRule'",
        );
        (DefinedRules::create())->addRule(\sndsgd\schema\rules\ArrayRule::class);
    }

    public function testAddRuleInvalidName()
    {
        $instance = new class() implements \sndsgd\schema\Rule {
            public static function getName(): string
            {
                return "000000";
            }
            public static function getAcceptableTypes(): array
            {
                return ["string"];
            }
            public function getSummary(): string
            {
                return "test";
            }
            public function getDescription(): string
            {
                return "test";
            }
            public function validate($value, string $path = "$")
            {
                return $value;
            }
        };

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("invalid rule name '");
        (DefinedRules::create())->addRule($instance::class);
    }

    public function testInstantiateRuleUndefinedNameException(): void
    {
        $name = "nope";
        $rules = DefinedRules::create();
        $this->expectException(UndefinedRuleException::class);
        $this->expectExceptionMessage("rule for '$name' not defined");
        $rules->instantiateRule($name, []);
    }
}
