---
name: plan
description: Turn feature ideas into detailed implementation plans
---

Use this command when the user wants to start a new feature or task.

Follow the compound engineering planning workflow:

1. **Understand the goal** - Ask clarifying questions if needed
2. **Explore the codebase** - Use Glob and Grep to understand existing patterns
3. **Identify dependencies** - What files, APIs, or systems will this touch?
4. **Create a plan** - Write a detailed plan to `plans/{feature-name}.md` including:
   - Goal and success criteria
   - Files to modify/create
   - Implementation approach
   - Testing strategy
   - Risks and mitigations
5. **Get approval** - Present the plan and wait for user confirmation before proceeding

Remember: 80% planning, 20% execution. Be thorough.
