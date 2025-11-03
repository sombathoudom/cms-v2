#!/usr/bin/env python3
import csv
import re
import sys
from pathlib import Path

CSV_PATH = Path('issues.csv')
REQUIRED_HEADINGS = ["### Summary", "### Scope", "### Acceptance Criteria", "### Notes"]
VALID_PRIORITIES = {"priority:P0", "priority:P1", "priority:P2"}
MILESTONES = {"Week 1", "Week 2", "Week 3", "Week 4"}
LABEL_PATTERN = re.compile(r"^[a-z]+:[A-Za-z0-9]+$")
ID_PATTERN = re.compile(r"^E\d+-F\d+-I\d+$")

def fail(msg: str) -> None:
    print(f"FAIL: {msg}")
    sys.exit(1)

if not CSV_PATH.exists():
    fail("issues.csv not found")

with CSV_PATH.open(newline='', encoding='utf-8') as fh:
    reader = csv.DictReader(fh)
    rows = list(reader)

if reader.fieldnames != ["id", "title", "body", "labels", "milestone"]:
    fail("Invalid CSV headers; expected id,title,body,labels,milestone")

ids = set()
titles = set()
for idx, row in enumerate(rows, start=2):
    row_id = row['id'].strip()
    title = row['title'].strip()
    body = row['body']
    labels = row['labels'].split(',')
    milestone = row['milestone'].strip()

    if not row_id:
        fail(f"Row {idx}: empty id")
    if not title:
        fail(f"Row {idx}: empty title")
    if not body:
        fail(f"Row {idx}: empty body")
    if not row['labels']:
        fail(f"Row {idx}: empty labels")
    if not milestone:
        fail(f"Row {idx}: empty milestone")

    if row_id in ids:
        fail(f"Duplicate id detected: {row_id}")
    ids.add(row_id)

    if title in titles:
        fail(f"Duplicate title detected: {title}")
    titles.add(title)

    if not title.startswith(f"{row_id}: "):
        fail(f"Row {idx}: title must begin with '{row_id}: '")

    if not ID_PATTERN.match(row_id):
        fail(f"Row {idx}: id '{row_id}' malformed")

    for heading in REQUIRED_HEADINGS:
        if heading not in body:
            fail(f"Row {idx}: missing heading '{heading}'")

    label_set = [label.strip() for label in labels if label.strip()]
    if len(label_set) != 4:
        fail(f"Row {idx}: expected 4 labels, found {len(label_set)}")

    for label in label_set:
        if not LABEL_PATTERN.match(label):
            fail(f"Row {idx}: label '{label}' has invalid format")
    priorities = [label for label in label_set if label.startswith('priority:')]
    if len(priorities) != 1 or priorities[0] not in VALID_PRIORITIES:
        fail(f"Row {idx}: invalid priority label")

    if milestone not in MILESTONES:
        fail(f"Row {idx}: milestone '{milestone}' out of range")

print("CSV Lint Report: PASS")
