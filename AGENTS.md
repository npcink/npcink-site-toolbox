# AGENTS.md - Npcink Site Toolbox

## Session Startup Protocol

Every AI development session should start with:

1. Run `git status --short --branch`.
2. Read `README.md`.
3. Read the relevant release wrap-up or development note before editing.
4. Briefly report the focused module and intended verification gate.

## Product Boundary

Npcink Site Toolbox is a WordPress utility plugin for site owners. Keep changes
inside the existing plugin feature surface unless a separate design note says
otherwise.

Do not fold Npcink Core, Toolkit, Adapter, Cloud Addon, or Cloud control-plane
responsibilities into this plugin. Governed AI writes, proposal approval,
provider runtime, billing, queues, or cross-repo workflow truth belong outside
this repository.

## AI Development Rules

- Write a compact change envelope before editing: target repositories, focused
  module, intended change, explicit non-goals, public contracts touched,
  expected files, files or areas that must not change, required gates,
  cross-repo matrix requirement, and rollback plan.
- Keep changes scoped to one module per session.
- When a public API or user-facing feature changes, update its implementation
  contract, built-in help page, and documentation-site tutorial together. Run
  the relevant contract and link checks before closeout.
- Before staging, inspect `git status --short --branch` and `git diff --stat`.
  Stage only files changed for the current task. Do not use `git add -A` in a
  mixed worktree.
- Do not run `git reset --hard`, `git checkout -- .`, or equivalent destructive
  cleanup unless the user explicitly asks for that exact operation.
- Before committing, verify `git diff --cached --stat` and
  `git diff --cached --name-only`; after committing, verify
  `git show --name-status --stat HEAD`.
- For a WordPress.org submission or resubmission, build the final ZIP through
  the repository release commands, verify that every archive path is portable
  ASCII with no case-only collision, activate that exact ZIP in a clean
  WordPress environment, and run the latest official Plugin Check against its
  extracted plugin directory. Record the WordPress version, PCP version,
  complete error/warning counts, remaining-warning rationale, and ZIP SHA-256.
  PCP errors block submission; warnings require explicit review and must not be
  hidden with blanket ignore flags.
- For multi-repo milestones, run the central matrix from
  `/Users/muze/gitee/npcink-toolbox` instead of copying the script here:
  `composer quality:matrix` for status and `composer quality:matrix:run` before
  cross-repo closeout.

## Verification Gates

Default gate:

```bash
composer test
```

Static analysis:

```bash
composer phpstan
```

WordPress.org release gate:

```bash
pnpm --dir vite build
composer release:build
composer release:verify -- npcink-site-toolbox.zip
composer release:wordpress-org-check
# The final command activates this exact ZIP with WP_DEBUG enabled and scans it
# with the latest official Plugin Check in a disposable Docker environment.
```

Before finishing a code session, run the narrowest useful gate and report
exactly what passed or failed.
