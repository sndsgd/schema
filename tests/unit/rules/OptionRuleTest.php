<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\OptionRule
 */
class OptionRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("option", OptionRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(
            ["integer", "float", "string"],
            OptionRule::getAcceptableTypes(),
        );
    }

    public function testConstructorEmptyOptions(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("'options' must contain at least one option");
        new OptionRule([]);
    }

    public function testConstructorOptionsContainsNonScalar(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("all options must be scalar values");
        new OptionRule([null]);
    }

    public function testGetSummary(): void
    {
        $rule = new OptionRule(["a", "b", "c"]);
        $this->assertSame("option:['a','b','c']", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new OptionRule(["a", "b", "c"]);
        $this->assertSame(
            "must be one of the following values: 'a', 'b', 'c'",
            $rule->getDescription(),
        );
    }

    public function testValidateFailure(): void
    {
        $rule = new OptionRule([1, 2, 3]);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate(4);
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate(array $options, $value): void
    {
        $rule = new OptionRule($options);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [[42], 42],
            [[1, 2, 3], 1],
            [[1, 2, 3], 2],
            [[1, 2, 3], 3],
            [[1.1, 2.2], 1.1],
            [[1.1, 2.2], 2.2],
            [["foo", "bar", "baz"], "foo"],
            [["foo", "bar", "baz"], "bar"],
            [["foo", "bar", "baz"], "baz"],
        ];
    }
}
