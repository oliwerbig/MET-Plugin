#!/usr/bin/env bash
set -euo pipefail

# Docker-based FTP deploy script using lftp inside an Alpine image.
# Requires env vars: FTP_HOST, FTP_USER, FTP_PASS, FTP_TARGET

: ${FTP_HOST:?Need to set FTP_HOST}
: ${FTP_USER:?Need to set FTP_USER}
: ${FTP_PASS:?Need to set FTP_PASS}
: ${FTP_TARGET:?Need to set FTP_TARGET}

# Gather changed files via git
mapfile -t changed < <(git status --porcelain | awk '{print substr($0,4)}')
if [ ${#changed[@]} -eq 0 ]; then
  echo "No changed files to upload."
  exit 0
fi

# Build a temporary upload list for lftp
UPLOAD_LIST=$(mktemp)
for f in "${changed[@]}"; do
  # Skip deleted files
  if [ -f "$f" ]; then
    echo "$f" >> "$UPLOAD_LIST"
  fi
done

if [ ! -s "$UPLOAD_LIST" ]; then
  echo "No files to upload after filtering."
  rm -f "$UPLOAD_LIST"
  exit 0
fi

# Run lftp to mirror individual files
echo "Uploading ${#changed[@]} files via Docker+lftp..."
for f in "${changed[@]}"; do
  if [ ! -f "$f" ]; then
    continue
  fi
  remote_dir="$FTP_TARGET/$(dirname "$f")"
  echo "Uploading $f -> $remote_dir"
  docker run --rm -v "$PWD":/app -e FTP_HOST -e FTP_USER -e FTP_PASS alpine:3.18 /bin/sh -c "apk add --no-cache lftp >/dev/null 2>&1; lftp -u \"$FTP_USER\",\"$FTP_PASS\" \"$FTP_HOST\" -e 'set ftp:ssl-allow no; mkdir -p \"$remote_dir\"; put -O \"$remote_dir\" \"/app/$f\"; quit'"
done

rm -f "$UPLOAD_LIST"

echo "Upload complete."
