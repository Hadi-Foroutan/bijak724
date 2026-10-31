<?php

namespace App\Services\Company\Report\Templates;

/**
 * One filter a report type accepts from the frontend (the period, ...). The backend decides which filters each type has;
 * the frontend only sends the type and the values of those filters.
 */
final class TemplateFilter
{
    public const DATE = 'date';

    /**
     * @param list<mixed> $rules validation rules of the value (without `nullable`/`required`, which come from `$required`);
     *                           `{prefix}` in a rule is replaced by the request key of the filters, e.g. `filters.`
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type,
        public readonly array  $rules,
        public readonly bool   $required = true,
    )
    {
    }

    public function with(?string $label, ?bool $required): self
    {
        return new self($this->key, $label ?? $this->label, $this->type, $this->rules, $required ?? $this->required);
    }

    /** @return list<mixed> the rules for `$prefix . key`, e.g. `filters.issued_from` */
    public function rulesFor(string $prefix): array
    {
        return [
            $this->required ? 'required' : 'nullable',
            ...array_map(fn(mixed $rule): mixed => is_string($rule) ? str_replace('{prefix}', $prefix, $rule) : $rule, $this->rules),
        ];
    }

    /** @return array{key: string, label: string, type: string, required: bool} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'type' => $this->type, 'required' => $this->required];
    }
}
