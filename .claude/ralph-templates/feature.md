# Ralph Loop Template: Feature Implementation

## Command
```
/ralph-loop:ralph-loop "Implement {{FEATURE_NAME}}:

Overview:
{{FEATURE_DESCRIPTION}}

Requirements:
- {{REQUIREMENT_1}}
- {{REQUIREMENT_2}}
- {{REQUIREMENT_3}}

Technical Details:
- {{TECH_DETAIL_1}}
- {{TECH_DETAIL_2}}

Success criteria:
- Feature is fully functional
- All new code has tests
- Tests passing (aim for >80% coverage)
- No TypeScript/linter errors
- Documentation updated (if applicable)

Output <promise>FEATURE_COMPLETE</promise> when done." --max-iterations {{ITERATIONS}} --completion-promise "FEATURE_COMPLETE"
```

## Usage Example
Replace the placeholders:
- `{{FEATURE_NAME}}`: Name of the feature
- `{{FEATURE_DESCRIPTION}}`: Brief description
- `{{REQUIREMENT_*}}`: Specific feature requirements
- `{{TECH_DETAIL_*}}`: Technical implementation details
- `{{ITERATIONS}}`: Max iterations (recommend 40-80 for features)

## Example
```
/ralph-loop:ralph-loop "Implement user password reset:

Overview:
Add password reset functionality with email-based token verification

Requirements:
- Generate secure reset tokens
- Send reset email with token link
- Validate tokens and allow password update
- Expire tokens after 24 hours

Technical Details:
- Use crypto for token generation
- Store tokens in database with expiration
- Send emails via existing email service

Success criteria:
- Feature is fully functional
- All new code has tests
- Tests passing (aim for >80% coverage)
- No TypeScript/linter errors
- Documentation updated (if applicable)

Output <promise>FEATURE_COMPLETE</promise> when done." --max-iterations 50 --completion-promise "FEATURE_COMPLETE"
```
