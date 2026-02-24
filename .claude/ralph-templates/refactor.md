# Ralph Loop Template: Code Refactoring

## Command
```
/ralph-loop:ralph-loop "Refactor {{TARGET}}:

Requirements:
- {{REQUIREMENT_1}}
- {{REQUIREMENT_2}}
- {{REQUIREMENT_3}}

Constraints:
- Maintain existing functionality
- All existing tests must pass
- Follow project coding standards

Success criteria:
- Code is cleaner and more maintainable
- No breaking changes
- All tests passing
- No linter errors

Output <promise>REFACTOR_COMPLETE</promise> when done." --max-iterations {{ITERATIONS}} --completion-promise "REFACTOR_COMPLETE"
```

## Usage Example
Replace the placeholders:
- `{{TARGET}}`: The code/module to refactor
- `{{REQUIREMENT_*}}`: Specific refactoring goals
- `{{ITERATIONS}}`: Max iterations (recommend 20-40)

## Example
```
/ralph-loop:ralph-loop "Refactor the user authentication module:

Requirements:
- Extract business logic into service classes
- Remove code duplication in validation
- Improve error handling

Constraints:
- Maintain existing functionality
- All existing tests must pass
- Follow project coding standards

Success criteria:
- Code is cleaner and more maintainable
- No breaking changes
- All tests passing
- No linter errors

Output <promise>REFACTOR_COMPLETE</promise> when done." --max-iterations 30 --completion-promise "REFACTOR_COMPLETE"
```
