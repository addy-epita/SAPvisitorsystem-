# Ralph Loop Template: Bug Fix

## Command
```
/ralph-loop:ralph-loop "Fix bug: {{BUG_DESCRIPTION}}

Bug Details:
- Issue: {{ISSUE_SUMMARY}}
- Expected behavior: {{EXPECTED_BEHAVIOR}}
- Actual behavior: {{ACTUAL_BEHAVIOR}}

Steps to Reproduce:
1. {{STEP_1}}
2. {{STEP_2}}
3. {{STEP_3}}

Fix Requirements:
- Root cause identified and fixed
- No regressions introduced
- Add regression test if applicable

Success criteria:
- Bug is fixed and verified
- All existing tests still pass
- New regression test added (if applicable)
- No linter errors

Output <promise>BUG_FIXED</promise> when done." --max-iterations {{ITERATIONS}} --completion-promise "BUG_FIXED"
```

## Usage Example
Replace the placeholders:
- `{{BUG_DESCRIPTION}}`: Brief bug summary
- `{{ISSUE_SUMMARY}}`: What's going wrong
- `{{EXPECTED_BEHAVIOR}}`: What should happen
- `{{ACTUAL_BEHAVIOR}}`: What's actually happening
- `{{STEP_*}}`: Steps to reproduce
- `{{ITERATIONS}}`: Max iterations (recommend 15-30)

## Example
```
/ralph-loop:ralph-loop "Fix bug: Login fails with special characters in password

Bug Details:
- Issue: Users with special characters (#, @, etc.) in passwords cannot log in
- Expected behavior: All valid passwords should work
- Actual behavior: Returns 'Invalid credentials' error

Steps to Reproduce:
1. Create user with password 'MyP@ss#123'
2. Attempt to log in
3. Observe authentication failure

Fix Requirements:
- Root cause identified and fixed
- No regressions introduced
- Add regression test if applicable

Success criteria:
- Bug is fixed and verified
- All existing tests still pass
- New regression test added (if applicable)
- No linter errors

Output <promise>BUG_FIXED</promise> when done." --max-iterations 20 --completion-promise "BUG_FIXED"
```
