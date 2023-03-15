<?php declare(strict_types=1);

namespace sndsgd\schema;

use Exception;
use sndsgd\yaml\Document as YamlDocument;

class YamlDoc extends YamlDocument
{
    public static function create(
        string $path,
        int $index,
        array $doc,
    ): ?self {
        // the '.type.yaml' suffix is legacy. we want to be able to
        // include type definitions in yaml files with docs that
        // include other things, and in that case we'll require
        // `kind: type` attribute.
        if (
            !str_ends_with($path, ".type.yaml")
            && ($doc["kind"] ?? "") !== "type"
        ) {
            return null;
        }

        self::ensureNonEmptyString($doc, "name");
        self::ensureNonEmptyString($doc, "type");

        return new self(...func_get_args());
    }

    private static function ensureNonEmptyString(
        array $doc,
        string $key,
    ): string {
        if (!isset($doc[$key])) {
            throw new Exception(
                sprintf(
                    "missing required attribute '%s'",
                    $key,
                ),
            );
        }

        if (!is_string($doc[$key]) || $doc[$key] === "") {
            throw new Exception(
                sprintf(
                    "attribute '%s' must be a non empty string",
                    $key,
                ),
            );
        }

        return $doc[$key];
    }

    private function __construct(
        public readonly string $path,
        public readonly int $index,
        public readonly array $doc,
    ) {}

    public function getName(): string
    {
        return $this->doc["name"];
    }

    public function getType(): string
    {
        return $this->doc["type"];
    }
}
