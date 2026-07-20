# Security Policy

## Reporting a Vulnerability

Please report security issues privately. Do not open a public issue, pull
request, or discussion for a vulnerability — public disclosure gives everyone
running the affected code a window in which they are exposed and cannot act.

Two private channels, either is fine:

- **GitHub Security Advisories** — open a draft advisory from the
  [Security tab](https://github.com/Yeonik/laravel-legacy-password-upgrader/security/advisories/new).
  Preferred, since it keeps the report, the fix, and the eventual disclosure in
  one place.
- **Email** — kirelitom@gmail.com

Useful things to include: the affected version or commit, what an attacker can
achieve, and the smallest set of steps that demonstrates the problem. A rough
report you are unsure about is still worth sending; a report nobody sends is the
only one that cannot be fixed.

Please do not include real user data, production credentials, or live password
hashes in a report. A synthetic example is always enough to show the issue.

## Response Time

Expect an initial response within a few days. If a report turns out to be valid,
you will get an assessment and a rough fix timeline in that first reply. If it
turns out not to be a vulnerability, you will get an explanation of why rather
than silence.

## Scope

This is a portfolio repository. It is not deployed to production anywhere, has
no users, and holds no data — so there is no live system to attack and no
incident to contain.

Reports are still welcome, and are still taken seriously. The package handles
password verification and rehashing, which is exactly the kind of code where a
subtle mistake matters: a flaw here would follow the code into any application
that adopts it. A bug found in a portfolio repository is a bug that never
reaches someone's real login form.

## Supported Versions

The latest release on the `main` branch is the supported version. Fixes land
there; older tags do not receive backports.
