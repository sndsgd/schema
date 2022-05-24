<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\ReadableFileRule
 */
class ReadableFileRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(
            "readableFile",
            ReadableFileRule::getName(),
        );
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(
            ["string"],
            ReadableFileRule::getAcceptableTypes(),
        );
    }

    public function testExecuteYamlCallback(): void
    {
        $result = ReadableFileRule::executeYamlCallback("!rule/readableFile", "", 0, new ParserContext());
        $this->assertSame(["rule" => "readableFile"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/readableFile'");
        ReadableFileRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/readableFile' yaml callback cannot be used with a value");
        ReadableFileRule::executeYamlCallback("!rule/readableFile", "nope", 0, new ParserContext());
    }

    public function testGetSummary(): void
    {
        $this->assertSame(
            "readable file",
            (new ReadableFileRule())->getSummary(),
        );
    }

    public function testGetDescription(): void
    {
        $this->assertSame(
            "must be a readable file path",
            (new ReadableFileRule())->getDescription(),
        );
    }

    public function testValidate(): void
    {
        $this->assertSame(
            __FILE__,
            (new ReadableFileRule())->validate(__FILE__),
        );
    }

    public function testValidateFailure(): void
    {
        $rule = new ReadableFileRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate(__DIR__);
    }
}
