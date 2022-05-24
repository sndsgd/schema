<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\EqualRule
 */
class EqualRuleTest extends TestCase
{
    private const DEFAULT_EQUAL_ARGUMENT = 123;

    public function testGetName(): void
    {
        $this->assertSame("equal", EqualRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string", "integer", "float"], EqualRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = EqualRule::executeYamlCallback("!rule/equal", "hi", 0, new ParserContext());
        $this->assertSame(["rule" => "equal", "equal" => "hi"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/equal'");
        EqualRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/equal' can only be used with a scalar value");
        EqualRule::executeYamlCallback("!rule/equal", [], 0, new ParserContext());
    }

    public function testGetSummary(): void
    {
        $rule = new EqualRule(self::DEFAULT_EQUAL_ARGUMENT);
        $this->assertSame("equal:" . self::DEFAULT_EQUAL_ARGUMENT, $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new EqualRule(self::DEFAULT_EQUAL_ARGUMENT);
        $this->assertSame("must be " . self::DEFAULT_EQUAL_ARGUMENT, $rule->getDescription());
    }

    public function testValidateFailure(): void
    {
        $rule = new EqualRule(self::DEFAULT_EQUAL_ARGUMENT);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate(456);
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($equal, $value): void
    {
        $rule = new EqualRule($equal);
        $this->assertEquals($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["please work", "please work"],
            [123, 123],
            [3.14, 3.14],
        ];
    }
}
