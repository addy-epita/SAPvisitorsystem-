# Compound Engineering Setup - Quick Reference

## Global Installation (Run Once)

Open a terminal (outside Claude Code) and run:

```bash
# Or use the setup script:
~/.claude/compound-engineering-setup.sh

# Or manually:
claude /plugin marketplace add https://github.com/EveryInc/compound-engineering-plugin
claude /plugin install compound-engineering
```

## This Project is Ready

This project (SAPvisitorsystem) is already configured with:
- ✅ `CLAUDE.md` - Project guidelines
- ✅ `plans/` directory - For implementation plans
- ✅ `docs/learnings/` directory - For documented learnings
- ✅ `.claude/commands/` - Custom commands

## How to Use

### Starting New Work
```
/workflows:plan
```
Creates a detailed plan before writing code.

### Executing a Plan
```
/workflows:work
```
Executes plans with worktrees and task tracking.

### Before Merging
```
/workflows:review
```
Multi-agent code review.

### After Completing Work
```
/workflows:compound
```
Document learnings for future reference.

## For New Projects

When starting a new project, run:

```bash
mkdir -p plans docs/learnings .claude/commands
# Copy the CLAUDE.md template:
cp ~/Projects/SAPvisitorsystem/CLAUDE.md ./CLAUDE.md
```

Or simply ask me: "Set up compound engineering for this project"

## Philosophy Reminder

> **80% planning and review, 20% execution**

1. Plan thoroughly before writing code
2. Review to catch issues and capture learnings
3. Codify knowledge so it's reusable
4. Keep quality high so future changes are easy

## Files Created

```
/Users/addysharma/.claude/
├── MEMORY.md                          # Global memory (persisted across sessions)
├── compound-engineering-setup.sh      # Setup script for new machines
└── compound-engineering/
    └── CLAUDE.md.template             # Template for new projects

/Users/addysharma/Projects/SAPvisitorsystem/
├── CLAUDE.md                          # Project guidelines
├── COMPOUND-SETUP.md                  # This file
├── plans/                             # Implementation plans
├── docs/learnings/                    # Documented learnings
└── .claude/
    ├── README.md                      # Config documentation
    └── commands/
        ├── plan.md                    # Custom plan command
        └── compound.md                # Custom compound command
```
