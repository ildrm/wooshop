<?php
declare(strict_types=1);
namespace WooShop\Commerce\Inventory;

/** Implement only in an approved adapter that verifies provider truth, order, amount and currency. */
interface CaptureVerifier
{
    public function requireCapture(string $orderId, string $captureReference): void;
}
