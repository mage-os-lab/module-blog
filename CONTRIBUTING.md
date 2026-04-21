# Contributing

Thanks for wanting to help. This module is open source under OSL-3.0 and welcomes pull requests, bug reports, and documentation fixes.

## Before you open an issue

- Search [existing issues](https://github.com/mage-os-lab/module-blog/issues) first, your problem may already be tracked.
- For storefront/admin bugs, please include the Magento version, PHP version, and reproduction steps. The **Bug report** template prompts for all of these.
- For security vulnerabilities, don't open a public issue. See `SECURITY.md`.

## Before you open a PR

1. Open or comment on the issue that covers the change. Lets us agree on scope before code moves.
2. Branch off `main`. Naming like `feat/<topic>` or `fix/<topic>` is fine, nothing strict.
3. Use [Conventional Commits](https://www.conventionalcommits.org/) for commit messages: `feat:`, `fix:`, `refactor:`, `test:`, `chore:`, `docs:`. One logical change per commit.
4. Every new PHP file starts with `declare(strict_types=1);`. Match surrounding style, no tabs, no trailing whitespace.
5. Run the gates locally before pushing:
   ```bash
   composer install
   vendor/bin/phpunit --testsuite unit
   vendor/bin/phpstan analyse --memory-limit=1G
   vendor/bin/phpcs --standard=phpcs.xml.dist
   vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
   ```
   All four must pass. CI runs the same ones plus integration tests against a live Magento install.
6. If the change is user-facing (storefront behavior, admin UI, GraphQL surface), add a `CHANGELOG.md` entry under `## [Unreleased]`.
7. Keep PRs small. Two 200-line PRs review faster than one 400-line PR.

## What you can expect

- Initial response on a PR within about a week on a good week, longer on a busy one. Ping the PR if silence stretches past two weeks.
- Reviews focus on correctness, test coverage, and matching existing patterns. Style nits are rare, the tooling handles most of them.
- Merges use `--no-ff` so feature history is preserved.

## Release cadence

Semver. Patch releases land when there's a user-facing fix to ship. Minor/major coincide with new features or breaking changes and get a CHANGELOG section.

## Attribution

Design inspiration from [Magefan Blog](https://magefan.com/magento2-blog-extension) (OSL-3.0). v1 is an independent implementation with no shared code.

## Code of conduct

Participation is governed by the [Contributor Covenant 2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). See `CODE_OF_CONDUCT.md`.
