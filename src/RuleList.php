<?php declare(strict_types=1);

namespace sndsgd\schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use sndsgd\schema\exceptions\DuplicateRuleException;
use sndsgd\schema\NamedRule;
use sndsgd\schema\rules\AnyTypeRule;
use sndsgd\schema\rules\ArrayRule;
use sndsgd\schema\rules\BooleanRule;
use sndsgd\schema\rules\FloatRule;
use sndsgd\schema\rules\IntegerRule;
use sndsgd\schema\rules\ObjectRule;
use sndsgd\schema\rules\OneOfRule;
use sndsgd\schema\rules\StringRule;
use Traversable;

class RuleList implements Countable, IteratorAggregate
{
    public const TYPE_RULES = [
        AnyTypeRule::class => true,
        ArrayRule::class => true,
        BooleanRule::class => true,
        FloatRule::class => true,
        IntegerRule::class => true,
        ObjectRule::class => true,
        OneOfRule::class => true,
        StringRule::class => true,
    ];

    /**
     * @var array<Rule>
     */
    private array $rules = [];

    /**
     * A map of name and/or class to rule instance
     *
     * @var array<string,Rule>
     */
    private array $rulesByName = [];

    public function __construct(Rule ...$rules)
    {
        if (
            $rules === []
            || !isset(self::TYPE_RULES[get_class($rules[0])])
        ) {
            throw new LogicException(
                "a type rule must be provided as the first element",
            );
        }

        $typeRule = array_shift($rules);
        if (!($typeRule instanceof NamedRule)) {
            throw new LogicException(
                sprintf(
                    "the type rule (the first rule) must be an instance of '%s'",
                    NamedRule::class,
                ),
            );
        }
        $typeRuleName = $typeRule::getName();
        $this->rules = [$typeRule];

        foreach ($rules as $rule) {
            $class = $rule::class;

            if (isset(self::TYPE_RULES[$class])) {
                throw new LogicException(
                    "a type rule may only be used as the first element",
                );
            }

            if (isset($this->rulesByName[$class])) {
                throw new DuplicateRuleException(
                    "failed to add '$class' to rule list multiple times",
                );
            }

            if (
                !isset(self::TYPE_RULES[$class])
                && !in_array($typeRuleName, $rule::getAcceptableTypes(), true)
            ) {
                throw new LogicException(
                    sprintf(
                        "failed to add %s rule when type is %s; " .
                        "acceptable types are [%s]",
                        $class,
                        $typeRuleName,
                        implode(",", $rule::getAcceptableTypes()),
                    ),
                );
            }

            $this->rules[] = $rule;

            $this->rulesByName[$class] = $rule;
            if ($rule instanceof NamedRule) {
                $this->rulesByName[$rule::getName()] = $rule;
            }
        }
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator(array_filter($this->rules));
    }

    public function toArray(): array
    {
        return array_filter($this->rules);
    }

    public function getTypeRule(): Rule
    {
        return $this->rules[0];
    }

    public function getTypeName(): string
    {
        $typeRule = $this->getTypeRule();
        assert($typeRule instanceof NamedRule);
        return $typeRule::getName();
    }

    public function getRule(string $name): ?Rule
    {
        return $this->rulesByName[$name] ?? null;
    }
}
