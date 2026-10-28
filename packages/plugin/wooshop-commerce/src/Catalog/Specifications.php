<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Support\DomainError;

final class Specifications
{
    private Database $db;
    private EntityStore $store;
    private Products $products;

    public function __construct(Database $db, EntityStore $store, Products $products) { $this->db = $db; $this->store = $store; $this->products = $products; }

    public static function requirePublisher(): void
    {
        if (!current_user_can('commerce.catalog.publish') && !current_user_can('manage_woocommerce')) { throw new DomainError('forbidden', 'Catalog publication permission is required.', 403); }
    }

    /** Authorized internal adapter. Published schemas are immutable, including historical field definitions. */
    public function publishSchema(array $input): array
    {
        self::requirePublisher();
        Input::keys($input, ['key', 'version', 'fields'], ['key', 'version', 'fields']);
        $key = Input::text($input['key'], 1, 64);
        if (!preg_match('/^[a-z][a-z0-9_.-]*$/D', $key)) { throw new DomainError('invalid_schema_key', 'Use a stable schema key.', 422); }
        $version = Input::positiveInt($input['version']);
        if (!is_array($input['fields']) || count($input['fields']) < 1 || count($input['fields']) > 100 || !array_is_list($input['fields'])) { throw new DomainError('invalid_fields', 'A schema requires 1–100 fields.', 422); }
        $fields = [];
        foreach ($input['fields'] as $field) {
            if (!is_array($field)) { throw new DomainError('invalid_field', 'Invalid schema field.', 422); }
            Input::keys($field, ['key', 'type', 'label', 'required', 'unit', 'allowed_units', 'min', 'max', 'options', 'evidence_required', 'safety', 'facet', 'compare'], ['key', 'type', 'label', 'required']);
            $field['key'] = Input::text($field['key'], 1, 80);
            if (!preg_match('/^[a-z][a-z0-9_.-]*$/D', $field['key']) || isset($fields[$field['key']])) { throw new DomainError('invalid_field_key', 'Field keys must be unique stable identifiers.', 422); }
            if (!in_array($field['type'], ['text', 'number', 'boolean', 'enum', 'evidence'], true)) { throw new DomainError('invalid_field_type', 'Unsupported field type.', 422); }
            $field['label'] = Input::text($field['label'], 1, 120);
            foreach (['required', 'evidence_required', 'safety', 'facet', 'compare'] as $flag) { $field[$flag] = Input::bool($field[$flag] ?? ($flag === 'compare')); }
            $field['unit'] = $field['unit'] ?? null;
            if ($field['unit'] !== null) {
                if (!is_string($field['unit']) || $field['type'] !== 'number') { throw new DomainError('invalid_unit', 'Only numeric fields accept measurement units.', 422); }
                $dimension = Units::dimension($field['unit']);
                $field['allowed_units'] = $field['allowed_units'] ?? [$field['unit']];
                if (!is_array($field['allowed_units']) || count($field['allowed_units']) > 10 || !in_array($field['unit'], $field['allowed_units'], true)) { throw new DomainError('invalid_unit', 'Declare the canonical and supported input units.', 422); }
                foreach ($field['allowed_units'] as $unit) { if (!is_string($unit) || Units::dimension($unit) !== $dimension) { throw new DomainError('incompatible_unit', 'Schema units must share a dimension.', 422); } }
            }
            foreach (['min', 'max'] as $bound) { if (isset($field[$bound])) { Units::decimal($field[$bound]); } }
            if (isset($field['min'], $field['max']) && Units::decimal($field['min']) > Units::decimal($field['max'])) { throw new DomainError('invalid_range', 'Minimum must not exceed maximum.', 422); }
            if ($field['type'] === 'enum') {
                if (!isset($field['options']) || !is_array($field['options']) || count($field['options']) < 1 || count($field['options']) > 100 || count(array_unique($field['options'], SORT_REGULAR)) !== count($field['options'])) { throw new DomainError('invalid_enum', 'Declare unique allowed values.', 422); }
                foreach ($field['options'] as $option) { Input::text($option, 1, 120); }
            }
            $fields[$field['key']] = $field;
        }
        $id = Input::stableId('spec_schema:' . $key . ':' . $version);
        if ($version > 1) {
            $previous = $this->store->get('spec_schema', Input::stableId('spec_schema:' . $key . ':' . ($version - 1)));
            if (!$previous) { throw new DomainError('schema_revision_gap', 'Publish schema revisions in order.', 422); }
            $oldFields = array_column($previous['data']['fields'], null, 'key');
            foreach ($fields as $fieldKey => $field) {
                if (!isset($oldFields[$fieldKey])) { continue; } $old = $oldFields[$fieldKey];
                if ($old['type'] !== $field['type'] || $old['safety'] !== $field['safety'] || $old['evidence_required'] !== $field['evidence_required'] || ($old['unit'] === null) !== ($field['unit'] === null) || ($old['unit'] !== null && Units::dimension($old['unit']) !== Units::dimension($field['unit']))) { throw new DomainError('new_field_key_required', 'Changed field meaning or evidence classification requires a new field key and reviewed migration.', 422); }
            }
        }
        $data = ['key' => $key, 'schema_version' => $version, 'fields' => array_values($fields), 'actor' => (string) get_current_user_id()];
        $existing = $this->store->get('spec_schema', $id);
        if ($existing) {
            if ($existing['data']['fields'] !== $data['fields']) { throw new DomainError('immutable_schema', 'Publish a new schema version to change field definitions.', 409); }
            return $existing;
        }
        return $this->store->create('spec_schema', $data, 'catalog', $id);
    }

    public function schemas(int $limit = 20, ?string $cursor = null): array
    {
        $records = $this->store->find('spec_schema', 'catalog', $limit, $cursor);
        return ['items' => array_map([$this, 'schemaProjection'], $records), 'next_cursor' => count($records) === $limit ? end($records)['id'] : null];
    }

    public function schemaProjection(array $record): array
    {
        return ['id' => $record['id'], 'key' => $record['data']['key'], 'version' => $record['data']['schema_version'], 'fields' => array_map(static function (array $field): array {
            return array_intersect_key($field, array_flip(['key', 'type', 'unit', 'required', 'label']));
        }, $record['data']['fields'])];
    }

    public function evidence(array $input): array
    {
        self::requirePublisher();
        Input::keys($input, ['product_ids', 'source_uri', 'document_sha256', 'asserted_at', 'expires_at', 'scope', 'safety'], ['product_ids', 'source_uri', 'document_sha256', 'asserted_at', 'expires_at', 'scope', 'safety']);
        if (!is_array($input['product_ids']) || count($input['product_ids']) < 1 || count($input['product_ids']) > 20) { throw new DomainError('invalid_evidence', 'Evidence requires bounded product scope.', 422); }
        foreach ($input['product_ids'] as $id) { $this->products->get(Input::id($id)); }
        $uri = Input::text($input['source_uri'], 8, 1000);
        if (!filter_var($uri, FILTER_VALIDATE_URL) || strtolower((string) parse_url($uri, PHP_URL_SCHEME)) !== 'https' || parse_url($uri, PHP_URL_USER) !== null || parse_url($uri, PHP_URL_PASS) !== null) { throw new DomainError('invalid_source', 'An HTTPS source reference without credentials is required.', 422); }
        if (!is_string($input['document_sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $input['document_sha256'])) { throw new DomainError('invalid_digest', 'A source document SHA-256 is required.', 422); }
        foreach (['asserted_at', 'expires_at'] as $date) { if (!is_string($input[$date]) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $input[$date]) || strtotime($input[$date]) === false) { throw new DomainError('invalid_date', 'UTC ISO8601 evidence dates are required.', 422); } }
        if (strtotime($input['asserted_at']) > time() || strtotime($input['expires_at']) <= time()) { throw new DomainError('expired_evidence', 'Evidence dates are invalid or expired.', 422); }
        $input['scope'] = Input::text($input['scope'], 1, 2000);
        $input['safety'] = Input::bool($input['safety']);
        return $this->store->create('catalog_evidence', $input + ['state' => 'pending', 'submitted_by' => (string) get_current_user_id(), 'reviewers' => []], 'catalog');
    }

    public function reviewEvidence(string $id, int $revision, string $action): array
    {
        if (!current_user_can('commerce.evidence.approve')) { throw new DomainError('forbidden', 'Specialist approval permission is required.', 403); }
        $record = $this->store->get('catalog_evidence', Input::uuid($id));
        if (!$record) { throw new DomainError('not_found', 'Evidence unavailable.', 404); }
        Records::expect($record, $revision);
        if (!in_array($action, ['approve', 'revoke'], true)) { throw new DomainError('invalid_action', 'Unsupported evidence review action.', 422); }
        $actor = (string) get_current_user_id();
        if ($record['data']['submitted_by'] === $actor) { throw new DomainError('self_approval', 'Another specialist must review the evidence.', 403); }
        $data = $record['data'];
        $data['reviewers'] = array_values(array_unique(array_merge($data['reviewers'], [$actor])));
        $data['state'] = $action === 'revoke' ? 'revoked' : (count($data['reviewers']) >= ($data['safety'] ? 2 : 1) ? 'approved' : 'pending');
        $result = $this->store->update('catalog_evidence', $record['id'], $revision, $data);
        do_action('wooshop_commerce_evidence_changed', $record['id'], $result['version']);
        return $result;
    }

    public function approvedEvidence(?string $id, string $product, bool $safety = false): ?array
    {
        if (!$id) { return null; }
        $record = $this->store->get('catalog_evidence', Input::uuid($id));
        if (!$record || $record['data']['state'] !== 'approved' || !in_array($product, $record['data']['product_ids'], true) || strtotime($record['data']['expires_at']) <= time() || ($safety && !$record['data']['safety'])) { return null; }
        return $record;
    }

    public function publish(string $productId, array $input, int $revision): array
    {
        self::requirePublisher();
        $this->products->get($productId);
        if (!current_user_can('edit_post', (int) $productId)) { throw new DomainError('not_found', 'Product unavailable.', 404); }
        Input::keys($input, ['schema_id', 'schema_version', 'product_id', 'values', 'version'], ['schema_id', 'schema_version', 'product_id', 'values', 'version']);
        if ($input['product_id'] !== $productId || Input::positiveInt($input['version']) !== $revision) { throw new DomainError('revision_conflict', 'Product and revision must match this resource.', 412); }
        $schema = $this->store->get('spec_schema', Input::uuid($input['schema_id']));
        if (!$schema || $schema['data']['schema_version'] !== Input::positiveInt($input['schema_version'])) { throw new DomainError('schema_unavailable', 'Published schema revision unavailable.', 422); }
        if (!is_array($input['values']) || !array_is_list($input['values']) || count($input['values']) > 100) { throw new DomainError('invalid_values', 'A bounded values list is required.', 422); }
        $fields = array_column($schema['data']['fields'], null, 'key');
        $values = [];
        foreach ($input['values'] as $value) {
            if (!is_array($value)) { throw new DomainError('invalid_value', 'Typed specification values are required.', 422); }
            Input::keys($value, ['key', 'value', 'unit', 'evidence_ref'], ['key', 'value']);
            $key = Input::text($value['key'], 1, 80);
            if (!isset($fields[$key]) || isset($values[$key])) { throw new DomainError('invalid_field', 'Unknown or duplicate specification field.', 422); }
            $field = $fields[$key];
            $value['unit'] = $value['unit'] ?? null;
            $value['evidence_ref'] = $value['evidence_ref'] ?? null;
            if ($value['unit'] !== null && !is_string($value['unit'])) { throw new DomainError('invalid_unit', 'A measurement unit must be a string or null.', 422); }
            if ($value['evidence_ref'] !== null) { Input::uuid($value['evidence_ref']); }
            $normalized = $value['value'];
            if ($normalized !== null) {
                if ($field['type'] === 'number') {
                    Units::decimal($normalized);
                    if ($field['unit'] !== null) {
                        if (!in_array($value['unit'], $field['allowed_units'], true)) { throw new DomainError('incompatible_unit', 'This unit is not allowed for the field.', 422); }
                        $normalized = Units::convert($normalized, $value['unit'], $field['unit']);
                    } elseif ($value['unit'] !== null) { throw new DomainError('invalid_unit', 'This field has no measurement unit.', 422); }
                    foreach (['min', 'max'] as $bound) {
                        if (isset($field[$bound]) && ($bound === 'min' ? Units::decimal($normalized) < Units::decimal($field[$bound]) : Units::decimal($normalized) > Units::decimal($field[$bound]))) { throw new DomainError('value_out_of_range', 'Measurement is outside the allowed range.', 422); }
                    }
                    $normalized = Units::format(Units::decimal($normalized));
                } elseif ($field['type'] === 'boolean') { $normalized = Input::bool($normalized); }
                else { $normalized = Input::text($normalized, 1, 500); if ($field['type'] === 'enum' && !in_array($normalized, $field['options'], true)) { throw new DomainError('invalid_enum', 'Value is not an allowed choice.', 422); } }
                if (($field['evidence_required'] || $field['safety'] || $field['type'] === 'evidence') && !$this->approvedEvidence($value['evidence_ref'], $productId, $field['safety'])) { throw new DomainError('evidence_required', 'Current specialist-approved evidence is required for this claim.', 422); }
            }
            $values[$key] = $value + ['normalized' => $normalized, 'canonical_unit' => $field['unit']];
        }
        foreach ($fields as $field) { if ($field['required'] && (!isset($values[$field['key']]) || $values[$field['key']]['value'] === null)) { throw new DomainError('required_claim_missing', 'A required field is not supplied.', 422); } }
        $id = Input::stableId('spec_values:' . $productId);
        $existing = $this->store->get('spec_values', $id);
        if ($existing) { Records::expect($existing, $revision); }
        elseif ($revision !== 1) { throw new DomainError('stale_revision', 'Initial revision is 1.', 412); }
        $data = ['schema_id' => $schema['id'], 'schema_version' => $input['schema_version'], 'product_id' => $productId, 'values' => array_values($values), 'actor' => (string) get_current_user_id()];
        $result = $this->db->transaction(function () use ($id, $existing, $data, $revision, $productId): array {
            if ($existing) { $this->store->create('spec_snapshot', $existing['data'] + ['source_revision' => $existing['version']], 'product:' . $productId); }
            return $existing ? $this->store->update('spec_values', $id, $revision, $data) : $this->store->create('spec_values', $data, 'product:' . $productId, $id);
        });
        do_action('wooshop_commerce_specification_changed', $productId, $result['version']);
        return $this->projection($result);
    }

    public function record(string $productId): ?array { return $this->store->get('spec_values', Input::stableId('spec_values:' . $productId)); }

    public function read(string $productId): array
    {
        $this->products->get($productId);
        $record = $this->record($productId);
        if (!$record) { throw new DomainError('not_found', 'No approved specifications have been supplied.', 404); }
        return $this->projection($record);
    }

    public function projection(array $record): array
    {
        $data = $record['data'];
        $schema = $this->store->get('spec_schema', $data['schema_id']);
        $fields = $schema ? array_column($schema['data']['fields'], null, 'key') : [];
        $public = [];
        foreach ($data['values'] as $value) {
            $field = $fields[$value['key']] ?? null;
            if (!$field) { continue; }
            if (($field['safety'] || $field['evidence_required'] || $field['type'] === 'evidence') && !$this->approvedEvidence($value['evidence_ref'], $data['product_id'], $field['safety'])) { continue; }
            $public[] = ['key' => $value['key'], 'value' => $value['normalized'], 'unit' => $value['canonical_unit'], 'evidence_ref' => $value['evidence_ref']];
        }
        return ['schema_id' => $data['schema_id'], 'schema_version' => $data['schema_version'], 'product_id' => $data['product_id'], 'values' => $public, 'version' => $record['version']];
    }

    public function fields(string $schemaId): array
    {
        $schema = $this->store->get('spec_schema', $schemaId);
        return $schema ? array_column($schema['data']['fields'], null, 'key') : [];
    }
}
