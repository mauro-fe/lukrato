<?php

declare(strict_types=1);

namespace Application\DTO;

use Application\Enums\InsightType;

/**
 * Item individual de insight financeiro.
 * Substitui arrays associativos ['type' => string, 'icon' => string, ...] sem tipagem.
 */
readonly class InsightItemDTO
{
    public function __construct(
        public InsightType $type,
        public string      $icon,
        public string      $title,
        public string      $message,
        public ?float      $value = null,
        public ?float      $percentage = null,
    ) {}

    public function toArray(): array
    {
        $arr = [
            'type'    => $this->type->value,
            'icon'    => $this->icon,
            'title'   => $this->title,
            'message' => $this->message,
        ];

        if ($this->value !== null) {
            $arr['value'] = $this->value;
        }
        if ($this->percentage !== null) {
            $arr['percentage'] = $this->percentage;
        }

        return $arr;
    }
}
