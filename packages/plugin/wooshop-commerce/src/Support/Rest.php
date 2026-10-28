<?php
declare(strict_types=1);
namespace WooShop\Commerce\Support;

final class Rest
{
    public static function respond(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (DomainError $error) {
            return new \WP_Error($error->errorCode, $error->getMessage(), [
                'status' => $error->status, 'fields' => $error->details,
                'correlation_id' => Ids::uuid(),
            ]);
        } catch (\Throwable $error) {
            $correlation = Ids::uuid();
            error_log('wooshop failure ' . $correlation . ' ' . get_class($error));
            return new \WP_Error('operation_failed', __('The operation could not be completed. Please try again or contact support.', 'wooshop-commerce'), ['status' => 500, 'correlation_id' => $correlation]);
        }
    }

    public static function version(\WP_REST_Request $request): int
    {
        $value = $request->get_header('if-match');
        if (!$value) { throw new DomainError('precondition_required', 'The current If-Match resource version is required.', 428); }
        if (!preg_match('/^"([1-9][0-9]{0,15})"$/', $value, $match)) { throw new DomainError('invalid_version', 'The resource version is invalid.', 422); }
        return (int) $match[1];
    }

    public static function idempotency(\WP_REST_Request $request): string
    {
        $key = $request->get_header('idempotency-key');
        if (!is_string($key) || !preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $key)) {
            throw new DomainError('idempotency_required', 'Supply an Idempotency-Key of 16–128 safe characters.', 428);
        }
        return $key;
    }
}
