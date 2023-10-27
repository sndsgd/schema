<?php declare(strict_types=1);

namespace sndsgd\schema;

use RuntimeException;
use sndsgd\Classname;
use UnexpectedValueException;

class CodePathResolver
{
    public static function create(
        string $baseDir,
        string $fallbackDir = "",
    ): self {
        $baseDir = rtrim($baseDir, "/");
        $fallbackDir = rtrim($fallbackDir, "/");

        // if a fallback directory is provided, make sure that it is
        // in the base directory, but not the same as the base directory
        if (
            $fallbackDir !== ""
            && (
                !str_starts_with($fallbackDir, $baseDir)
                || substr_count($baseDir, "/") == substr_count($fallbackDir,"/")
            )
        ) {
            throw new UnexpectedValueException(
                "fallback directory must be a subdirectory of the base directory",
            );
        }

        if (!is_dir($baseDir)) {
            throw new UnexpectedValueException(
                "the provided path is not a valid directory",
            );
        }

        $composerJsonPath = "$baseDir/composer.json";
        if (!is_file($composerJsonPath)) {
            throw new UnexpectedValueException(
                "the provided path is not a valid directory",
            );
        }

        $json = file_get_contents($composerJsonPath);
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new UnexpectedValueException(
                "composer json contents are malformed; expecting an array",
            );
        }

        if (!isset($data["autoload"]["psr-4"])) {
            throw new UnexpectedValueException(
                "failed to locate psr-4 autoload map in composer json",
            );
        }

        $map = $data["autoload"]["psr-4"];
        foreach ($data["autoload-dev"]["psr-4"] ?? [] as $namespace => $paths) {
            if (!isset($map[$namespace])) {
                $map[$namespace] = $paths;
            } else {
                $map[$namespace] = array_unique(
                    array_merge((array) $map[$namespace], (array) $paths)
                );
            }
        }

        return new self($baseDir, $map, $fallbackDir);
    }

    public function __construct(
        private readonly string $baseDir,
        private readonly array $autoloadMap,
        private readonly string $fallbackDir = ""
    ) {}

    public function getPath(string $class): string
    {
        $class = Classname::toString($class);

        // attempt to locate the directory for a class by iterating the
        // psr-4 autoload maps
        foreach ($this->autoloadMap as $prefix => $paths) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            // the path can be a string or an array of strings
            $paths = (array) $paths;

            $ns = substr($class, strlen($prefix));
            $ns = str_replace("\\", "/", $ns);
            $ns .= ".php";

            $path = sprintf(
                "%s/%s/%s",
                $this->baseDir,
                rtrim($paths[array_key_last($paths)], "/"),
                $ns,
            );

            if (str_starts_with($path, "$this->baseDir/vendor")) {
                throw new RuntimeException(
                    "unable to resolve namespace paths in vendor directory",
                );
            }

            return $path;
        }

        // if a fallback directory was provided, use it
        if ($this->fallbackDir !== "") {
            return sprintf(
                "%s/%s.php",
                $this->fallbackDir,
                str_replace("\\", "/", $class),
            );
        }

        // if we make it here we are unable to determine the path
        throw new RuntimeException(
            "failed to determine path for '$class'; does your " .
            "composer.json define the appropriate psr-4 namespace?",
        );
    }

    public function isPathRenderable(string $path): bool
    {
        return (
            str_starts_with($path, $this->baseDir)
            && !str_starts_with($path, "$this->baseDir/vendor/")
        );
    }
}
