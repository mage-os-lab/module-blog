# Security policy

## Supported versions

| Version | Supported |
| --- | --- |
| 1.x | yes |
| < 1.0 | no (pre-v1 forks are unmaintained) |

## Reporting a vulnerability

Please don't open a public GitHub issue for a security problem. Use GitHub's private advisory flow instead:

1. Go to https://github.com/mage-os-lab/module-blog/security/advisories/new
2. Fill in a concise title and a clear description with reproduction steps.
3. Submit. The maintainers get notified privately.

Alternatively, email `security@run-as-root.sh` with the same information.

## What to expect

- Initial acknowledgement within five working days.
- Triage + severity assessment within ten working days.
- A fix plan or a published advisory within thirty days of the report, depending on severity.
- Coordinated disclosure. We'll credit the reporter in the release notes unless you prefer anonymity.

## Scope

In scope:
- SQL injection, XSS, CSRF, privilege escalation, SSRF, or path traversal in any module code under this repository.
- Authentication / authorization bypass in GraphQL mutations or admin controllers.
- Information disclosure via the REST or GraphQL APIs.

Out of scope (not a MageOS_Blog vulnerability):
- Issues in Magento / Mage-OS core or in unrelated third-party modules.
- Social engineering, physical attacks, denial-of-service by volume.
- Findings that require admin-role access already granted by the merchant.

## Hardening

- Keep Magento / Mage-OS on a supported security patch level.
- Run `composer audit` regularly and apply dependency updates.
- Enable GitHub Dependabot alerts for your own fork (it's on by default for public repos).
