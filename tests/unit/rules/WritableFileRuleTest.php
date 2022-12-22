<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\WritableFileRule
 */
class WritableFileRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame(
            "writableFile",
            WritableFileRule::getName(),
        );
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(
            ["string"],
            WritableFileRule::getAcceptableTypes(),
        );
    }

    public function testExecuteYamlCallback(): void
    {
        $result = WritableFileRule::executeYamlCallback("!rule/writableFile", "", 0, new ParserContext());
        $this->assertSame(["rule" => "writableFile"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/writableFile'");
        WritableFileRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/writableFile' yaml callback cannot be used with a value");
        WritableFileRule::executeYamlCallback("!rule/writableFile", "nope", 0, new ParserContext());
    }

    public function testGetSummary(): void
    {
        $this->assertSame(
            "writable file",
            (new WritableFileRule())->getSummary(),
        );
    }

    public function testGetDescription(): void
    {
        $this->assertSame(
            "must be a writable file path",
            (new WritableFileRule())->getDescription(),
        );
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate(
        string $path,
    ): void {
        $this->assertSame(
            $path,
            (new WritableFileRule())->validate($path),
        );
    }

    public function provideValidate(): iterable
    {
        yield [__FILE__];
        yield [__DIR__ . "/dir/not/created/yet"];
    }

    public function testValidateFailure(): void
    {
        $rule = new WritableFileRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("/not/gonna/work");
    }
}
