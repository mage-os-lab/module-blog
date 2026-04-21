<!--
Thanks for opening a pull request. A short summary plus the checklist below keeps reviews fast.
-->

## Summary

<!-- What does this PR do? 2-3 bullets is plenty. -->

- 
- 

## Motivation

<!-- Why is the change needed? Link the related issue: Closes #123. -->

Closes #

## How to test

<!-- Step-by-step, or "run phpunit" if purely internal. -->

1. 
2. 

## Checklist

- [ ] Branch is based on the latest `main`.
- [ ] Commits follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `refactor:`, `test:`, `chore:`, `docs:`).
- [ ] `vendor/bin/phpunit --testsuite unit` passes locally.
- [ ] `vendor/bin/phpstan analyse --memory-limit=1G` passes locally.
- [ ] `vendor/bin/phpcs --standard=phpcs.xml.dist` passes locally.
- [ ] `vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes` shows no changes needed.
- [ ] New PHP files start with `declare(strict_types=1);`.
- [ ] User-facing change has a `CHANGELOG.md` entry under `## [Unreleased]`.
- [ ] No raw integer IDs in admin UX (use pickers / linked names. See `CONTRIBUTING.md`).

## Screenshots / GraphQL samples (if UI or API change)

<!-- Drag screenshots here, or paste a sample query + response. -->
