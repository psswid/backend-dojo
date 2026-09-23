#!/usr/bin/env python3
"""Structural validator for Backend Dojo lesson files.

Usage:
    python3 scripts/validate_lessons.py database/seeders/content/lessons/<slug>.json [more...]

Checks schema, payload shapes, markup balance, tag coverage vs the module's
questions/tasks, and php -l on every `code` payload with lang=php.
Exit code 0 => PASS (warnings allowed), 1 => FAIL.
"""
import json
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CONTENT = ROOT / "database" / "seeders" / "content"

KINDS = {"prose", "heading", "code", "table", "steps", "callout", "figure"}
TONES = {"tip", "watch", "why", "warning", "danger"}
LANGS = {"php", "sql", "text", "bash", "plain"}
DIFFS = {"easy", "medium", "hard"}

TEXT_KEYS = {"text", "intro", "state", "caption", "note", "summary"}


def errs(errors, msg):
    errors.append(msg)


def check_markup(field, value, where, errors):
    """Balanced backticks / bold and https-only links in text fields.

    Text fields are always HTML-escaped at render time, so operators like
    `=>`, `<`, `>` are safe literally — only the figure `html` payload is raw.
    """
    if not isinstance(value, str):
        return
    if value.count("`") % 2 != 0:
        errs(errors, f"{where}.{field}: unbalanced backticks ({value.count('`')})")
    if value.count("**") % 2 != 0:
        errs(errors, f"{where}.{field}: unbalanced **bold**")
    # links must be https and complete
    for m in re.finditer(r"\[[^\]]*\]\(([^)]*)\)", value):
        url = m.group(1)
        if not url.startswith("https://"):
            errs(errors, f"{where}.{field}: non-https link {url!r}")
    for m in re.finditer(r"\[([^\]]*)\]\(https?://[^)]*\)", value):
        if m.group(1).strip() == "":
            errs(errors, f"{where}.{field}: link with empty label")


def walk_text(payload, where, errors, seen=None):
    seen = seen or set()
    for k, v in (payload or {}).items():
        if k in TEXT_KEYS and k not in seen and isinstance(v, str):
            check_markup(k, v, where, errors)
        elif k == "table" and isinstance(v, dict):
            walk_text(v, f"{where}.table", errors)
    # bullets inside text are fine; nothing else to walk


def lint_php(code, where, errors):
    p = subprocess.run(
        ["php", "-l"], input="\n<?php\n" + code + "\n", capture_output=True, text=True
    )
    if p.returncode != 0:
        tail = (p.stderr or p.stdout).strip().splitlines()
        errs(errors, f"{where}: php -l failed — {tail[-1] if tail else 'unknown'}")


def validate_file(path, tag_sets):
    errors = []
    warns = []

    data = json.loads(path.read_text(encoding="utf-8"))
    slug = path.stem

    if data.get("topic_slug") != slug:
        errs(errors, f"topic_slug {data.get('topic_slug')!r} != filename stem {slug!r}")

    lessons = data.get("lessons")
    if not isinstance(lessons, list) or not lessons:
        errs(errors, "lessons: empty or missing")
        lessons = []

    qtags, ttags = tag_sets.get(slug, (set(), set()))
    covered_q = set()
    covered_t = set()
    seen_slugs = set()

    for li, lesson in enumerate(lessons):
        where = f"lessons[{li}]"
        for field in ("slug", "title", "summary", "minutes", "difficulty", "tags", "sections"):
            if field not in lesson:
                errs(errors, f"{where}: missing '{field}'")
        slugv = lesson.get("slug", "")
        if slugv in seen_slugs:
            errs(errors, f"{where}: duplicate lesson slug {slugv!r}")
        seen_slugs.add(slugv)
        if lesson.get("title") and len(lesson["title"]) > 110:
            warns.append(f"{where}: long title ({len(lesson['title'])} chars)")
        if lesson.get("difficulty") not in DIFFS:
            errs(errors, f"{where}: bad difficulty {lesson.get('difficulty')!r}")
        minutes = lesson.get("minutes")
        if not isinstance(minutes, int) or not 1 <= minutes <= 20:
            errs(errors, f"{where}: minutes must be int 1..20, got {minutes!r}")
        check_markup("summary", lesson.get("summary"), where, errors)
        tags = lesson.get("tags") or []
        if isinstance(tags, list):
            if qtags and not set(tags) & qtags:
                warns.append(f"{where} ({slugv}): no tag overlap with module QUESTIONS")
            if ttags and not set(tags) & ttags:
                warns.append(f"{where} ({slugv}): no tag overlap with module TASKS")
            for q, t in ((qtags, covered_q), (ttags, covered_t)):
                t.update(set(tags) & q)
        else:
            errs(errors, f"{where}: tags not a list")

        sections = lesson.get("sections") or []
        if not sections:
            errs(errors, f"{where}: no sections")
        seen_keys = set()
        for si, s in enumerate(sections):
            swhere = f"{where}.sections[{si}]"
            key = s.get("key")
            if not isinstance(key, str) or not re.fullmatch(r"[a-z0-9-]{1,40}", key):
                errs(errors, f"{swhere}: bad key {key!r}")
            elif key in seen_keys:
                errs(errors, f"{swhere}: duplicate key {key!r}")
            seen_keys.add(key)
            kind = s.get("kind")
            if kind not in KINDS:
                errs(errors, f"{swhere}: bad kind {kind!r}")
                continue
            payload = s.get("payload")
            if kind == "heading":
                if not s.get("title"):
                    errs(errors, f"{swhere}: heading needs a title")
                if payload not in (None, {}):
                    errs(errors, f"{swhere}: heading must not carry a payload")
                continue
            if not isinstance(payload, dict):
                errs(errors, f"{swhere}: {kind} needs a dict payload")
                continue

            if kind == "prose":
                if not isinstance(payload.get("text"), str) or not payload["text"].strip():
                    errs(errors, f"{swhere}: prose payload.text required")
                walk_text(payload, swhere, errors)
            elif kind == "code":
                code = payload.get("code")
                if not isinstance(code, str) or not code.strip():
                    errs(errors, f"{swhere}: code payload.code required")
                lang = payload.get("lang", "php")
                if lang not in LANGS:
                    errs(errors, f"{swhere}: unknown lang {lang!r}")
                if lang == "php":
                    lint_php(code, swhere, errors)
                walk_text(payload, swhere, errors)
            elif kind == "table":
                headers = payload.get("headers", [])
                rows = payload.get("rows", [])
                if not isinstance(headers, list) or not headers:
                    errs(errors, f"{swhere}: table headers required")
                for ri, row in enumerate(rows):
                    if not isinstance(row, list) or len(row) != len(headers):
                        errs(errors, f"{swhere}: row {ri} length != headers ({len(headers)})")
                for col in (payload.get("mono") or []) + (payload.get("em") or []):
                    if not isinstance(col, int) or col < 0 or col >= len(headers):
                        errs(errors, f"{swhere}: bad col index {col!r}")
                walk_text(payload, swhere, errors)
            elif kind == "steps":
                steps = payload.get("steps")
                if not isinstance(steps, list) or not steps:
                    errs(errors, f"{swhere}: steps payload.steps required")
                if len(steps) > 6:
                    warns.append(f"{swhere}: {len(steps)} steps (target <= 6)")
                allowed = {"title", "text", "code", "table", "state"}
                for i, step in enumerate(steps):
                    if not isinstance(step, dict) or not set(step) <= allowed:
                        errs(errors, f"{swhere}.steps[{i}]: bad keys {set(step) - allowed if isinstance(step, dict) else step!r}")
                        continue
                    if not any(step.get(k) for k in ("title", "text", "code")):
                        errs(errors, f"{swhere}.steps[{i}]: needs title, text or code")
                    walk_text(step, f"{swhere}.steps[{i}]", errors)
                    if isinstance(step.get("table"), dict):
                        t = step["table"]
                        for ri, row in enumerate(t.get("rows", [])):
                            if len(row) != len(t.get("headers", [])):
                                errs(errors, f"{swhere}.steps[{i}].table: row {ri} width mismatch")
                walk_text(payload, swhere, errors)
            elif kind == "callout":
                if payload.get("tone") not in TONES:
                    errs(errors, f"{swhere}: bad callout tone {payload.get('tone')!r}")
                walk_text(payload, swhere, errors)
            elif kind == "figure":
                html = payload.get("html")
                if not isinstance(html, str) or "ld-" not in html:
                    errs(errors, f"{swhere}: figure payload.html must contain ld-* diagram markup")
                if "ld-" not in html or not html.lstrip().startswith("<"):
                    errs(errors, f"{swhere}: figure html must be real markup")
                title = payload.get("title")
                if not isinstance(title, str) or not title.strip() or title.lstrip().startswith("<"):
                    errs(errors, f"{swhere}: figure title must be plain text, got {title!r}")
                if "<script" in html.lower() or "onerror=" in html.lower() or "style=" in html.lower():
                    errs(errors, f"{swhere}: figure html uses forbidden markup")
                walk_text(payload, swhere, errors)

    q_orphans = sorted(qtags - covered_q)
    t_orphans = sorted(ttags - covered_t)
    if q_orphans:
        warns.append(f"question tags not covered by any lesson: {q_orphans}")
    if t_orphans:
        warns.append(f"task tags not covered by any lesson: {t_orphans}")

    return errors, warns


def collect_tag_sets():
    tag_sets = {}
    for qfile in CONTENT.glob("*.json"):
        try:
            data = json.loads(qfile.read_text(encoding="utf-8"))
        except Exception:
            continue
        slug = data.get("topic", {}).get("slug")
        if not slug:
            continue
        qt = set()
        for q in data.get("questions", []):
            qt.update(q.get("tags") or [])
        tfile = CONTENT / "tasks" / f"{slug}.json"
        tt = set()
        if tfile.exists():
            try:
                tdata = json.loads(tfile.read_text(encoding="utf-8"))
                for t in tdata.get("tasks", []):
                    tt.update(t.get("tags") or [])
            except Exception:
                pass
        tag_sets[slug] = (qt, tt)
    return tag_sets


def main():
    files = sys.argv[1:]
    if not files:
        print("usage: validate_lessons.py <file.json> [...]")
        return 1
    tag_sets = collect_tag_sets()
    overall_fail = False
    for fp in files:
        path = Path(fp)
        if not path.exists():
            print(f"[FAIL] {path} does not exist")
            overall_fail = True
            continue
        try:
            errors, warns = validate_file(path, tag_sets)
        except Exception as e:
            print(f"[FAIL] {path}: unhandled {type(e).__name__}: {e}")
            overall_fail = True
            continue
        status = "PASS" if not errors else "FAIL"
        print(f"[{status}] {path.name}  ({len(errors)} errors, {len(warns)} warnings)")
        for w in warns:
            print(f"   warn: {w}")
        for e in errors:
            print(f"   error: {e}")
            overall_fail = True
    return 1 if overall_fail else 0


if __name__ == "__main__":
    sys.exit(main())
