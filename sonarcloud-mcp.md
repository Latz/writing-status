# SonarCloud MCP Setup

## What it is

The `sonarqube-mcp-server` npm package exposes SonarCloud data as MCP tools inside Claude Code. Once configured, you can query quality gate status, issues, measures, and hotspots directly from the Claude Code conversation without leaving the terminal.

## Installation

No manual install needed — the server runs on demand via `npx`.

Add the following to your project's `.claude/settings.json` (or global `~/.claude/settings.json`):

```json
{
  "mcpServers": {
    "sonarqube": {
      "command": "npx",
      "args": ["-y", "sonarqube-mcp-server"],
      "env": {
        "SONARQUBE_TOKEN": "<your-sonarcloud-token>",
        "SONARQUBE_URL": "https://sonarcloud.io"
      }
    }
  }
}
```

**`SONARQUBE_URL`** must be `https://sonarcloud.io` (not a self-hosted SonarQube instance URL).

**`SONARQUBE_TOKEN`** — generate one at:  
`https://sonarcloud.io/account/security` → Generate Token → Type: User Token.

## Project key

The project key is defined in `sonar-project.properties`:

```
sonar.projectKey=Latz_draft-status
```

Use this value as the `project_key` parameter in all MCP tool calls.

## Tools that work on SonarCloud

| Tool                  | Works | Notes                                                           |
| --------------------- | ----- | --------------------------------------------------------------- |
| `system_ping`         | ✅     | Returns `pong`                                                  |
| `system_health`       | ❌     | `/api/v2/system/health` is SonarQube-only                       |
| `projects`            | ❌     | Requires `organization` param not exposed by the MCP            |
| `quality_gate_status` | ✅     | Use `project_key`                                               |
| `issues`              | ✅     | Use `project_key` + optional filters                            |
| `measures_component`  | ✅     | Use `project_key`                                               |
| `hotspots`            | ✅     | Use `project_key`                                               |
| `source_code`         | ✅     | Use component key (e.g. `Latz_draft-status:writing-status.php`) |

## Example queries

**Check quality gate:**

```
quality_gate_status(project_key: "Latz_draft-status")
```

**New code issues only:**

```
issues(project_key: "Latz_draft-status", in_new_code_period: true)
```

**Open issues by severity:**

```
issues(project_key: "Latz_draft-status", resolved: false, severities: ["CRITICAL", "BLOCKER"])
```

## Known limitations

- `system_health` is unavailable on SonarCloud (SonarQube on-prem only).
- `projects` listing requires an `organization` parameter the MCP doesn't pass automatically — use the project key directly instead.
- The token in `settings.json` is stored in plaintext. Use the project-level settings file (not the global one) and ensure it is listed in `.gitignore` if the token should not be committed.
