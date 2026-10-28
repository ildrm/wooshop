<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class Compatibility
{
    private EntityStore $store; private Products $products; private Specifications $specs;
    public function __construct(EntityStore $store, Products $products, Specifications $specs) { $this->store = $store; $this->products = $products; $this->specs = $specs; }

    public function publishRule(array $input): array
    {
        if (!current_user_can('commerce.evidence.approve')) { throw new DomainError('forbidden', 'Specialist evidence approval is required.', 403); }
        Input::keys($input, ['product_id', 'target_product_id', 'evidence_id', 'region', 'model_revision', 'firmware_min', 'firmware_max', 'result', 'conditions'], ['product_id', 'target_product_id', 'evidence_id', 'region', 'result', 'conditions']);
        foreach (['product_id', 'target_product_id'] as $field) { $this->products->get(Input::id($input[$field])); }
        if ($input['product_id'] === $input['target_product_id']) { throw new DomainError('self_relation', 'Select two distinct products.', 422); }
        if (!$this->specs->approvedEvidence($input['evidence_id'], $input['product_id']) || !$this->specs->approvedEvidence($input['evidence_id'], $input['target_product_id'])) { throw new DomainError('evidence_required', 'Evidence must cover both exact products.', 422); }
        $input['region'] = Input::text($input['region'], 1, 20);
        $input['conditions'] = Input::text($input['conditions'], 1, 300);
        if (!in_array($input['result'], ['compatible', 'conditional', 'incompatible'], true)) { throw new DomainError('invalid_result', 'Invalid evidence result.', 422); }
        if (isset($input['model_revision'])) { $input['model_revision'] = Input::text($input['model_revision'], 1, 80); }
        foreach (['firmware_min', 'firmware_max'] as $field) { if (isset($input[$field]) && (!is_string($input[$field]) || !preg_match('/^\d{1,5}(?:\.\d{1,5}){0,3}$/D', $input[$field]))) { throw new DomainError('unsupported_version_scheme', 'Only an explicitly reviewed numeric dotted firmware scheme is supported.', 422); } }
        if (isset($input['firmware_min'], $input['firmware_max']) && version_compare($input['firmware_min'], $input['firmware_max'], '>')) { throw new DomainError('invalid_firmware_range', 'Firmware minimum exceeds maximum.', 422); }
        return $this->store->create('compatibility_rule', $input + ['published_by' => (string) get_current_user_id()], 'product:' . $input['product_id']);
    }

    public function evaluate(array $input): array
    {
        Input::keys($input, ['product_id', 'target_product_id', 'region', 'model_revision', 'firmware'], ['product_id', 'target_product_id', 'region']);
        foreach (['product_id', 'target_product_id'] as $field) { $this->products->get(Input::id($input[$field])); }
        foreach (['region' => 20, 'model_revision' => 80, 'firmware' => 80] as $field => $max) { if (isset($input[$field])) { Input::text($input[$field], 1, $max); } }
        $result = ['result' => 'unknown', 'reasons' => [__('No current reviewed evidence matches this exact context.', 'wooshop-commerce')], 'evidence_ids' => [], 'evaluated_at' => gmdate('Y-m-d\TH:i:s\Z'), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 60)];
        $matches = []; $missingContext = false;
        foreach ($this->store->find('compatibility_rule', 'product:' . $input['product_id'], 100) as $record) {
            $rule = $record['data'];
            if ($rule['target_product_id'] !== $input['target_product_id'] || $rule['region'] !== $input['region']) { continue; }
            $evidence = $this->specs->approvedEvidence($rule['evidence_id'], $input['product_id']);
            if (!$evidence || !$this->specs->approvedEvidence($rule['evidence_id'], $input['target_product_id'])) { continue; }
            if (isset($rule['model_revision']) && !isset($input['model_revision'])) { $missingContext = true; continue; }
            if (isset($rule['model_revision']) && $rule['model_revision'] !== $input['model_revision']) { continue; }
            if (isset($rule['firmware_min']) || isset($rule['firmware_max'])) {
                if (!isset($input['firmware']) || !preg_match('/^\d{1,5}(?:\.\d{1,5}){0,3}$/D', $input['firmware'])) { $missingContext = true; continue; }
                if ((isset($rule['firmware_min']) && version_compare($input['firmware'], $rule['firmware_min'], '<')) || (isset($rule['firmware_max']) && version_compare($input['firmware'], $rule['firmware_max'], '>'))) { continue; }
            }
            $matches[] = $rule;
            $result['expires_at'] = gmdate('Y-m-d\TH:i:s\Z', min(strtotime($result['expires_at']), strtotime($evidence['data']['expires_at'])));
        }
        if (!$matches) { if ($missingContext) { $result['reasons'] = [__('Model revision and reviewed firmware context are required.', 'wooshop-commerce')]; } return $result; }
        $specificity = max(array_map(static fn (array $rule): int => (int) isset($rule['model_revision']) + (int) isset($rule['firmware_min']) + (int) isset($rule['firmware_max']), $matches));
        $matches = array_values(array_filter($matches, static fn (array $rule): bool => ((int) isset($rule['model_revision']) + (int) isset($rule['firmware_min']) + (int) isset($rule['firmware_max'])) === $specificity));
        $states = array_unique(array_column($matches, 'result'));
        $result['result'] = count($states) === 1 ? reset($states) : 'unknown';
        $result['reasons'] = count($states) === 1 ? array_values(array_unique(array_column($matches, 'conditions'))) : [__('Reviewed evidence conflicts; specialist review is required.', 'wooshop-commerce')];
        $result['evidence_ids'] = array_values(array_unique(array_column($matches, 'evidence_id')));
        return $result;
    }
}
