# Security Policy

## Reporting a vulnerability

**Do not open a public issue, pull request, or forum post for security problems.**
A public report exposes every site running AcyMailing until a patch is released.

Report privately to **security@acyba.com**, or through our contact form at
https://www.acymailing.com/contact/

Please include:

- The product and exact version you tested (AcyMailing version, and Joomla or WordPress version)
- Steps to reproduce
- The privileges required to trigger the issue (none, subscriber, admin…)
- The impact you believe it has
- Whether you have seen any evidence of real-world exploitation

Our full disclosure policy is published at
https://www.acymailing.com/security-policy

## What we commit to

| Stage | Target |
| --- | --- |
| Acknowledgement of your report | 1 business day |
| Initial assessment (confirmed or not, affected versions, planned timeline) | 3 to 5 business days |
| Status updates until the issue is closed | At every significant step |

We ask that you do not publicly disclose details until we have agreed on a
disclosure date together. We will credit you in the advisory, in the form you
choose, or not at all if you prefer. We do not run a paid bug bounty program.

## Supported versions

A security fix is shipped as a new minor version, and it is not backported to
earlier ones, so upgrading to the latest version is always the way to get the patch.

AcyMailing is distributed for both Joomla and WordPress from a shared code
base. A vulnerability in shared code is fixed and released on both platforms.

## Scope and good-faith research

In scope: AcyMailing releases distributed by Acyba, on supported versions.

Out of scope: third-party extensions, the host CMS itself (report those to the
Joomla or WordPress security teams), issues requiring an already-compromised
site, and findings from automated scanners with no demonstrated impact.

While researching, please do not access data belonging to others, perform
destructive or denial-of-service testing, use social engineering, scan
third-party infrastructure, establish persistence, or exfiltrate data.

## Security advisories

Published advisories are listed at https://www.acymailing.com/security
