# Package: redberryproducts/laravel-bog-payment

This is a Laravel package wrapping the Bank of Georgia payment gateway. Public surface:

- Facades: `Pay`, `Transaction` (in `src/Facades/`)
- Event: `RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated`
- Callback route: `POST /bog-payment/payment/callback` (name: `bog-payment.callback`)
- Config: `config/bog-payment.php` (publish tag `bog-payment-config`)

## Keep user-facing artifacts in sync with the code

Whenever you change anything under `src/`, `config/`, `routes/`, `database/`, or `composer.json`, you MUST also audit and update — in the same change — every artifact below that describes the package's public behavior. Do this *before* declaring the task done, not as a follow-up.

Artifacts to cross-check on every change:

1. **`README.md`** — installation steps, env vars, code examples, response shapes.
2. **`resources/boost/skills/bog-payment-development/SKILL.md`** — the Laravel Boost skill that ships to consuming apps. Frontmatter `description` (triggers — facade/method/event names), all code snippets, the "Common Pitfalls" list, and the env var list.
3. **`CHANGELOG.md`** — add a Keep-a-Changelog-style entry under the unreleased section for any behavior change.
4. **`config/bog-payment.php`** — comments and `env(...)` defaults must match what README and SKILL.md describe.
5. **`SECURITY.md`** — touch only if signature-verification, callback, or credential handling changes.

What "audit" means in practice — for each change you make, walk this checklist out loud (briefly) in your response:

- Did I add, remove, or rename a **facade method, parameter, return type, or thrown exception**? → update README usage examples + SKILL.md snippets + SKILL.md pitfalls.
- Did I add, remove, or rename an **env var or config key**? → update README env section + SKILL.md "Installation & Configuration" + the `config/bog-payment.php` comment block.
- Did I change a **route, route name, or HTTP method**? → update README callback section + SKILL.md callback section.
- Did I change an **event name, event payload shape, or dispatch condition**? → update README listener example + SKILL.md callback section.
- Did I change a **chaining requirement** (e.g. method order, terminal vs. non-terminal)? → update SKILL.md "Common Pitfalls" and the relevant snippet.
- Did I bump a **dependency or PHP version constraint** in `composer.json`? → update README install instructions and `.github/workflows/run-tests.yml` matrix if needed.

If a change makes any example in README or SKILL.md wrong, fix the example. Do not leave "see code for current behavior" placeholders.

When you change the skill's `description` frontmatter, re-verify it still lists the concrete activation triggers (facade names, method names, event names, env vars) — that string is how AI agents decide whether to load the skill in a consuming app. Vague descriptions silently fail to activate.

## After any PHP change

Run `vendor/bin/pint --dirty --format agent` (per the root project's `CLAUDE.md`). Run `composer test` if behavior changed.
