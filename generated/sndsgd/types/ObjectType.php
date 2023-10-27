<?php declare(strict_types=1);

namespace sndsgd\types;

/**
 * GENERATED CODE! DO NOT EDIT!
 * This file was generated by the sndsgd/schema library
 *
 * Name: sndsgd.types.ObjectType
 * Description: an object
 */
final class ObjectType implements \JsonSerializable, \sndsgd\schema\GeneratedType
{
    /**
     * @return \sndsgd\schema\types\ObjectType
     */
    public static function fetchType(): \sndsgd\schema\types\ObjectType
    {
        return unserialize('O:30:"sndsgd\\schema\\types\\ObjectType":6:{s:34:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'name";s:23:"sndsgd.types.ObjectType";s:41:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'description";s:9:"an object";s:35:"' . "\0" . 'sndsgd\\schema\\types\\BaseType' . "\0" . 'rules";O:22:"sndsgd\\schema\\RuleList":2:{s:29:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rules";a:1:{i:0;O:30:"sndsgd\\schema\\rules\\ObjectRule":2:{s:39:"' . "\0" . 'sndsgd\\schema\\rules\\ObjectRule' . "\0" . 'summary";s:11:"type:object";s:43:"' . "\0" . 'sndsgd\\schema\\rules\\ObjectRule' . "\0" . 'description";s:17:"must be an object";}}s:35:"' . "\0" . 'sndsgd\\schema\\RuleList' . "\0" . 'rulesByName";a:0:{}}s:42:"' . "\0" . 'sndsgd\\schema\\types\\ObjectType' . "\0" . 'properties";O:26:"sndsgd\\schema\\PropertyList":1:{s:38:"' . "\0" . 'sndsgd\\schema\\PropertyList' . "\0" . 'properties";a:0:{}}s:50:"' . "\0" . 'sndsgd\\schema\\types\\ObjectType' . "\0" . 'requiredProperties";a:0:{}s:40:"' . "\0" . 'sndsgd\\schema\\types\\ObjectType' . "\0" . 'defaults";a:0:{}}');
    }


    public function __construct(
        $values,
        bool $ignoreRequired = false,
        string $path = "$"
    ) {
        $values = (new \sndsgd\schema\rules\ObjectRule(
            summary: 'type:object',
            description: _('must be an object'),
        ))->validate($values, $path);
        $errors = new \sndsgd\schema\ValidationErrorList();

        foreach ($values as $name => $value) {
            $errors->addError(
                "$path.$name",
                _('unknown property')
            );
        }

        if (count($errors)) {
            throw $errors->createException();
        }
    }


    public function jsonSerialize(): array
    {
        $ret = [];
        return $ret;
    }
}
