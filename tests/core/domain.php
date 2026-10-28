<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/packages/plugin/wooshop-commerce/autoload.php';

use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Support\Ids;
use WooShop\Commerce\Support\SchemaValidator;
use WooShop\Commerce\Settings\Validator;

$checks = 0;
function check(bool $condition, string $message): void { global $checks; $checks++; if (!$condition) { throw new RuntimeException($message); } }
function denied(callable $work, string $code): void { try { $work(); } catch (DomainError $error) { check($error->errorCode === $code, 'Wrong rejection: ' . $error->errorCode); return; } throw new RuntimeException('Expected rejection: ' . $code); }
$contract = json_decode(file_get_contents(dirname(__DIR__, 2) . '/packages/plugin/wooshop-commerce/resources/openapi.json'), true, 128, JSON_THROW_ON_ERROR);
$schema = new SchemaValidator($contract['components']['schemas']);
for ($i = 0; $i < 40; $i++) { $id = Ids::uuid(); check(Ids::valid($id), 'Generated UUID invalid'); }
check(!Ids::valid('../invalid'), 'Traversal accepted as UUID');
$money = $contract['components']['schemas']['Money'];
foreach (['0', '123', '-123', '9000000000000000'] as $amount) { $schema->validate((object) ['amount_minor' => $amount, 'currency' => 'USD', 'exponent' => 2], $money); check(true, 'Exact money encoding'); }
denied(fn () => $schema->validate((object) ['amount_minor' => 123, 'currency' => 'USD', 'exponent' => 2], $money), 'invalid_input');
denied(fn () => $schema->validate((object) ['amount_minor' => '01', 'currency' => 'USD', 'exponent' => 2], $money), 'invalid_input');
denied(fn () => $schema->validate((object) ['amount_minor' => '1', 'currency' => 'USD', 'exponent' => 5], $money), 'invalid_input');
denied(fn () => $schema->validate((object) ['amount_minor' => '1', 'currency' => 'USD', 'exponent' => 2, 'owner' => 1], $money), 'invalid_input');
denied(fn () => $schema->validate((object) ['currency' => 'USD', 'exponent' => 2], $money), 'invalid_input');
$schema->validate((object) ['date' => '2026-02-28'], ['type' => 'object', 'properties' => ['date' => ['type' => 'string', 'format' => 'date']]]); check(true, 'Date accepted');
denied(fn () => $schema->validate('2026-02-30', ['type' => 'string', 'format' => 'date']), 'invalid_input');
denied(fn () => $schema->validate([1, 1], ['type' => 'array', 'uniqueItems' => true]), 'invalid_input');
denied(fn () => $schema->validate([1, 2, 3], ['type' => 'array', 'maxItems' => 2]), 'invalid_input');
$validator = new Validator(json_decode(file_get_contents(dirname(__DIR__, 2) . '/packages/plugin/wooshop-commerce/resources/settings.json'), true, 64, JSON_THROW_ON_ERROR));
check($validator->default('wishlist.max_items') === 100, 'Wishlist default');
check($validator->validate('wishlist.max_items', 200) === 200, 'Upper inclusive bound');
denied(fn () => $validator->validate('wishlist.max_items', 201), 'invalid_setting');
denied(fn () => $validator->validate('wishlist.max_items', '50'), 'invalid_setting');
denied(fn () => $validator->validate('marketplace.enabled', true), 'approval_required');
denied(fn () => $validator->validate('analytics.raw_query', true), 'invalid_setting');
denied(fn () => $validator->validate('compatibility.show_evidence', false), 'invalid_setting');
denied(fn () => $validator->validate('custom.javascript', '<script>'), 'setting_unavailable');
denied(fn () => $validator->validate('color.canvas', '#ffffff'), 'native_editor_required');
denied(fn () => $validator->validate('unknown.key', true), 'unknown_setting');
denied(fn () => $validator->validate('seller.accent', '#FFFFFF', 'seller'), 'invalid_setting');
check($validator->validate('seller.accent', '#234D70', 'seller') === '#234D70', 'Approved seller palette');
echo json_encode(['suite' => 'core domain', 'assertions' => $checks, 'result' => 'PASS'], JSON_THROW_ON_ERROR) . PHP_EOL;
