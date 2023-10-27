<?php declare(strict_types=1);

namespace sndsgd\types;

/**
 * GENERATED CODE! DO NOT EDIT!
 * This file was generated by the sndsgd/schema library
 *
 * Name: sndsgd.types.MapType
 * Description: an object with key validation
 */
final class MapType implements \ArrayAccess, \Iterator, \JsonSerializable, \sndsgd\schema\GeneratedType
{
    /**
     * @return \sndsgd\schema\types\MapType
     */
    public static function fetchType(): \sndsgd\schema\types\MapType
    {
        return unserialize('O:27:"sndsgd\\schema\\types\\MapType":5:{s:34:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'name";s:20:"sndsgd.types.MapType";s:41:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'description";s:29:"an object with key validation";s:35:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'rules";O:22:"sndsgd\\schema\\RuleList":2:{s:29:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rules";a:1:{i:0;O:30:"sndsgd\\schema\\rules\\ObjectRule":2:{s:39:"' . "\0" . 'sndsgd\\schema\\rules\\ObjectRule' . "\0" . 'summary";s:11:"type:object";s:43:"' . "\0" . 'sndsgd\\schema\\rules\\ObjectRule' . "\0" . 'description";s:17:"must be an object";}}s:35:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rulesByName";a:0:{}}s:36:"' . "\0" . 'sndsgd\\schema\\types\\MapType' . "\0" . 'keyType";O:30:"sndsgd\\schema\\types\\ScalarType":4:{s:34:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'name";s:23:"sndsgd.types.StringType";s:41:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'description";s:8:"a string";s:35:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'rules";O:22:"sndsgd\\schema\\RuleList":2:{s:29:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rules";a:1:{i:0;O:30:"sndsgd\\schema\\rules\\StringRule":2:{s:39:"' . "\0" . 'sndsgd\\schema\\rules\\StringRule' . "\0" . 'summary";s:11:"type:string";s:43:"' . "\0" . 'sndsgd\\schema\\rules\\StringRule' . "\0" . 'description";s:16:"must be a string";}}s:35:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rulesByName";a:0:{}}s:42:"' . "\0" . 'sndsgd\\schema\\types\\ScalarType' . "\0" . 'parentName";s:0:"";}s:38:"' . "\0" . 'sndsgd\\schema\\types\\MapType' . "\0" . 'valueType";r:10;}');
    }

    private int $index = 0;
    private array $keys = [];
    private array $values = [];

    public function __construct($values, string $path = "$")
    {
        $values = (new \sndsgd\schema\rules\ObjectRule(
            summary: 'type:object',
            description: _('must be an object'),
        ))->validate($values, $path);
        $errors = new \sndsgd\schema\ValidationErrorList();

        foreach ($values as $key => $value) {
            try {
                $this->keys[] = $key = (new \sndsgd\types\StringType(
                    $key,
                    "$path.$key"
                ))->getValue();
            } catch (\sndsgd\schema\ValidationFailure $ex) {
                $errors->addErrors($ex->getValidationErrors());
                continue;
            }

            try {
                $this->values[$key] = (new \sndsgd\types\StringType(
                    $values->$key,
                    "$path.$key"
                ))->getValue();
            } catch (\sndsgd\schema\ValidationFailure $ex) {
                $errors->addErrors($ex->getValidationErrors());
            }
        }

        if (count($errors)) {
            throw $errors->createException();
        }
    }

    /** @see https://www.php.net/manual/en/class.arrayaccess.php */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->values[$offset]);
    }

    /** @see https://www.php.net/manual/en/class.arrayaccess.php */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->values[$offset];
    }

    /** @see https://www.php.net/manual/en/class.arrayaccess.php */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('setting values via ArrayAccess is disabled');
    }

    /** @see https://www.php.net/manual/en/class.arrayaccess.php */
    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('unsetting values via ArrayAccess is disabled');
    }

    /** @see https://www.php.net/manual/en/class.iterator.php */
    public function current(): mixed
    {
        return $this->values[$this->key()];
    }

    /** @see https://www.php.net/manual/en/class.iterator.php */
    public function key(): mixed
    {
        return $this->keys[$this->index];
    }

    /** @see https://www.php.net/manual/en/class.iterator.php */
    public function next(): void
    {
        $this->index++;
    }

    /** @see https://www.php.net/manual/en/class.iterator.php */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /** @see https://www.php.net/manual/en/class.iterator.php */
    public function valid(): bool
    {
        return isset($this->keys[$this->index]);
    }

    /** @see https://www.php.net/manual/en/class.jsonserializable.php */
    public function jsonSerialize(): array
    {
        return $this->getValues();
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
