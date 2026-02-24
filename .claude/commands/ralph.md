---
name: ralph
description: Launch an autonomous Ralph Wiggum execution loop
---

Use this command to start an autonomous task execution loop.

## Usage

```
/ralph "your task description here" [options]
```

## Parameters

- `--iterations, -i`: Maximum iterations (default: 30)
- `--promise, -p`: Completion promise text (default: "COMPLETE")
- `--timeout, -t`: Timeout per iteration in minutes (default: 10)

## Example

```
/ralph "Refactor authentication module to use JWT" --iterations 25 --promise "DONE"
```

## Best Practices

1. Be specific about requirements
2. Define clear success criteria
3. Include completion promise in the task description
4. Set reasonable iteration limits
