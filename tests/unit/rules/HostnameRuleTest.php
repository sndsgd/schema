<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\HostnameRule
 */
class HostnameRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("hostname", HostnameRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], HostnameRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new HostnameRule();
        $this->assertSame("hostname", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new HostnameRule();
        $this->assertSame("must be a valid hostname", $rule->getDescription());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = HostnameRule::executeYamlCallback("!rule/hostname", "", 0, new ParserContext());
        $this->assertSame(["rule" => "hostname"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/hostname'");
        HostnameRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/hostname' yaml callback cannot be used with a value");
        HostnameRule::executeYamlCallback("!rule/hostname", [], 0, new ParserContext());
    }

    public function testValidateFailure(): void
    {
        $rule = new HostnameRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("boop!");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new HostnameRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["google.com"],
            ["my-host"],
        ];
    }
}
