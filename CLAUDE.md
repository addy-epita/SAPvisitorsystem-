# Compound Engineering Guidelines

This project follows the [Compound Engineering](https://every.to/guides/compound-engineering) methodology.

## Philosophy

> "80% is in planning and review, 20% is in execution"

Each unit of engineering work should make future work easier than the last.

## Workflow

```
Plan → Work → Review → Compound → Repeat
```

### 1. Plan (`/workflows:plan`)
- Turn feature ideas into detailed implementation plans
- Create plans in the `plans/` directory
- Get approval before writing code

### 2. Work (`/workflows:work`)
- Execute plans with worktrees and task tracking
- Work in isolation using git worktrees
- Track tasks and dependencies

### 3. Review (`/workflows:review`)
- Multi-agent code review before merging
- Catch issues and capture learnings
- Ensure quality stays high

### 4. Compound (`/workflows:compound`)
- Document learnings in `docs/learnings/`
- Update this CLAUDE.md with patterns discovered
- Codify knowledge so it's reusable

## Project Structure

```
plans/              # Implementation plans (created via /workflows:plan)
docs/learnings/     # Documented learnings and patterns
.claude/commands/   # Custom Claude commands (if any)
CLAUDE.md           # This file - project-specific guidelines
```

## Commands Available

### 🚀 Superpowers (Recommended Primary Workflow)

Superpowers is the main development workflow - skills trigger automatically!

| Skill | When to Use |
|-------|-------------|
| `brainstorming` | Starting a new feature (auto-triggers) |
| `writing-plans` | Creating implementation plans (auto-triggers) |
| `using-git-worktrees` | Isolated development branches (auto-triggers) |
| `executing-plans` | Batch execution with checkpoints (auto-triggers) |
| `subagent-driven-development` | Complex multi-file tasks (auto-triggers) |
| `test-driven-development` | RED-GREEN-REFACTOR TDD (auto-triggers) |
| `systematic-debugging` | Debugging issues (auto-triggers) |
| `requesting-code-review` | Automated code review (auto-triggers) |
| `finishing-a-development-branch` | Merge/PR workflow (auto-triggers) |
| `writing-skills` | Creating custom skills |

**Installation:**
```
/plugin marketplace add obra/superpowers-marketplace
/plugin install superpowers@superpowers-marketplace
```

### Ralph Wiggum (Autonomous Execution - Optional)

For extended autonomous execution loops:

| Command | When to Use |
|---------|-------------|
| `/ralph-loop:ralph-loop` | Autonomous task execution with iterative loops |

**Installation:**
```
/plugin marketplace add anthropics/claude-code
/plugin install ralph-loop@claude-plugins-official
```

See `RALPH-SETUP.md` for templates.

### Compound Engineering (Alternative - Not needed with Superpowers)

If you prefer manual workflow control:

| Command | When to Use |
|---------|-------------|
| `/workflows:plan` | Starting a new feature or task |
| `/workflows:work` | Executing an approved plan |
| `/workflows:review` | Before merging any code |
| `/workflows:compound` | After completing work to document learnings |

See `COMPOUND-SETUP.md` for details.

## Key Principles

1. **Plan thoroughly before writing code** - Use `/workflows:plan` to think through implementation
2. **Review to catch issues and capture learnings** - Quality gates prevent technical debt
3. **Codify knowledge so it's reusable** - Document in `docs/learnings/`
4. **Keep quality high so future changes are easy** - Compound, don't accumulate debt

## Learnings

<!-- Add documented learnings here as the project grows -->

