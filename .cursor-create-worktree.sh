#!/bin/bash
set -euo pipefail
cd /home/shino/projects/odds
WORKTREE_ID="crlf-fix-$(openssl rand -hex 4)"
REPO_ROOT="$(git rev-parse --show-toplevel)"
REPO_BASENAME="$(basename "$REPO_ROOT")"
if command -v shasum >/dev/null 2>&1; then
	REPO_HASH="$(printf '%s' "$REPO_ROOT" | shasum -a 256 | cut -c1-12)"
else
	REPO_HASH="$(printf '%s' "$REPO_ROOT" | sha256sum | cut -c1-12)"
fi
REPO_KEY="${REPO_BASENAME}-${REPO_HASH}"
WORKTREE_SET_DIR="$HOME/.cursor/worktrees/$WORKTREE_ID"
WORKTREE_DIR="$WORKTREE_SET_DIR/$REPO_KEY"
mkdir -p "$WORKTREE_SET_DIR"
if [ -d "$WORKTREE_DIR" ]; then echo "ERROR: worktree directory already exists: $WORKTREE_DIR" >&2; exit 1; fi
WORKTREE_START_REF="${WORKTREE_START_REF:-HEAD}"
git worktree add --detach "$WORKTREE_DIR" "$WORKTREE_START_REF"
HEAD_COMMIT="$(git -C "$WORKTREE_DIR" rev-parse HEAD)"
echo "WORKTREE_ID=$WORKTREE_ID"
echo "REPO_KEY=$REPO_KEY"
echo "WORKTREE_PATH=$WORKTREE_DIR"
echo "REPO_ROOT=$REPO_ROOT"
echo "HEAD_COMMIT=$HEAD_COMMIT"
echo "WORKTREE_START_REF=$WORKTREE_START_REF"
