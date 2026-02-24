# Claude Configuration

This directory contains project-specific Claude Code configurations.

## 🚀 Superpowers (Recommended)

**Superpowers** is the recommended all-in-one development workflow plugin.

### Installation
```bash
/plugin marketplace add obra/superpowers-marketplace
/plugin install superpowers@superpowers-marketplace
```

### How It Works
Skills trigger automatically as you work:
1. **brainstorming** → Refines requirements
2. **writing-plans** → Creates implementation plans
3. **using-git-worktrees** → Sets up isolated branches
4. **subagent-driven-development** → Executes with review
5. **test-driven-development** → Enforces RED-GREEN-REFACTOR
6. **requesting-code-review** → Automated reviews
7. **finishing-a-development-branch** → Merge/PR workflow

Just say: *"I want to build a feature that..."* and Superpowers handles the rest!

### Documentation
- See `SUPERPOWERS-SETUP.md` in project root for complete guide
- Official docs: https://github.com/obra/superpowers

---

## 🔄 Ralph Wiggum (Optional)

For autonomous execution loops, combine with Ralph:

### Installation
```bash
/plugin marketplace add anthropics/claude-code
/plugin install ralph-loop@claude-plugins-official
```

### Templates
See `.claude/ralph-templates/` for reusable task templates:
- `refactor.md` - Code refactoring
- `feature.md` - New features
- `bugfix.md` - Bug fixes

### Documentation
- See `RALPH-SETUP.md` in project root

---

## 📝 Alternative: Compound Engineering

If you prefer manual workflow control, Compound Engineering is available:

### Commands
- `plan.md` - Planning command
- `compound.md` - Documentation command

### Installation
```bash
/plugin marketplace add https://github.com/EveryInc/compound-engineering-plugin
/plugin install compound-engineering
```

### Documentation
- See `COMPOUND-SETUP.md` in project root
- Guide: https://every.to/guides/compound-engineering

---

## Quick Reference

| Plugin | Best For | Install Command |
|--------|----------|-----------------|
| **Superpowers** | Complete workflow (recommended) | `obra/superpowers-marketplace` |
| **Ralph Wiggum** | Autonomous execution loops | `anthropics/claude-code` |
| **Compound Engineering** | Manual workflow control | `EveryInc/compound-engineering-plugin` |

## Project Guidelines

See `CLAUDE.md` in project root for project-specific development guidelines.
