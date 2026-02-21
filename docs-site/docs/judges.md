# Judges (AI)

AI Judges let LLMs grade snapshots automatically using the same evaluation framework as human evaluators.

## Supported Providers

- `openai`
- `anthropic`
- `google`
- `deepseek`
- `xai`
- `groq`
- `mistral`
- `custom_openai`
- `ollama`

Provider notes:

- Most providers require API key.
- `ollama` can work without API key.
- `custom_openai` uses custom OpenAI-compatible base URL.

## Judge Configuration

- Name, description
- Provider + model
- API key / base URL
- Prompts per scale (`binary`, `graded`, `detail`)
- Model params (`key: value`)
- Batch size (`1..20`)
- Tags

## Prompt Design Guidance

- Keep grading criteria explicit and stable.
- Require concise reason text.
- Keep outputs machine-parseable and deterministic.
- Enforce output language policy (for example, English-only reasons if required by your workflow).

## Runtime Statuses

Active judges can show:

- **Working**: currently processing claimed feedback slots.
- **Waiting**: active, but no available slot to claim now.
- **Error marker**: last request failed.

Inactive judges do not show runtime status badges.

Judges page status filter uses segmented control:

- `All`
- `Active`
- `Archived`

## Security, Privacy & Costs

- **API Key Storage**: All provider API keys are securely encrypted at rest in the database and are never sent to the client side.
- **Costs (BYOK)**: You bring your own API keys, meaning you have complete transparency over costs and pay the LLM provider directly for the tokens you use. Token usage is logged per evaluation to help forecast expenses.
- **Data Privacy (Local LLMs)**: If your search data is highly confidential, you can use local, self-hosted LLMs (via `ollama` or `custom_openai`) so that your evaluation data never leaves your internal network.

## Assignment and Safety Rules

- One AI judge can grade at most one slot per snapshot.
- Human and AI grades can coexist under strategy `3`.
- Admin/human override can replace AI-attributed slot ownership.
- Judge processing respects lock expiry and open slot availability.

## Judge Logs

Use judge logs for observability:

- request/response payloads
- HTTP status
- latency
- token usage
- error text

Judge Logs page supports:

- global mode (`/judges/logs`) and per-judge mode (`/judges/{judge}/logs`)
- filters by status/judge/evaluation/date
- **Export JSONL** of the currently filtered dataset (includes request/response bodies)

This is the primary source for debugging provider, prompt, and parsing issues.
