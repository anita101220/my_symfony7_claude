# Git Commit & Push Skill

Stage, commit, and push every changed file in the working tree to the configured git remote.

## How to invoke

/git-push [commit message]

- With no argument: Claude auto-generates a concise commit message from the diff.
- With an argument: the supplied text is used as the commit message verbatim.

Examples:
  /git-push
  /git-push fix validation bug in CreateBookRequest

## Steps

1. **Show current state** — run `git status` and `git diff --stat` so both you and the user can see exactly what has changed.

2. **Bail-out guard** — if `git status` reports nothing to commit, tell the user and stop.

3. **Craft commit message** — if no message was supplied, read `git diff` (full) and write a one-sentence summary in the imperative mood (e.g. "Add ISBN validation to CreateBookRequest"). If a message was supplied, use it as-is.

4. **Stage changed files** — run `git add` on every modified, added, or deleted file reported by `git status` (tracked files only — do not `git add -A` blindly; use `git add -u` for tracked changes plus explicit paths for untracked files the user intends to include).

5. **Commit** — create the commit with the message from step 3. Use a HEREDOC to avoid shell quoting issues:
   ```
   git commit -m "$(cat <<'EOF'
   <message here>
   EOF
   )"
   ```

6. **Push** — run `git push`. If the branch has no upstream yet, run `git push -u origin <branch>`.

7. **Confirm** — report the commit hash, branch, and remote URL so the user can verify.

## Output format

After completing, print:

```
Committed: <short-hash> — <commit message>
Pushed to: <remote-url> (<branch>)
Files changed: <N>
```

If anything fails (dirty merge state, rejected push, auth error), stop immediately and show the raw git error — do not retry automatically.
