<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\EqualRule
 */
class RegexRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("regex", RegexRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], RegexRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = RegexRule::executeYamlCallback("!rule/regex", "/^[0-9]+$/", 0, new ParserContext());
        $this->assertSame(["rule" => "regex", "regex" => "/^[0-9]+$/"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/regex'");
        RegexRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/regex' must be used with a valid regex");
        RegexRule::executeYamlCallback("!rule/regex", "/^", 0, new ParserContext());
    }

    public function testConstructorInvalidRegexException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("'/a' is not a valid regex");
        new RegexRule("/a");
    }

    /**
     * @dataProvider provideGetSummary
     */
    public function testGetSummary(string $regex, string $expect): void
    {
        $rule = new RegexRule($regex);
        $this->assertSame($expect, $rule->getSummary());
    }

    public function provideGetSummary(): iterable
    {
        yield ["/^[0-9]+$/", "regex:/^[0-9]+$/"];
        yield ["/^[a-z0-9]+$/i", "regex:/^[a-z0-9]+$/i"];
    }

    public function testGetDescription(): void
    {
        $rule = new RegexRule("/[a-z]/");
        $this->assertSame("must match a regular expression '/[a-z]/'", $rule->getDescription());
    }

    public function testValidateFailure(): void
    {
        $rule = new RegexRule('/[a-z]/');
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate('456');
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($regex, $value): void
    {
        $rule = new RegexRule($regex);
        $this->assertEquals($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["/^[a-z]+$/", "abcdefg"],
        ];
    }
}
