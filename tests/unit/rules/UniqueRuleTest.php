<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\UniqueRule
 */
class UniqueRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("unique", UniqueRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["array"], UniqueRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = UniqueRule::executeYamlCallback("!rule/unique", "", 0, new ParserContext());
        $this->assertSame(["rule" => "unique"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/unique'");
        UniqueRule::executeYamlCallback("nope", "", 0,  new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/unique' yaml callback cannot be used with a value");
        UniqueRule::executeYamlCallback("!rule/unique", "nope", 0,  new ParserContext());
    }

    public function testGetSummary(): void
    {
        $rule = new UniqueRule();
        $this->assertSame("unique", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new UniqueRule();
        $this->assertSame("all values must be unique", $rule->getDescription());
    }

    /**
     * @dataProvider provideValidateFailure
     */
    public function testValidateFailure(
        $value,
        string $exceptionClass,
        string $exceptionMessage,
    ): void {
        $rule = new UniqueRule();
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($exceptionMessage);
        $rule->validate($value);
    }

    public function provideValidateFailure(): iterable
    {
        $class = LogicException::class;
        $message = "refusing to validate a non array";

        yield ["not an array", $class, $message];
        yield [123, $class, $message];
        yield [null, $class, $message];

        $class = RuleValidationException::class;
        $message = "all values must be unique";

        // TODO this does not handle objects correctly!

        yield [[1,2,1], $class, $message];
        yield [[1,2,3,4,5,6,7,8,9,1], $class, $message];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new UniqueRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [[1]],
            [[1,2,3,4,5,6,7,8,9,10]],
            [["foo", "bar", "yep"]],
        ];
    }
}
