<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;
use WooShop\Commerce\Merchandising\Merchandising;

final class Admin
{
    private EntityStore $store; private Specifications $specs; private Compatibility $compatibility; private Community $community; private Merchandising $merchandising;
    private string $message = ''; private bool $error = false;
    public function __construct(EntityStore $store, Specifications $specs, Compatibility $compatibility, Community $community, Merchandising $merchandising) { $this->store = $store; $this->specs = $specs; $this->compatibility = $compatibility; $this->community = $community; $this->merchandising = $merchandising; }

    public function register(): void
    {
        add_action('admin_menu', function (): void { add_submenu_page('woocommerce', __('Commerce catalog', 'wooshop-commerce'), __('Commerce catalog', 'wooshop-commerce'), 'read', 'wooshop-catalog', [$this, 'page']); });
        add_action('admin_init', function (): void {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || ($_POST['action'] ?? '') !== 'wooshop_catalog') { return; }
            try {
                $this->access(); check_admin_referer('wooshop_catalog');
                $post = wp_unslash($_POST); Input::keys($post, ['action', '_wpnonce', '_wp_http_referer', 'command', 'payload'], ['action', 'command', 'payload']);
                if (!is_string($post['payload']) || strlen($post['payload']) > 65536 || !is_object(json_decode($post['payload']))) { throw new DomainError('invalid_json', 'Enter a JSON object within 64 KiB.', 400); }
                $input = json_decode($post['payload'], true, 32, JSON_THROW_ON_ERROR);
                switch ($post['command']) {
                    case 'schema': $result = $this->specs->publishSchema($input); break;
                    case 'evidence': $result = $this->specs->evidence($input); break;
                    case 'evidence_review': Input::keys($input, ['id', 'version', 'action'], ['id', 'version', 'action']); $result = $this->specs->reviewEvidence(Input::uuid($input['id']), Input::positiveInt($input['version']), Input::text($input['action'], 1, 20)); break;
                    case 'specifications': $result = $this->specs->publish(Input::id($input['product_id'] ?? null), $input, Input::positiveInt($input['version'] ?? null)); break;
                    case 'compatibility': $result = $this->compatibility->publishRule($input); break;
                    case 'placement': $revision = Input::positiveInt($input['version'] ?? null); unset($input['version']); $result = $this->merchandising->placement($input, $revision); break;
                    case 'relationship': $revision = Input::positiveInt($input['version'] ?? null); unset($input['version']); $result = $this->merchandising->relationship($input, $revision); break;
                    case 'campaign': $revision = Input::positiveInt($input['version'] ?? null); unset($input['version']); $result = $this->merchandising->campaign($input, $revision); break;
                    case 'campaign_review': Input::keys($input, ['id', 'version'], ['id', 'version']); $result = $this->merchandising->approveCampaign(Input::uuid($input['id']), Input::positiveInt($input['version'])); break;
                    case 'question_review': Input::keys($input, ['id', 'version', 'action', 'answer', 'evidence_ref'], ['id', 'version', 'action']); $result = $this->community->moderate(Input::uuid($input['id']), Input::positiveInt($input['version']), Input::text($input['action'], 1, 20), $input['answer'] ?? null, $input['evidence_ref'] ?? null); break;
                    default: throw new DomainError('invalid_command', 'Choose an available catalog operation.', 400);
                }
                $this->message = __('Saved resource: ', 'wooshop-commerce') . ($result['id'] ?? $result['product_id'] ?? '') . ' · ' . __('revision', 'wooshop-commerce') . ' ' . ($result['version'] ?? 1);
            } catch (DomainError $error) { $this->message = $error->getMessage(); $this->error = true; }
            catch (\Throwable $error) { $this->message = __('The operation failed. Check the JSON and retry. No success was recorded.', 'wooshop-commerce'); $this->error = true; }
        });
    }

    private function access(): void
    {
        if (!current_user_can('manage_woocommerce') && !current_user_can('commerce.catalog.publish') && !current_user_can('commerce.evidence.approve') && !current_user_can('moderate_comments')) { throw new DomainError('forbidden', 'Catalog or moderation permission is required.', 403); }
    }

    public function page(): void
    {
        try { $this->access(); } catch (DomainError $error) { wp_die(esc_html($error->getMessage())); }
        echo '<div class="wrap"><h1>' . esc_html__('Commerce catalog and moderation', 'wooshop-commerce') . '</h1>';
        if ($this->message !== '') { echo '<div class="notice ' . ($this->error ? 'notice-error' : 'notice-success') . '" role="status"><p>' . esc_html($this->message) . '</p></div>'; }
        echo '<p>' . esc_html__('Publish immutable schema revisions, edit typed product values and review evidence. Safety claims need two specialist reviewers independent of the submitter. No source URL is fetched by this tool.', 'wooshop-commerce') . '</p><form method="post">';
        wp_nonce_field('wooshop_catalog'); echo '<input type="hidden" name="action" value="wooshop_catalog"><p><label for="commerce-command">' . esc_html__('Operation', 'wooshop-commerce') . '</label> <select name="command" id="commerce-command">';
        foreach (['schema' => __('Publish schema revision', 'wooshop-commerce'), 'evidence' => __('Submit source evidence', 'wooshop-commerce'), 'evidence_review' => __('Review or revoke evidence', 'wooshop-commerce'), 'specifications' => __('Publish product specifications', 'wooshop-commerce'), 'compatibility' => __('Publish reviewed compatibility rule', 'wooshop-commerce'), 'question_review' => __('Moderate question or answer', 'wooshop-commerce'), 'placement' => __('Publish curated placement', 'wooshop-commerce'), 'relationship' => __('Publish typed relationship', 'wooshop-commerce'), 'campaign' => __('Prepare campaign', 'wooshop-commerce'), 'campaign_review' => __('Approve campaign funding', 'wooshop-commerce')] as $key => $label) { echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>'; }
        echo '</select></p><p><label for="commerce-payload">' . esc_html__('Typed JSON payload', 'wooshop-commerce') . '</label></p><textarea id="commerce-payload" name="payload" class="large-text code" rows="12" required spellcheck="false">' . esc_textarea(wp_json_encode(['key' => 'equipment', 'version' => 1, 'fields' => [['key' => 'weight', 'type' => 'number', 'label' => __('Weight', 'wooshop-commerce'), 'required' => false, 'unit' => 'g', 'allowed_units' => ['g', 'kg'], 'min' => '0.000001', 'facet' => true, 'compare' => true]]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</textarea>';
        submit_button(__('Validate and save', 'wooshop-commerce')); echo '</form>';
        foreach (['spec_schema' => __('Published schemas', 'wooshop-commerce'), 'catalog_evidence' => __('Evidence review queue', 'wooshop-commerce'), 'product_question' => __('Question moderation queue', 'wooshop-commerce'), 'placement' => __('Curated placements', 'wooshop-commerce'), 'product_relationship' => __('Typed relationships', 'wooshop-commerce'), 'campaign' => __('Campaigns', 'wooshop-commerce')] as $kind => $title) {
            echo '<h2>' . esc_html($title) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__('Resource', 'wooshop-commerce') . '</th><th>' . esc_html__('Version', 'wooshop-commerce') . '</th><th>' . esc_html__('Data', 'wooshop-commerce') . '</th></tr></thead><tbody>';
            foreach ($this->store->find($kind, null, 100) as $record) { $data = $record['data']; unset($data['actor'], $data['submitted_by'], $data['reviewers'], $data['answer_author']); echo '<tr><td><code>' . esc_html($record['id']) . '</code></td><td>' . (int) $record['version'] . '</td><td><pre style="white-space:pre-wrap;overflow-wrap:anywhere;max-width:70em">' . esc_html(wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td></tr>'; }
            echo '</tbody></table>';
        }
        echo '</div>';
    }
}
