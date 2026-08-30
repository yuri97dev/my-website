<?php

namespace App\DataTransferObjects;

readonly class OptimizationResult
{
    public function __construct(
        public string $path,
        public int $original_size_bytes,
        public int $optimized_size_bytes,
        public bool $converted,
    ) {}

    public function savingsRatio(): float
    {
        if( $this->original_size_bytes <= 0 ) {
            return 0.0;
        }

        return 1 - ($this->optimized_size_bytes / $this->original_size_bytes);
    }
}
