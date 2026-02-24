# Superpowers Plugin Setup Guide

**Superpowers** is a comprehensive software development workflow plugin for Claude Code that bundles planning, TDD, debugging, code review, and skill authoring into a single install.

> **Note:** This is a more powerful alternative to the Compound Engineering plugin, with automatic skill triggering and a complete development workflow.

## Installation (Inside Claude Code)

### Step 1 — Add the Superpowers Marketplace
```
/plugin marketplace add obra/superpowers-marketplace
```

### Step 2 — Install Superpowers
```
/plugin install superpowers@superpowers-marketplace
```

### Step 3 — Verify Installation
Start a new session and say something like:
```
"Help me plan a new feature"
```

The agent should automatically invoke the brainstorming skill.

---

## The Complete Workflow

Superpowers automatically triggers skills at each stage:

```
┌─────────────────────────────────────────────────────────────────┐
│  1. BRAINSTORMING                                               │
│  Refines rough ideas through Socratic questioning                │
│  Explores alternatives, presents design in readable chunks       │
│  → Saves design document                                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  2. USING-GIT-WORKTREES                                         │
│  Creates isolated workspace on new branch                        │
│  Runs project setup, verifies clean test baseline               │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  3. WRITING-PLANS                                               │
│  Breaks work into bite-sized tasks (2-5 min each)               │
│  Every task has exact file paths, complete code, verification   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  4. SUBAGENT-DRIVEN-DEVELOPMENT / EXECUTING-PLANS               │
│  Dispatches fresh subagent per task                              │
│  Two-stage review: spec compliance → code quality               │
│  Or executes in batches with human checkpoints                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  5. TEST-DRIVEN-DEVELOPMENT                                     │
│  Enforces RED-GREEN-REFACTOR cycle                              │
│  Write failing test → watch it fail → write minimal code        │
│  → watch it pass → commit → refactor                            │
│  Deletes any code written before tests!                         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  6. REQUESTING-CODE-REVIEW                                      │
│  Reviews against plan, reports issues by severity               │
│  Critical issues block progress                                  │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│  7. FINISHING-A-DEVELOPMENT-BRANCH                              │
│  Verifies tests, presents options (merge/PR/keep/discard)       │
│  Cleans up worktree                                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Available Skills

| Skill | Purpose | Trigger |
|-------|---------|---------|
| **brainstorming** | Socratic design refinement | Say "plan this feature" or "design this" |
| **writing-plans** | Detailed implementation plans | After brainstorming approval |
| **using-git-worktrees** | Isolated development branches | After design approval |
| **executing-plans** | Batch execution with checkpoints | With approved plan |
| **subagent-driven-development** | Fast iteration with two-stage review | Complex multi-file tasks |
| **test-driven-development** | RED-GREEN-REFACTOR TDD cycle | During implementation |
| **systematic-debugging** | 4-phase root cause process | Say "debug this" or "fix this bug" |
| **requesting-code-review** | Pre-review against plan | Between tasks |
| **receiving-code-review** | Responding to feedback | When issues found |
| **dispatching-parallel-agents** | Concurrent subagent workflows | Parallelizable tasks |
| **finishing-a-development-branch** | Merge/PR decision workflow | When tasks complete |
| **writing-skills** | Create new skills | Say "create a skill" |

---

## Philosophy

- **Test-Driven Development** — Write tests first, always
- **Systematic over ad-hoc** — Process over guessing
- **Complexity reduction** — Simplicity as primary goal
- **Evidence over claims** — Verify before declaring success

---

## Example Sessions

### Building a New Feature

**You:**
```
I need to add user authentication with JWT tokens
```

**Superpowers automatically triggers:**
1. **brainstorming** → Asks clarifying questions about requirements
2. **writing-plans** → Creates detailed implementation plan
3. **using-git-worktrees** → Sets up isolated workspace
4. **subagent-driven-development** → Dispatches agents to implement
5. **test-driven-development** → Ensures RED-GREEN-REFACTOR
6. **requesting-code-review** → Reviews each task
7. **finishing-a-development-branch** → Presents merge options

### Debugging an Issue

**You:**
```
Users are reporting login failures with special characters in passwords
```

**Superpowers automatically triggers:**
1. **systematic-debugging** → 4-phase root cause analysis
2. **test-driven-development** → Write test reproducing bug first
3. **requesting-code-review** → Verify fix against plan

### Creating a Custom Skill

**You:**
```
Create a skill for optimizing database queries
```

**Superpowers automatically triggers:**
1. **writing-skills** → Guides through skill creation following TDD principles

---

## Manual Skill Invocation

While skills trigger automatically, you can also invoke them directly:

```
/brainstorming

I want to build a feature that...
```

```
/executing-plans

Execute the plan from plans/feature-x.md
```

```
/test-driven-development

Implement this function following TDD:
def calculate_discount(price, customer_tier):
    ...
```

---

## Comparison: Superpowers vs Compound Engineering

| Feature | Superpowers | Compound Engineering |
|---------|-------------|---------------------|
| **Installation** | Single plugin | Single plugin |
| **Skills bundled** | 12+ skills | 4 workflows |
| **Auto-trigger** | ✅ Yes | Manual invocation |
| **TDD enforcement** | ✅ Strict RED-GREEN-REFACTOR | ❌ Not enforced |
| **Subagent development** | ✅ Built-in | ❌ Not included |
| **Debug methodology** | ✅ 4-phase systematic | ❌ Not included |
| **Git worktrees** | ✅ Automatic | ✅ Via /workflows:work |
| **Code review** | ✅ Two-stage automated | ✅ Multi-agent |
| **Skill authoring** | ✅ Can write new skills | ❌ Not included |
| **Maintenance** | Active (obra/superpowers) | Community |

---

## Recommended: Superpowers + Ralph Wiggum

For maximum autonomy, combine Superpowers with Ralph Wiggum:

```
Superpowers (planning + TDD + review)
         ↓
Ralph Wiggum (autonomous execution loop)
         ↓
Superpowers (finishing + compound)
```

This gives you:
- Structured planning and review (Superpowers)
- Autonomous execution capability (Ralph)
- Best of both worlds!

---

## Updating

```
/plugin update superpowers
```

---

## Resources

- **GitHub:** https://github.com/obra/superpowers
- **Marketplace:** https://github.com/obra/superpowers-marketplace
- **Issues:** https://github.com/obra/superpowers/issues
- **Documentation:** https://claude.com/plugins/superpowers
