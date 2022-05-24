<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\DateTimeRule
 */
class DateTimeRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("datetime", DateTimeRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], DateTimeRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = DateTimeRule::executeYamlCallback("!rule/datetime", "", 0, new ParserContext());
        $this->assertSame(["rule" => "datetime"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/datetime'");
        DateTimeRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/datetime' yaml callback cannot be used with a value");
        DateTimeRule::executeYamlCallback("!rule/datetime", "nope", 0, new ParserContext());
    }

    public function testGetSummary(): void
    {
        $rule = new DateTimeRule();
        $this->assertSame("datetime:iso8610", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new DateTimeRule();
        $this->assertSame("must be a valid date and time", $rule->getDescription());
    }

    public function testValidateNonArray(): void
    {
        $rule = new DateTimeRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("nope");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate(string $value, string $expect): void
    {
        $rule = new DateTimeRule();
        $this->assertSame($expect, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["2000-01-01", "2000-01-01T00:00:00+00:00"],
        ];
    }
}
