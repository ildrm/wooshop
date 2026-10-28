<?php
declare(strict_types=1);
namespace WooShop\Commerce\Support;

/** The bounded JSON-Schema subset used by our transport contract. */
final class SchemaValidator
{
    public function __construct(private readonly array $schemas) {}

    public function validate(mixed $value, array $schema, string $path = '$'): void
    {
        if (isset($schema['$ref'])) {
            $name = basename($schema['$ref']);
            if (!isset($this->schemas[$name])) { throw new \LogicException('Unknown contract schema.'); }
            $this->validate($value, $this->schemas[$name], $path);
            return;
        }
        if (isset($schema['oneOf'])) {
            $matches = 0;
            foreach ($schema['oneOf'] as $candidate) {
                try { $this->validate($value, $candidate, $path); $matches++; } catch (DomainError) {}
            }
            if ($matches !== 1) { $this->invalid($path, 'Value must match exactly one allowed type.'); }
        }
        if (isset($schema['type'])) {
            $types = (array) $schema['type'];
            $matches = false;
            foreach ($types as $type) {
                $matches = $matches || match ($type) {
                    'null' => $value === null,
                    'string' => is_string($value),
                    'integer' => is_int($value),
                    'number' => is_int($value) || (is_float($value) && is_finite($value)),
                    'boolean' => is_bool($value),
                    'array' => is_array($value) && array_is_list($value),
                    'object' => $value instanceof \stdClass || (is_array($value) && !array_is_list($value)),
                    default => false,
                };
            }
            if (!$matches) { $this->invalid($path, 'Incorrect value type.'); }
        }
        if (isset($schema['enum']) && !in_array($value, $schema['enum'], true)) { $this->invalid($path, 'Value is not in the supported enumeration.'); }
        if (array_key_exists('const', $schema) && $value !== $schema['const']) { $this->invalid($path, 'Incorrect fixed value.'); }
        if (is_string($value)) {
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($length < ($schema['minLength'] ?? 0) || $length > ($schema['maxLength'] ?? PHP_INT_MAX)) { $this->invalid($path, 'Text length is outside the allowed range.'); }
            if (isset($schema['pattern']) && preg_match('~' . str_replace('~', '\\~', $schema['pattern']) . '~uD', $value) !== 1) { $this->invalid($path, 'Text has an invalid format.'); }
            if (($schema['format'] ?? '') === 'uuid' && !Ids::valid($value)) { $this->invalid($path, 'A UUID is required.'); }
            if (($schema['format'] ?? '') === 'date' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || !checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)))) { $this->invalid($path, 'A valid calendar date is required.'); }
            if (($schema['format'] ?? '') === 'date-time' && (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/D', $value) || strtotime($value) === false)) { $this->invalid($path, 'An RFC3339 timestamp is required.'); }
        }
        if (is_int($value) || is_float($value)) {
            if ($value < ($schema['minimum'] ?? -INF) || $value > ($schema['maximum'] ?? INF)) { $this->invalid($path, 'Number is outside the allowed range.'); }
        }
        if (is_array($value) && array_is_list($value)) {
            if (count($value) < ($schema['minItems'] ?? 0) || count($value) > ($schema['maxItems'] ?? PHP_INT_MAX)) { $this->invalid($path, 'List length is outside the allowed range.'); }
            if (!empty($schema['uniqueItems']) && count(array_unique(array_map(static fn ($item) => json_encode($item, JSON_THROW_ON_ERROR), $value))) !== count($value)) { $this->invalid($path, 'Duplicate list items are not allowed.'); }
            foreach ($value as $index => $item) { if (isset($schema['items'])) { $this->validate($item, $schema['items'], $path . '[' . $index . ']'); } }
        }
        if ($value instanceof \stdClass || (is_array($value) && !array_is_list($value))) {
            $object = (array) $value;
            if (count($object) > ($schema['maxProperties'] ?? PHP_INT_MAX)) { $this->invalid($path, 'Object contains too many properties.'); }
            foreach ($schema['required'] ?? [] as $key) { if (!array_key_exists($key, $object)) { $this->invalid($path . '.' . $key, 'Required field is missing.'); } }
            foreach ($object as $key => $item) {
                if (isset($schema['properties'][$key])) { $this->validate($item, $schema['properties'][$key], $path . '.' . $key); }
                elseif (($schema['additionalProperties'] ?? true) === false) { $this->invalid($path . '.' . $key, 'Unknown input field.'); }
                elseif (is_array($schema['additionalProperties'] ?? null)) { $this->validate($item, $schema['additionalProperties'], $path . '.' . $key); }
            }
        }
    }

    private function invalid(string $path, string $message): never
    {
        throw new DomainError('invalid_input', $message, 422, [$path => $message]);
    }
}
