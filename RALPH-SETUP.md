# Ralph Wiggum Plugin Setup Guide

The **Ralph Wiggum** plugin (also known as `ralph-loop`) is an official Anthropic plugin that enables Claude Code to work in autonomous iterative loops. Instead of stopping after each response, Claude continues working until the task is complete.

## Installation (Run Inside Claude Code)

### Step 1 — Add the Anthropic Plugin Marketplace
```
/plugin marketplace add anthropics/claude-code
```

### Step 2 — Install Ralph Wiggum
```
/plugin install ralph-loop@claude-plugins-official
```

### Step 3 — Verify Installation
```
/plugin
```

You should see `ralph-loop` listed as an active plugin.

---

## Usage

### Basic Command Structure
```
/ralph-loop:ralph-loop "YOUR TASK DESCRIPTION

Requirements:
- Requirement 1
- Requirement 2

Success criteria:
- All requirements implemented
- No linter errors

Output <promise>COMPLETE</promise> when done." --max-iterations 30 --completion-promise "COMPLETE"
```

### Key Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `--max-iterations` | Maximum loop iterations | `--max-iterations 50` |
| `--completion-promise` | Text that signals completion | `--completion-promise "COMPLETE"` |
| `--timeout` | Timeout per iteration (minutes) | `--timeout 10` |

---

## Best Practices

### 1. Clear Task Description
Be specific about what needs to be done:
```
"Implement user authentication with the following requirements:
- JWT-based auth
- Password hashing with bcrypt
- Login/logout endpoints
- Middleware for protected routes

Success criteria:
- All endpoints tested and working
- No TypeScript errors
- All tests passing

Output <promise>DONE</promise> when complete."
```

### 2. Define Success Criteria
Always include clear completion signals:
- All requirements implemented
- Tests passing
- No linter errors
- Specific output marker

### 3. Set Iteration Limits
Start with reasonable limits to avoid runaway loops:
- Simple tasks: 10-20 iterations
- Medium tasks: 30-50 iterations
- Complex tasks: 50-100 iterations

---

## Example Workflows

### Example 1: Refactor Code
```
/ralph-loop:ralph-loop "Refactor the auth module to use dependency injection:

Requirements:
- Extract AuthService class
- Remove direct imports
- Update all dependent modules

Success criteria:
- All tests passing
- No circular dependencies
- Code coverage maintained

Output <promise>REFACTOR_COMPLETE</promise> when done." --max-iterations 25 --completion-promise "REFACTOR_COMPLETE"
```

### Example 2: Write Tests
```
/ralph-loop:ralph-loop "Write comprehensive unit tests for the user service:

Requirements:
- Test all public methods
- Mock external dependencies
- Cover edge cases and error scenarios

Success criteria:
- Minimum 80% code coverage
- All tests passing
- No test warnings

Output <promise>TESTS_DONE</promise> when done." --max-iterations 40 --completion-promise "TESTS_DONE"
```

### Example 3: Documentation
```
/ralph-loop:ralph-loop "Create API documentation:

Requirements:
- Document all REST endpoints
- Include request/response examples
- Add authentication instructions

Success criteria:
- All endpoints documented
- Examples are valid and tested
- Markdown format

Output <promise>DOCS_COMPLETE</promise> when done." --max-iterations 20 --completion-promise "DOCS_COMPLETE"
```

---

## Combining with Compound Engineering

You can use Ralph Wiggum with the Compound Engineering workflow:

### 1. Plan Phase (Manual)
Use `/workflows:plan` to create a detailed implementation plan first.

### 2. Execute with Ralph
Then use `/ralph-loop` to execute the plan autonomously:
```
/ralph-loop:ralph-loop "Execute the implementation plan from plans/feature-auth.md:

Requirements:
- Follow all steps in the plan
- Implement each component as specified
- Maintain code quality standards

Success criteria:
- All plan items completed
- All tests passing
- Code reviewed and clean

Output <promise>PLAN_EXECUTED</promise> when done." --max-iterations 50 --completion-promise "PLAN_EXECUTED"
```

### 3. Review & Compound
After Ralph completes, use `/workflows:review` and `/workflows:compound` to capture learnings.

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Plugin not found | Ensure you're using the latest Claude Code: `npm update -g @anthropic-ai/claude-code` |
| Loop not stopping | Check your `--completion-promise` text matches exactly what's in the prompt |
| Too many iterations | Reduce `--max-iterations` or make requirements more specific |
| Timeout errors | Increase `--timeout` value for complex operations |

---

## Quick Reference Card

```
# Install
/plugin marketplace add anthropics/claude-code
/plugin install ralph-loop@claude-plugins-official

# Basic usage
/ralph-loop:ralph-loop "TASK" --max-iterations N --completion-promise "DONE"

# With detailed requirements
/ralph-loop:ralph-loop "TASK

Requirements:
- Req 1
- Req 2

Success criteria:
- Criteria 1
- Criteria 2

Output <promise>DONE</promise> when done." --max-iterations 30 --completion-promise "DONE"
```

---

## Resources

- [Anthropic Plugin Documentation](https://docs.anthropic.com/claude/docs/plugins)
- [Ralph Wiggum Technique](https://awesomeclaude.ai/ralph-wiggum)
- GitHub: `anthropics/claude-code/plugins/ralph-wiggum`
