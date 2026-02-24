# Ralph Wiggum Templates

This directory contains reusable templates for the Ralph Wiggum autonomous execution plugin.

## What is Ralph Wiggum?

Ralph Wiggum (`ralph-loop`) is an official Anthropic plugin for Claude Code that enables autonomous iterative task execution. Instead of stopping after each response, Claude continues working in a loop until the task is complete.

## Available Templates

| Template | Purpose | Recommended Iterations |
|----------|---------|----------------------|
| `refactor.md` | Code refactoring tasks | 20-40 |
| `feature.md` | New feature implementation | 40-80 |
| `bugfix.md` | Bug fixes | 15-30 |

## Quick Start

1. Choose a template based on your task type
2. Copy the template content
3. Replace the `{{PLACEHOLDER}}` values with your specific details
4. Run inside Claude Code with the plugin installed

## Installation

Run these commands inside Claude Code:

```
/plugin marketplace add anthropics/claude-code
/plugin install ralph-loop@claude-plugins-official
```

Verify installation:
```
/plugin
```

## Template Usage Examples

### Refactoring
```
/ralph-loop:ralph-loop "Refactor the auth module:

Requirements:
- Extract AuthService class
- Remove direct database calls from controllers
- Add proper dependency injection

Success criteria:
- All tests passing
- No circular dependencies
- Code coverage maintained

Output <promise>REFACTOR_COMPLETE</promise> when done." --max-iterations 30 --completion-promise "REFACTOR_COMPLETE"
```

### Feature Implementation
```
/ralph-loop:ralph-loop "Implement user password reset:

Requirements:
- Generate secure reset tokens
- Send email with reset link
- Validate tokens and allow password update

Success criteria:
- Feature fully functional
- All new code tested
- Tests passing (>80% coverage)

Output <promise>FEATURE_COMPLETE</promise> when done." --max-iterations 50 --completion-promise "FEATURE_COMPLETE"
```

### Bug Fix
```
/ralph-loop:ralph-loop "Fix: Login fails with special characters

Bug Details:
- Users with # or @ in passwords can't log in
- Expected: All valid passwords work
- Actual: Returns 'Invalid credentials'

Success criteria:
- Bug fixed and verified
- All tests passing
- No regressions

Output <promise>BUG_FIXED</promise> when done." --max-iterations 20 --completion-promise "BUG_FIXED"
```

## Combining with Compound Engineering

You can use Ralph Wiggum with the Compound Engineering workflow:

1. **Plan**: Use `/workflows:plan` to create a detailed plan
2. **Execute with Ralph**: Use `/ralph-loop` to autonomously execute the plan
3. **Review**: Use `/workflows:review` to review the results
4. **Compound**: Use `/workflows:compound` to document learnings

## Tips for Success

1. **Be specific** - Clear requirements lead to better results
2. **Define success criteria** - Know when the task is done
3. **Use completion promises** - The exact text to signal completion
4. **Set iteration limits** - Prevent runaway loops
5. **Start small** - Test with simple tasks first

## Resources

- [Full Setup Guide](../RALPH-SETUP.md)
- [Anthropic Documentation](https://docs.anthropic.com/claude/docs/plugins)
- [Ralph Wiggum Technique](https://awesomeclaude.ai/ralph-wiggum)
