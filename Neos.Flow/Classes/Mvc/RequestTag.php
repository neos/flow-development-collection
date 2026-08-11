<?php
namespace Neos\Flow\Mvc;

/**
 *
 */
final readonly class RequestTag implements \JsonSerializable
{
    public const VALUE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9.:]{0,63}$/';

    public const TYPE_PATTERN = '/[A-Za-z0-9]{1,32}/';

    public string $value;

    public string $type;

    /**
     * @param string $value
     * @param string $type
     */
    public function __construct(string $value, string $type)
    {
        if ($value === '') {
            throw new \InvalidArgumentException('RequestTag value cannot be empty', 1745266796);
        }

        if (strlen($value) > 64) {
            throw new \InvalidArgumentException('RequestTag value cannot be longer than 64 characters', 1745266792);
        }

        if (!preg_match(self::VALUE_PATTERN, $value)) {
            throw new \InvalidArgumentException('RequestTag value contains invalid characters, only A-Z, a-z, 0-9, ":" and "." are allowed ', 1745266788);
        }

        if (strlen($type) > 32) {
            throw new \InvalidArgumentException('RequestTag type cannot be longer than 32 characters', 1745266824);
        }

        if ($type !== '' && !preg_match(self::TYPE_PATTERN, $type)) {
            throw new \InvalidArgumentException('RequestTag type contains invalid characters, only A-Z, a-z, 0-9 are allowed.', 1745266895);
        }

        $this->value = $value;
        $this->type = $type;
    }

    /**
     * From serialized "TheType-TheValue.WithDots:AndColons"
     *
     * @param string $requestTag
     * @return self
     */
    public static function fromString(string $requestTag): RequestTag
    {
        $parts = explode('-', $requestTag);

        if (count($parts) > 2) {
            throw new \InvalidArgumentException('More than one separator found in RequestTag, "-" can only occur once.', 1745267312);
        }

        if (count($parts) === 2) {
            return new self($parts[1], $parts[0]);
        }

        // untyped
        return new self($parts[0], '');
    }

    public function matches(RequestTag $requestTag): bool
    {
        return $requestTag->type === $this->type && $requestTag->value === $this->value;
    }

    public function __toString(): string
    {
        $result = '';
        if ($this->type !== '') {
            $result .= $this->type;
            $result .= '-';
        }

        $result .= $this->value;

        return $result;
    }

    public function jsonSerialize(): string
    {
        return json_encode($this->__toString(), JSON_THROW_ON_ERROR);
    }
}
