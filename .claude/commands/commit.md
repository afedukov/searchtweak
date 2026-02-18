Review all staged and unstaged changes, then create one or more atomic git commits.

Steps:
1. Run `git status` and `git diff` (staged + unstaged) to understand all changes.
2. Run `git log --oneline -10` to see recent commit message style.
3. Analyze the changes to identify logical groups (e.g., documentation updates, refactoring, feature X, bugfix Y).
4. For EACH logical group of changes, perform the following loop:
   a. Stage ONLY the files relevant to this specific group (prefer specific file names). Do NOT stage files that contain secrets.
   b. Write a concise commit message (1-2 sentences) focusing on the "why". Follow the recent style.
   c. **Never** add `Co-Authored-By`.
   d. Create the commit using a HEREDOC.
5. If changes are unrelated, ensure they are committed separately. If all changes are tightly coupled, one commit is acceptable.
6. Run `git status` after the process to verify that the working directory is clean.

If there are no changes to commit, inform the user and do nothing.
