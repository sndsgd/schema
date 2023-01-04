<?php declare(strict_types=1);

namespace sndsgd\schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use LogicException;
use Traversable;

class RuleList implements Countable, IteratorAggregate
{
    public const TYPE_RULES = [
        \sndsgd\schema\rules\AnyTypeRule::class => true,
        \sndsgd\schema\rules\ArrayRule::class => true,
        \sndsgd\schema\rules\BooleanRule::class => true,
        \sndsgd\schema\rules\FloatRule::class => true,
        \sndsgd\schema\rules\IntegerRule::class => true,
        \sndsgd\schema\rules\ObjectRule::class => true,
        \sndsgd\schema\rules\OneOfRule::class => true,
        \sndsgd\schema\rules\StringRule::class => true,
    ];

    /**
     * A map of all added rule classes for dupe detection
     *
     * @var array<string,string>
     */
    private array $addedClasses = [];

    /**
     * @var array<Rule>
     */
    private array $rules = [];

    public function __construct(Rule ...$rules)
    {
        if (
            empty($rules)
            || !isset(self::TYPE_RULES[get_class($rules[0])])
        ) {
            throw new LogicException(
                "a type rule must be provided as the first element",
            );
        }

        $typeRule = array_shift($rules);
        $typeRuleName = $typeRule::getName();
        $this->rules = [$typeRule];

        foreach ($rules as $rule) {
            $class = $rule::class;

            if (isset(self::TYPE_RULES[$class])) {
                throw new LogicException(
                    "a type rule may only be used as the first element",
                );
            }

            if (isset($this->addedClasses[$class])) {
                throw new \sndsgd\schema\exceptions\DuplicateRuleException(
                    "failed to add '$class' to rule list multiple times",
                );
            }

            if (
                !isset(self::TYPE_RULES[$class])
                && !in_array($typeRuleName, $rule::getAcceptableTypes(), true)
            ) {
                throw new \LogicException(
                    sprintf(
                        "failed to add %s rule when type is %s; " .
                        "acceptable types are [%s]",
                        $rule::getName(),
                        $typeRuleName,
                        implode(",", $rule::getAcceptableTypes()),
                    ),
                );
            }

            $this->addedClasses[$class] = $class;
            $this->rules[] = $rule;
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
        return $this->rules[0]->getName();
    }

    public function getRule(string $name): ?Rule
    {
        foreach ($this->rules as $rule) {
            if ($rule->getName() === $name) {
                return $rule;
            }
        }

        return null;
    }
}
