<?php

namespace App\DTOs;

class ContentFilterData
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $category = null,
        public readonly ?string $tag = null,
        public readonly ?int $year = null,
        public readonly ?int $month = null,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            search: self::trimOrNull($attributes['q'] ?? null),
            category: self::trimOrNull($attributes['category'] ?? null),
            tag: self::trimOrNull($attributes['tag'] ?? null),
            year: isset($attributes['year']) ? (int) $attributes['year'] : null,
            month: isset($attributes['month']) ? (int) $attributes['month'] : null,
        );
    }

    public static function fromArchive(int $year, ?int $month = null): self
    {
        return new self(year: $year, month: $month);
    }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
