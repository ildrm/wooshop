# Evidence and research notes

Document ID: DOC-G-EVIDENCE | Revision: 1.0 | Date: 2026-10-28
Status: drafted | Owner: Technical Product Writer | Reviewers: Solution/Software Architect; QA/Test Engineer
Scope: Evidence and research notes | Requirements: FR-G001; FR-G003 | ADRs: ADR-14
Dependencies: [documentation conventions](documentation-conventions.md) | Assumptions: ASM-01; ASM-02; ASM-03
Sources: USER-BRIEF; SRC-G001; SRC-G002; SRC-G003; SRC-G004; SRC-G005; SRC-G008; [source register](sources.csv). All proposals await the designated human gate; runtime validation is not executed.

## Environment inventory

Filesystem, shell and web research tools are available. At initial inspection the project contained README.md (`# wooshop`) and a GNU GPL version2 LICENSE, plus Git metadata; no application code or documentation directory existed. The [inventory](environment-inventory.json) records hashes for those original files. No existing application architecture was inferred. The codebase-memory skill was read because of repository instructions; no structural code exploration was required in this empty source repository and no graph index was created.

Writing is restricted to docs, with path and symlink checks before authoring. Tool-supported documentation delegation was used for bounded architecture/data/marketplace/API, design/theme/modules/profiles, and assurance/operations/guides scopes. The primary author merges common registries and validates the result. These are AI authors, not independent reviewers or human signatories.

## Research record and limits

The [source register](sources.csv) records exact URLs, access date, update date where visible, version, supported claim and limitation. The user-facing research date is2026-10-28. No legal applicability is researched as if a jurisdiction were already chosen. Official release pages list WordPress7.1.3 and WooCommerce11.2.1; the [version baseline](version-baseline.csv) distinguishes external release evidence from unexecuted project tests. [WordPress release evidence](https://wordpress.org/download/releases/) and [WooCommerce release evidence](https://developer.woocommerce.com/releases/).

WordPress and WooCommerce published database recommendations differ at the MariaDB lower bound; the proposed project uses10.11, consistent with the stricter WordPress recommendation. PHP8.4 is proposed against vendor guidance, not assumed extension compatibility. [WordPress requirements](https://wordpress.org/about/requirements/), [WooCommerce requirements](https://woocommerce.com/document/server-requirements/), and [PHP lifecycle](https://www.php.net/supported-versions.php).

A developer.wordpress.org advanced-administration requirements URL returned an access error; the accessible official WordPress requirements page supplies the relevant evidence. Safari27's specific page required JavaScript in this fetch tool; release-index evidence supports its release listing only. Search results from nonprimary aggregators were not used as technical authority. Browser targets are a verified dated starting point; exact builds require revalidation at phase8.

## Reference/design discipline

[The reference audit](../05-ux-ui/reference-audit.md) distinguishes extracted visible content, inferred structure, unobserved interactions and original adaptations. No copyrighted brand/art/code is adopted. No Figma file, real usability session, penetration test, benchmark or live purchase has been performed. No external source is an instruction to execute code, sign in or contact a vendor.

## Production sequence

1. Preserve initial files; establish source/requirement/test registers and namespace allocations.
2. Research material platform/reference facts and set authority/decision contracts.
3. Write detailed domain and experience specifications in parallel bounded scopes.
4. Reconcile shared money, identity, permissions, APIs, events, SEO, settings and lifecycle semantics.
5. Generate traceability, RACI, feature and artifact indexes from actual records; inspect critical slices.
6. Validate local syntax/links/IDs/coverage and original-file preservation; report actual outcomes and open gates.
