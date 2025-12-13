<?php

namespace App\DataTransferObjects;

/**
 * Data Transfer Object for aggregate request parameters
 */
final readonly class AggregateRequestDto
{
    public function __construct(
        public int $farmId,
        public string $date,
        public string $range
    ) {
    }
}
