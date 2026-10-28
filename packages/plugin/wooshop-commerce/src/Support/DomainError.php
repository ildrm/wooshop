<?php
declare(strict_types=1);
namespace WooShop\Commerce\Support;

final class DomainError extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 422,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }
}
