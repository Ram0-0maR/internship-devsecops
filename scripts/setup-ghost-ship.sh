#!/usr/bin/env bash

set -e

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

BUNDLE="$PROJECT_ROOT/challenges/ghost-ship/repo.bundle"
TARGET="$PROJECT_ROOT/server1-web/laravel/public/ghost-ship"

echo "[*] Setting up Ghost Ship challenge..."

# Remove any existing deployment
rm -rf "$TARGET"

# Clone the repository from the bundle
git clone "$BUNDLE" "$TARGET"

echo "[+] Ghost Ship deployed successfully."
