<?php
declare(strict_types=1);
namespace WooShop\Commerce\Settings;

use WooShop\Commerce\Support\DomainError;

final class Validator
{
    public function __construct(private readonly array $inventory) {}
    public function inventory(): array { return $this->inventory; }

    public function default(string $key): mixed
    {
        $row = $this->row($key);
        $value = $row['default'];
        return match ($row['type']) {
            'boolean' => $value === 'true',
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'asset_id' => $value === 'null' ? null : (int) $value,
            default => $value,
        };
    }

    public function validate(string $key, mixed $value, string $scope = 'site'): mixed
    {
        $row = $this->row($key);
        if ($row['type'] === 'unavailable') { throw new DomainError('setting_unavailable', 'Executable or unsafe customization is unavailable.'); }
        if (!in_array($scope, ['site', 'seller'], true) || !str_contains($row['scopes'], $scope)) { throw new DomainError('setting_scope', 'This setting is unavailable in the selected scope.', 403); }
        if ($row['owning_system'] === 'theme') { throw new DomainError('native_editor_required', 'Use WordPress Site Editor for this presentation setting; the plugin does not overwrite native saved styles.', 422); }
        $range = $row['valid_values_units_range'];
        // Business approvals are data, never toggles inferred from a request.
        if ($value === true && (str_contains($range, 'after-') || str_contains($range, 'until-') || str_contains($range, 'with verified'))) {
            throw new DomainError('approval_required', 'The required provider or human activation decision has not been recorded.', 409);
        }
        switch ($row['type']) {
            case 'boolean':
                if (!is_bool($value)) { $this->bad($key); }
                if ((str_starts_with($range, 'false only') || $range === 'false') && $value) { $this->bad($key); }
                if (($range === 'true' || str_starts_with($range, 'true required')) && !$value) { $this->bad($key); }
                break;
            case 'integer':
                if (!is_int($value)) { $this->bad($key); }
                if (preg_match('/^(\d+)\.\.(\d+)/', $range, $m) && ($value < (int) $m[1] || $value > (int) $m[2])) { $this->bad($key); }
                if ($range === 'positive' && $value < 1) { $this->bad($key); }
                if ($key === 'history.window_days' && !in_array($value, [30, 90, 180, 365], true)) { $this->bad($key); }
                break;
            case 'enum':
                $enums = match ($key) {
                    'compatibility.unknown_purchase_policy' => ['warn', 'block'],
                    'incentives.stacking' => ['exclusive-by-group'],
                    default => explode(';', $range),
                };
                if (!is_string($value) || !in_array($value, $enums, true)) { $this->bad($key); }
                if ($key === 'notifications.channel_default' && $value === 'approvedSMS') { throw new DomainError('provider_unavailable', 'No verified SMS provider is configured.', 409); }
                break;
            case 'color':
                if (!is_string($value) || !preg_match('/^#[a-fA-F0-9]{6}$/D', $value)) { $this->bad($key); }
                // Seller accent is a controlled palette; avoid arbitrary contrast-breaking choices.
                if ($scope === 'seller' && !in_array(strtoupper($value), ['#234D70', '#17663A', '#704C00'], true)) { $this->bad($key); }
                break;
            case 'asset_id':
                if ($value !== null && (!is_int($value) || $value < 1 || get_post_type($value) !== 'attachment' || !wp_attachment_is_image($value))) { $this->bad($key); }
                break;
            default:
                throw new DomainError('setting_not_implemented', 'This control is not implemented by the current plugin version.', 501);
        }
        return $value;
    }

    private function row(string $key): array
    {
        return $this->inventory[$key] ?? throw new DomainError('unknown_setting', 'This setting key is unknown.');
    }
    private function bad(string $key): never { throw new DomainError('invalid_setting', 'The setting value is outside its allowed type or range.', 422, [$key => 'Invalid value.']); }
}
