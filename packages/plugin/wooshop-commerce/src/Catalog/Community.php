<?php
declare(strict_types=1);
namespace WooShop\Commerce\Catalog;

use WooShop\Commerce\Customer\Records;
use WooShop\Commerce\Customer\Session;
use WooShop\Commerce\Infrastructure\Database;
use WooShop\Commerce\Infrastructure\EntityStore;
use WooShop\Commerce\Support\DomainError;

final class Community
{
    private EntityStore $store;
    private Records $records;
    private Session $session;
    private Products $products;
    private Specifications $specs;

    public function __construct(Database $db, EntityStore $store, Session $session, Products $products, Specifications $specs)
    { $this->store = $store; $this->records = new Records($db, $store); $this->session = $session; $this->products = $products; $this->specs = $specs; }

    public function question(array $input, string $key): array
    {
        if (get_current_user_id() < 1) { throw new DomainError('unauthenticated', 'Sign in to ask a product question.', 401); }
        Input::keys($input, ['product_id', 'text'], ['product_id', 'text']);
        $this->products->get(Input::id($input['product_id']));
        $input['text'] = Input::text($input['text'], 10, 2000);
        return $this->records->command($this->session->owner(), 'question.submit', $key, $input, function () use ($input): array {
            $counter = $this->records->own('question_counter', $this->session->owner(), ['date' => gmdate('Y-m-d'), 'count' => 0]);
            $data = $counter['data']; if ($data['date'] !== gmdate('Y-m-d')) { $data = ['date' => gmdate('Y-m-d'), 'count' => 0]; }
            if ($data['count'] >= Options::integer('qa.rate_per_day', 5, 1, 20)) { throw new DomainError('rate_limited', 'You have reached the daily question limit.', 429); }
            $data['count']++; $this->store->update('question_counter', $counter['id'], $counter['version'], $data);
            $record = $this->store->create('product_question', $input + ['state' => 'pending', 'answer' => null, 'answer_state' => 'pending', 'moderation_reason' => null], $this->session->owner());
            return $this->questionProjection($record);
        });
    }

    public function questions(int $limit = 20, ?string $cursor = null, ?string $product = null): array
    {
        if ($product !== null) { $this->products->get($product); }
        $items = []; $scanned = 0; $last = $cursor; $more = true;
        // Bound private-row scanning; an opaque cursor can advance through moderation gaps.
        while (count($items) < $limit && $scanned < 500 && $more) {
            $records = $this->store->find('product_question', null, 100, $last);
            $more = count($records) === 100;
            foreach ($records as $record) {
                $scanned++; $last = $record['id'];
                if ($record['data']['state'] !== 'published' || ($product !== null && $record['data']['product_id'] !== $product)) { continue; }
                try { $this->products->get($record['data']['product_id']); } catch (DomainError $error) { if ($error->status === 404) { continue; } throw $error; }
                $items[] = $this->questionProjection($record);
                if (count($items) >= $limit) { $more = true; break; }
            }
        }
        return ['items' => $items, 'next_cursor' => $more && $last ? $last : null];
    }

    public function questionProjection(array $record): array
    {
        return ['id' => $record['id'], 'product_id' => $record['data']['product_id'], 'state' => $record['data']['state'], 'text' => $record['data']['text'], 'version' => $record['version']];
    }

    public function moderate(string $id, int $revision, string $action, ?string $answer = null, ?string $evidence = null): array
    {
        if (!current_user_can('moderate_comments')) { throw new DomainError('forbidden', 'Moderation permission is required.', 403); }
        $record = $this->store->get('product_question', Input::uuid($id));
        if (!$record) { throw new DomainError('not_found', 'Question unavailable.', 404); }
        Records::expect($record, $revision);
        if ($record['owner_id'] === $this->session->owner()) { throw new DomainError('self_approval', 'Another moderator must review your question.', 403); }
        if (!in_array($action, ['publish', 'reject', 'answer'], true)) { throw new DomainError('invalid_action', 'Unsupported moderation action.', 422); }
        $data = $record['data'];
        if ($action === 'answer') {
            $data['answer'] = Input::text($answer, 1, 4000);
            $data['answer_state'] = 'pending'; $data['answer_author'] = (string) get_current_user_id();
            $data['answer_evidence'] = $evidence;
            // All technical answers require evidence before a second moderator publishes them.
            if (!$this->specs->approvedEvidence($evidence, $data['product_id'])) { throw new DomainError('evidence_required', 'Approved product evidence is required for a technical answer.', 422); }
        } else {
            if ($action === 'publish' && $data['answer'] !== null) {
                if (($data['answer_author'] ?? '') === (string) get_current_user_id()) { throw new DomainError('self_approval', 'Another moderator must publish your answer.', 403); }
                if (!$this->specs->approvedEvidence($data['answer_evidence'], $data['product_id'])) { throw new DomainError('evidence_required', 'Answer evidence is no longer approved.', 422); }
                $data['answer_state'] = 'published';
            }
            $data['state'] = $action === 'publish' ? 'published' : 'rejected';
        }
        return $this->store->update('product_question', $id, $revision, $data);
    }

    public function publishedAnswer(string $id): ?string
    {
        $record = $this->store->get('product_question', $id);
        if (!$record || $record['data']['state'] !== 'published' || $record['data']['answer_state'] !== 'published' || !$this->specs->approvedEvidence($record['data']['answer_evidence'] ?? null, $record['data']['product_id'])) { return null; }
        return $record['data']['answer'];
    }

    public function helpfulVote(string $reviewId, string $key): array
    {
        $review = get_comment((int) Input::id($reviewId));
        if (!$review || $review->comment_type !== 'review' || $review->comment_approved !== '1') { throw new DomainError('not_found', 'Review unavailable.', 404); }
        $this->products->get((string) $review->comment_post_ID);
        if ($review->user_id > 0 && (int) $review->user_id === get_current_user_id()) { throw new DomainError('self_vote', 'You cannot vote on your own review.', 403); }
        return $this->records->command($this->session->owner(), 'review.helpful', $key, ['review_id' => $reviewId], function () use ($reviewId): array {
            $id = Input::stableId('helpful:' . $reviewId . ':' . $this->session->owner());
            if (!$this->store->get('review_helpful', $id)) { $this->store->create('review_helpful', ['review_id' => $reviewId], $this->session->owner(), $id); }
            return ['review_id' => $reviewId, 'helpful' => true];
        });
    }

    public function reviewProjection(string $reviewId): array
    {
        $review = get_comment((int) Input::id($reviewId));
        if (!$review || $review->comment_type !== 'review' || $review->comment_approved !== '1') { throw new DomainError('not_found', 'Review unavailable.', 404); }
        $this->products->get((string) $review->comment_post_ID);
        return ['review_id' => $reviewId, 'verified_purchase' => function_exists('wc_review_is_from_verified_owner') ? wc_review_is_from_verified_owner((int) $reviewId) : false];
    }
}
