# Skill Registry — appseguimiento

Generated: 2026-04-09

## User Skills

| Skill | Trigger | Source |
|-------|---------|--------|
| go-testing | Go tests, Bubbletea TUI testing | ~/.claude/skills/go-testing |
| skill-creator | Creating new AI skills | ~/.claude/skills/skill-creator |
| branch-pr | Creating a pull request, opening a PR | ~/.claude/skills/branch-pr |
| issue-creation | Creating a GitHub issue, reporting a bug | ~/.claude/skills/issue-creation |
| judgment-day | "judgment day", "dual review", "juzgar" | ~/.claude/skills/judgment-day |

## Project Conventions

| File | Description |
|------|-------------|
| ~/.claude/CLAUDE.md | Global user instructions: no AI attribution in commits, use bat/rg/fd/sd/eza, conventional commits, Rioplatense Spanish, Senior Architect persona |

## Compact Rules

### branch-pr
- Follow issue-first enforcement — every PR must reference an issue
- Use conventional commit style for PR titles
- Include test plan in PR description

### issue-creation
- Use structured templates for bug reports and feature requests
- Follow issue-first enforcement system

### judgment-day
- Parallel adversarial review with two blind judge sub-agents
- Synthesizes findings, applies fixes, re-judges until both pass
- Escalates after 2 iterations

### go-testing (context: Go files only)
- Not applicable to this project (Laravel/PHP stack)

### skill-creator
- For creating new AI skills with proper SKILL.md structure
