#!/usr/bin/env bash
set -Eeuo pipefail
trap 'echo "[ERR] line:$LINENO"; exit 1' ERR

REPO=${REPO:-}
RATE_LIMIT_SLEEP=${RATE_LIMIT_SLEEP:-2}
MAX_RETRY=${MAX_RETRY:-5}
TRACE_FILE="TRACEABILITY.md"

usage() {
  cat <<USAGE
Usage: $0 --try-run|--execute|--verify
USAGE
}

ensure_gh() {
  if command -v gh >/dev/null 2>&1; then
    return
  fi
  echo "[INFO] GitHub CLI not found. Attempting installation..."
  if command -v apt-get >/dev/null 2>&1; then
    sudo apt-get update -y >/dev/null
    if ! sudo apt-get install -y gh >/dev/null; then
      echo "[ERROR] Automatic gh installation failed. Install GitHub CLI manually and re-run." >&2
      exit 1
    fi
  else
    echo "[ERROR] Unsupported package manager. Install GitHub CLI manually from https://cli.github.com/" >&2
    exit 1
  fi
  gh --version >/dev/null
}

check_repo() {
  local origin=""
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    if origin=$(git remote get-url origin 2>/dev/null); then
      if [[ -z "$REPO" ]]; then
        REPO=${origin##*:}
        REPO=${REPO%.git}
      fi
    fi
  fi
  if [[ -z "$REPO" ]]; then
    echo "⚠️ No remote repository configured. Please set REPO=<owner>/<repo> and re-run." >&2
    exit 1
  fi
}

ensure_auth() {
  if gh auth status --hostname github.com >/dev/null 2>&1; then
    return
  fi
  echo "[INFO] GitHub CLI not authenticated. Starting login..."
  if ! gh auth login --hostname github.com --git-protocol https --web; then
    echo "[ERROR] GitHub authentication failed." >&2
    exit 1
  fi
}

run_with_retry() {
  local attempt=1
  local delay=$RATE_LIMIT_SLEEP
  while true; do
    if "$@"; then
      return 0
    fi
    if (( attempt >= MAX_RETRY )); then
      echo "[ERROR] Command failed after $attempt attempts: $*" >&2
      return 1
    fi
    sleep "$delay"
    delay=$((delay * 2))
    attempt=$((attempt + 1))
  done
}

read_csv() {
  python3 - <<'PY'
import csv
import json
with open('issues.csv', newline='', encoding='utf-8') as fh:
    reader = csv.DictReader(fh)
    print(json.dumps(list(reader)))
PY
}

unique_labels() {
  python3 - <<'PY'
import csv
labels=set()
with open('issues.csv', newline='', encoding='utf-8') as fh:
    for row in csv.DictReader(fh):
        for label in row['labels'].split(','):
            label = label.strip()
            if label:
                labels.add(label)
print("\n".join(sorted(labels)))
PY
}

unique_milestones() {
  python3 - <<'PY'
import csv
m=set()
with open('issues.csv', newline='', encoding='utf-8') as fh:
    for row in csv.DictReader(fh):
        milestone=row['milestone'].strip()
        if milestone:
            m.add(milestone)
print("\n".join(sorted(m)))
PY
}

lint_csv() {
  python3 lint_issues_csv.py
}

bootstrap_labels() {
  local existing
  existing=$(gh api repos/"$REPO"/labels --paginate --jq '.[].name' 2>/dev/null || true)
  while IFS= read -r label; do
    [[ -z "$label" ]] && continue
    if ! printf '%s\n' "$existing" | grep -Fxq "$label"; then
      echo "[INFO] Creating missing label $label"
      run_with_retry gh label create "$label" --repo "$REPO" >/dev/null
      existing+=$'\n'"$label"
    fi
  done < <(unique_labels)
}

bootstrap_milestones() {
  local existing_json
  existing_json=$(gh api repos/"$REPO"/milestones --paginate --jq '.[] | {title:.title, number:.number, state:.state}' 2>/dev/null || true)
  while IFS= read -r milestone; do
    [[ -z "$milestone" ]] && continue
    local number state
    number=$(printf '%s\n' "$existing_json" | jq -r "select(.title == \"$milestone\") | .number" 2>/dev/null || true)
    state=$(printf '%s\n' "$existing_json" | jq -r "select(.title == \"$milestone\") | .state" 2>/dev/null || true)
    if [[ -z "$number" || "$number" == "null" ]]; then
      echo "[INFO] Creating milestone $milestone"
      run_with_retry gh api repos/"$REPO"/milestones -X POST -f title="$milestone" >/dev/null
      existing_json=$(gh api repos/"$REPO"/milestones --paginate --jq '.[] | {title:.title, number:.number, state:.state}' 2>/dev/null || true)
    elif [[ "$state" != "open" ]]; then
      echo "[INFO] Reopening milestone $milestone"
      run_with_retry gh api repos/"$REPO"/milestones/$number -X PATCH -f state=open >/dev/null
      existing_json=$(gh api repos/"$REPO"/milestones --paginate --jq '.[] | {title:.title, number:.number, state:.state}' 2>/dev/null || true)
    fi
  done < <(unique_milestones)
}

load_traceability() {
  if [[ ! -f "$TRACE_FILE" ]]; then
    cat <<'HEADER' > "$TRACE_FILE"
| ID | Title | Labels | Milestone | Issue # | State | Timestamp |
|----|-------|--------|-----------|---------|-------|-----------|
HEADER
  fi
}

trace_contains() {
  local id="$1"
  grep -F "| $id |" "$TRACE_FILE" >/dev/null 2>&1
}

append_trace() {
  local id="$1"
  local title="$2"
  local labels="$3"
  local milestone="$4"
  local number="$5"
  local state="$6"
  local timestamp="$7"

  if trace_contains "$id" && grep -F "| $id |" "$TRACE_FILE" | grep -F "| $number |" >/dev/null 2>&1; then
    return
  fi

  printf '| %s | %s | %s | %s | %s | %s | %s |\n' \
    "$id" "$title" "$labels" "$milestone" "$number" "$state" "$timestamp" >> "$TRACE_FILE"
}

issue_exists_remote() {
  local title="$1"
  local existing
  existing=$(gh issue list --repo "$REPO" --state all --search "$title in:title" --json title --limit 1 --jq '.[0].title' 2>/dev/null || true)
  if [[ "$existing" == "$title" ]]; then
    return 0
  fi
  return 1
}

preflight() {
  ensure_gh
  check_repo
  ensure_auth
  if [[ ! -f issues.csv ]]; then
    echo "[ERROR] issues.csv not found" >&2
    exit 1
  fi
  lint_csv
  load_traceability
}

plan_actions() {
  python3 - <<'PY'
import csv
import json
rows=[]
with open('issues.csv', newline='', encoding='utf-8') as fh:
    for row in csv.DictReader(fh):
        rows.append(row)
print(json.dumps(rows))
PY
}

mode_try_run() {
  preflight
  bootstrap_labels
  bootstrap_milestones
  local json
  json=$(plan_actions)
  python3 - "$json" "$TRACE_FILE" "$REPO" <<'PY'
import json
import subprocess
import sys
from pathlib import Path

data=json.loads(sys.argv[1])
trace_path=Path(sys.argv[2])
repo=sys.argv[3]
trace_ids=set()
if trace_path.exists():
    for line in trace_path.read_text().splitlines()[2:]:
        if not line.strip():
            continue
        parts=[p.strip() for p in line.split('|') if p.strip()]
        if parts:
            trace_ids.add(parts[0])

print("[TRY-RUN] Planned issue actions:")
for row in data:
    row_id=row['id']
    title=row['title']
    proc=subprocess.run([
        'gh','issue','list','--repo',repo,'--state','all','--search',f"{title} in:title",'--json','title','--limit','1','--jq','.[0].title'
    ], capture_output=True, text=True)
    exists = proc.returncode==0 and proc.stdout.strip()==title
    if row_id in trace_ids or exists:
        print(f"SKIP: {title}")
    else:
        print(f"CREATE: {title}")
PY
}

mode_execute() {
  preflight
  bootstrap_labels
  bootstrap_milestones
  local json
  json=$(plan_actions)
  python3 - "$json" "$TRACE_FILE" "$REPO" "$RATE_LIMIT_SLEEP" "$MAX_RETRY" <<'PY'
import json
import os
import subprocess
import sys
import time
from datetime import datetime, timezone
from pathlib import Path
from tempfile import NamedTemporaryFile

data=json.loads(sys.argv[1])
trace_path=sys.argv[2]
repo=sys.argv[3]
base_sleep=float(sys.argv[4])
max_retry=int(sys.argv[5])

def trace_ids():
    ids=set()
    if Path(trace_path).exists():
        lines=Path(trace_path).read_text().splitlines()[2:]
        for line in lines:
            if not line.strip():
                continue
            parts=[p.strip() for p in line.split('|') if p.strip()]
            if parts:
                ids.add(parts[0])
    return ids

existing_trace=trace_ids()
created=0
skipped=0
failed=0

for row in data:
    row_id=row['id']
    title=row['title']
    labels=[label.strip() for label in row['labels'].split(',') if label.strip()]
    milestone=row['milestone'].strip()
    body=row['body']

    proc=subprocess.run([
        'gh','issue','list','--repo',repo,'--state','all','--search',f"{title} in:title",'--json','title','--limit','1','--jq','.[0].title'
    ], capture_output=True, text=True)
    exists_remote = proc.returncode==0 and proc.stdout.strip()==title
    if row_id in existing_trace or exists_remote:
        print(f"SKIP: {title}")
        skipped+=1
        continue

    attempt=1
    delay=base_sleep
    while True:
        with NamedTemporaryFile(mode='w+', encoding='utf-8', delete=False) as tmp:
            tmp.write(body)
            tmp_path=tmp.name
        try:
            cmd=['gh','issue','create','--repo',repo,'--title',title,'--milestone',milestone]
            for label in labels:
                cmd.extend(['--label',label])
            cmd.extend(['--body-file',tmp_path])
            proc=subprocess.run(cmd, text=True, capture_output=True)
        finally:
            try:
                os.remove(tmp_path)
            except FileNotFoundError:
                pass
        if proc.returncode==0:
            number=proc.stdout.strip().split('/')[-1]
            if not number.isdigit():
                # Fallback fetch number via search
                lookup=subprocess.run([
                    'gh','issue','list','--repo',repo,'--state','all','--search',f"{title} in:title",'--json','number','--limit','1','--jq','.[0].number'
                ], capture_output=True, text=True)
                number=lookup.stdout.strip()
            status=subprocess.run([
                'gh','issue','view',number,'--repo',repo,'--json','state','--jq','.state'
            ], capture_output=True, text=True)
            state=status.stdout.strip() if status.returncode==0 else 'unknown'
            timestamp=datetime.now(timezone.utc).isoformat()
            with open(trace_path,'a',encoding='utf-8') as fh:
                fh.write(f"| {row_id} | {title} | {row['labels']} | {milestone} | {number} | {state} | {timestamp} |\n")
            print(f"CREATED: {title} -> #{number}")
            created+=1
            break
        if attempt>=max_retry:
            print(f"FAILED: {title} -> {proc.stderr.strip()}")
            failed+=1
            break
        time.sleep(delay)
        delay*=2
        attempt+=1

print(f"Summary: Created={created}, Skipped={skipped}, Failed={failed}")
if failed:
    sys.exit(1)
PY
}

mode_verify() {
  preflight
  python3 - "$TRACE_FILE" "$REPO" <<'PY'
import subprocess
import sys
from pathlib import Path

trace_path=Path(sys.argv[1])
repo=sys.argv[2]
if not trace_path.exists():
    print("TRACEABILITY.md not found. Nothing to verify.")
    sys.exit(0)
lines=trace_path.read_text().splitlines()[2:]
print("| ID   | Title | Labels | Milestone | Issue # | State |")
print("|------|-------|--------|-----------|---------|-------|")
for line in lines:
    if not line.strip():
        continue
    parts=[p.strip() for p in line.split('|') if p.strip()]
    if len(parts)<7:
        continue
    id_, title, labels, milestone, number, _, _ = parts[:7]
    state_proc=subprocess.run([
        'gh','issue','view',number,'--repo',repo,'--json','state','--jq','.state'
    ], capture_output=True, text=True)
    state=state_proc.stdout.strip() if state_proc.returncode==0 else 'unknown'
    print(f"| {id_} | {title} | {labels} | {milestone} | {number} | {state} |")
PY
}

case "$1" in
  --try-run)
    mode_try_run
    ;;
  --execute)
    mode_execute
    ;;
  --verify)
    mode_verify
    ;;
  *)
    usage
    exit 1
    ;;
esac
