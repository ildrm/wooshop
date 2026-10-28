<?php
declare(strict_types=1);

namespace WooShop\Commerce\Marketplace;

use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class SellerService
{
    public const TEAM_CAPABILITIES = ['commerce.offer.write', 'commerce.allocation.fulfill', 'commerce.finance.read.own', 'commerce.export.own', 'commerce.team.manage'];
    private Records $r;
    public function __construct(private Database $db, private EntityStore $entities, private ?HumanVerifier $human = null) { $this->r = new Records($db); }

    public function apply(array $body, int $actor, string $key, int $publishedTermsVersion): array
    {
        Input::fields($body, ['display_name', 'public_slug', 'business_profile_ref', 'provider_onboarding_ref', 'accepted_terms_version'], ['display_name', 'public_slug', 'business_profile_ref', 'accepted_terms_version']);
        Input::text($body['display_name'], 120);
        Input::text($body['business_profile_ref'], 200);
        if (!is_string($body['public_slug']) || !preg_match('/^[a-z0-9-]{1,80}$/D', $body['public_slug']) || $actor < 1) { throw new DomainError('invalid_application', 'A valid slug and authenticated applicant are required.', 400); }
        if ($publishedTermsVersion < 1 || ($body['accepted_terms_version'] ?? 0) !== $publishedTermsVersion) { throw new DomainError('current_terms_required', 'Published current seller terms are required before submission.', 409); }
        if (isset($body['provider_onboarding_ref'])) { Input::text($body['provider_onboarding_ref'], 200); }
        return $this->r->command('seller-application:'.$actor, $key, $body, function () use ($body, $actor): array {
            $id = Records::id();
            $this->r->insert('sellers', ['id' => $id, 'applicant_id' => $actor, 'public_slug' => $body['public_slug'], 'display_name' => $body['display_name'], 'state' => 'application', 'provider_eligibility' => 'unverified', 'data' => Records::json($body), 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
            $this->r->insert('memberships', ['id' => Records::id(), 'seller_id' => $id, 'user_id' => $actor, 'capabilities' => Records::json(self::TEAM_CAPABILITIES), 'state' => 'active', 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
            return $this->project($this->r->one('sellers', $id));
        });
    }

    public function transition(string $id, array $body, int $version, int $actor, string $key): array
    {
        Input::fields($body, ['target_state', 'reason_code', 'evidence_ref'], ['target_state', 'reason_code']);
        Input::text($body['reason_code'], 80);
        if (isset($body['evidence_ref'])) { Input::text($body['evidence_ref'], 200); }
        $this->stepUp($actor, 'seller_transition', hash('sha256', Records::canonical([$id, $version, $body])));
        return $this->r->command('seller-transition:'.$actor.':'.$id, $key, [$body, $version], function () use ($id, $body, $version, $actor): array {
            $seller = $this->r->one('sellers', $id, true);
            Records::version($seller, $version);
            $w = $this->db->wpdb();
            $member = $w->get_var($w->prepare('SELECT user_id FROM '.$this->db->table('memberships').' WHERE seller_id=%s AND user_id=%d AND state<>%s', $id, $actor, 'revoked'));
            if ($member || (int) $seller['applicant_id'] === $actor) { throw new DomainError('independent_reviewer_required', 'A seller member cannot approve or review their own organization.', 403); }
            $transitions = ['application' => ['review'], 'review' => ['approved', 'rejected'], 'rejected' => ['review'], 'approved' => ['suspended', 'terminated'], 'suspended' => ['approved', 'terminated'], 'terminated' => []];
            $target = $body['target_state'];
            if (!in_array($target, $transitions[$seller['state']] ?? [], true)) { throw new DomainError('invalid_transition', 'Seller transition is not allowed.', 409); }
            if ($target === 'approved') { (new ActivationGate())->requireMarketplace(); }
            if ($target === 'terminated') {
                $open = (int) $w->get_var($w->prepare('SELECT COUNT(*) FROM '.$this->db->table('allocations').' WHERE seller_id=%s AND fulfillment_state NOT IN (%s,%s)', $id, 'delivered', 'cancelled'));
                // Provider liabilities/cases cannot be proven closed while integrations are unavailable.
                if ($open || $seller['state'] !== 'review') { throw new DomainError('open_obligations', 'Termination requires verified resolution or transfer of fulfillment, return, dispute and financial obligations.', 409); }
            }
            $this->r->execute($w->prepare('UPDATE '.$this->db->table('sellers').' SET state=%s,version=version+1,updated_at=%s WHERE id=%s AND version=%d', $target, $this->db->now(), $id, $version));
            $this->entities->create('seller.transition', ['seller_id' => $id, 'from' => $seller['state'], 'to' => $target, 'actor_id' => (string) $actor, 'reason_code' => $body['reason_code'], 'evidence_ref' => $body['evidence_ref'] ?? ''], $id);
            return $this->project($this->r->one('sellers', $id));
        });
    }

    public function invite(string $sellerId, array $body, int $actor, string $key): array
    {
        Input::fields($body, ['user_email', 'capabilities'], ['user_email', 'capabilities']);
        if (!is_string($body['user_email']) || strlen($body['user_email']) > 254 || !filter_var($body['user_email'], FILTER_VALIDATE_EMAIL) || !is_array($body['capabilities']) || !$body['capabilities'] || count($body['capabilities']) > 20 || count($body['capabilities']) !== count(array_unique($body['capabilities'])) || array_diff($body['capabilities'], self::TEAM_CAPABILITIES)) {
            throw new DomainError('invalid_invitation', 'A valid existing account and allowed unique capabilities are required.', 400);
        }
        $this->stepUp($actor, 'team_invite', hash('sha256', Records::canonical([$sellerId, $body])));
        return $this->r->command('team-invite:'.$actor.':'.$sellerId, $key, $body, function () use ($sellerId, $body, $actor): array {
            $seller = $this->r->one('sellers', $sellerId, true);
            if ($seller['state'] !== 'approved') { throw new DomainError('seller_unavailable', 'Team changes require an approved organization.', 409); }
            $w = $this->db->wpdb();
            $owner = $w->get_row($w->prepare('SELECT * FROM '.$this->db->table('memberships').' WHERE seller_id=%s AND user_id=%d AND state=%s FOR UPDATE', $sellerId, $actor, 'active'), ARRAY_A);
            $caps = $owner ? json_decode($owner['capabilities'], true) : [];
            if (!in_array('commerce.team.manage', $caps, true) || array_diff($body['capabilities'], $caps)) { throw new DomainError('delegation_denied', 'You may delegate only your current scoped capabilities.', 403); }
            $user = get_user_by('email', $body['user_email']);
            if (!$user) { throw new DomainError('invitation_unavailable', 'The invitation cannot be prepared for that account.', 422); }
            $id = Records::id();
            $this->r->insert('memberships', ['id' => $id, 'seller_id' => $sellerId, 'user_id' => $user->ID, 'capabilities' => Records::json($body['capabilities']), 'state' => 'invited', 'expires_at' => gmdate('Y-m-d H:i:s', strtotime($this->db->now().' UTC') + 604800), 'created_at' => $this->db->now(), 'updated_at' => $this->db->now()]);
            $op = $this->entities->create('membership.invitation', ['membership_id' => $id, 'seller_id' => $sellerId, 'user_id' => (string) $user->ID, 'state' => 'pending'], (string) $actor);
            return ['operation_id' => $op['id'], 'state' => 'pending', 'resource_id' => $id, 'correlation_id' => Records::id()];
        });
    }

    /** Account-bound, single use acceptance; invoked through a nonce-protected account form. */
    public function acceptInvitation(string $id, int $actor): array
    {
        return $this->db->transaction(function () use ($id, $actor): array {
            $hint = $this->r->one('memberships', $id);
            $seller = $this->r->one('sellers', $hint['seller_id'], true);
            $member = $this->r->one('memberships', $id, true);
            if ((int) $member['user_id'] !== $actor) { throw new DomainError('not_found', 'Resource not found.', 404); }
            if ($seller['state'] !== 'approved') { throw new DomainError('seller_unavailable', 'An invitation cannot be accepted while its organization is unavailable.', 409); }
            if ($member['state'] !== 'invited' || $member['expires_at'] <= $this->db->now()) { throw new DomainError('invitation_expired', 'Invitation has expired or has already been used.', 409); }
            $w = $this->db->wpdb();
            $this->r->execute($w->prepare('UPDATE '.$this->db->table('memberships').' SET state=%s,version=version+1,updated_at=%s WHERE id=%s', 'active', $this->db->now(), $id));
            return $this->membership($this->r->one('memberships', $id));
        });
    }

    public function revoke(string $seller, string $id, int $version, int $actor, string $key): array
    {
        $this->stepUp($actor, 'team_revoke', hash('sha256', $seller.'|'.$id.'|'.$version));
        return $this->r->command('team-revoke:'.$actor.':'.$seller, $key, [$id, $version], function () use ($seller, $id, $version): array {
            $this->r->one('sellers', $seller, true); // Serializes concurrent last-owner changes.
            $member = $this->r->one('memberships', $id, true);
            if ($member['seller_id'] !== $seller) { throw new DomainError('not_found', 'Resource not found.', 404); }
            Records::version($member, $version);
            $w = $this->db->wpdb();
            $all = $w->get_col($w->prepare('SELECT capabilities FROM '.$this->db->table('memberships').' WHERE seller_id=%s AND state=%s', $seller, 'active'));
            $owners = count(array_filter($all, static fn (string $caps): bool => in_array('commerce.team.manage', json_decode($caps, true), true)));
            if ($member['state'] === 'active' && in_array('commerce.team.manage', json_decode($member['capabilities'], true), true) && $owners <= 1) { throw new DomainError('last_owner', 'Verified ownership transfer is required before removing the last owner.', 409); }
            $this->r->execute($w->prepare('UPDATE '.$this->db->table('memberships').' SET state=%s,version=version+1,updated_at=%s WHERE id=%s', 'revoked', $this->db->now(), $id));
            return $this->membership($this->r->one('memberships', $id));
        });
    }

    private function stepUp(int $actor, string $purpose, string $digest): void
    {
        if (!$this->human) { throw new DomainError('verified_step_up_unavailable', 'A configured human identity provider with fresh purpose-bound step-up is required.', 503); }
        $this->human->requireFresh($actor, $purpose, $digest);
    }

    public function project(array $s): array { return ['id' => $s['id'], 'display_name' => $s['display_name'], 'state' => $s['state'], 'version' => (int) $s['version'], 'provider_eligibility' => $s['provider_eligibility']]; }
    private function membership(array $m): array { return ['id' => $m['id'], 'seller_id' => $m['seller_id'], 'user_id' => (string) $m['user_id'], 'state' => $m['state'], 'capabilities' => json_decode($m['capabilities'], true), 'version' => (int) $m['version']]; }
}
