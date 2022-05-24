<?php declare(strict_types=1);

namespace sndsgd\schema;

interface Type
{
    /**
     * Retrieve a list of the type's dependencies
     *
     * @param array $doc The yaml doc for the type
     * @return array<string> A list of the types that are required
     */
    public static function getDependencies(array $doc): array;

    public function getName(): string;
    public function getDescription(): string;
    public function getParentName(): string;
    public function getRules(): RuleList;
    public function getSignature(): string;
}
