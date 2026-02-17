Review all staged and unstaged changes, then create a git commit.

Steps:
1. Run `git status` and `git diff` (staged + unstaged) to understand all changes.
2. Run `git log --oneline -10` to see recent commit message style.
3. Stage all relevant changed files (prefer specific file names over `git add .`). Do NOT stage files that contain secrets (.env, credentials, etc.).
4. Write a concise commit message (1-2 sentences) that focuses on the "why" rather than the "what". Follow the style of recent commits.
5. **Never** add `Co-Authored-By` or any similar attribution lines to the commit message.
6. Create the commit using a HEREDOC for the message.
7. Run `git status` after committing to verify success.

If there are no changes to commit, inform the user and do nothing.
