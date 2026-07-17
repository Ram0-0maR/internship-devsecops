# 🎯 Challenge Design

## Educational Philosophy

Every challenge in Hackademy CTF is based on a realistic security mistake.

The objective is not to trick participants with obscure mechanics or unrealistic exploits.

Instead, every flag represents a lesson that engineers should carry into real-world systems.

The ideal participant should finish each challenge understanding:

* What happened.
* Why it happened.
* How it could have been prevented.

---

# Challenge Lifecycle

Every challenge follows the same structure.

```
Story
    │
    ▼
Hint
    │
    ▼
Exploration
    │
    ▼
Discovery
    │
    ▼
Flag
    │
    ▼
Lesson
```

This keeps the experience consistent throughout the CTF.

---

# Challenge 1 — Forgotten Harbor

## Story

Captain Cachebeard never removed old deployment backups.

A forgotten archive still remains accessible from the harbor.

---

## Learning Objective

Understand how exposed backup files can reveal sensitive information.

---

## Skills

* Directory enumeration
* Archive inspection
* Information gathering

---

## Intended Solution

1. Read the landing page.
2. Inspect the HTML source.
3. Notice the First Mate's clue.
4. Enumerate common backup locations.
5. Discover the backup archive.
6. Download and inspect the ZIP.
7. Recover Treasure Piece #1.

---

## Common Mistakes

* Looking for SQL injection immediately.
* Ignoring HTML comments.
* Assuming every page is vulnerable.

---

## Defensive Lesson

Never store deployment archives inside publicly accessible web directories.

---

# Challenge 2 — Captain's Cabin

## Story

The Captain forgot to remove an internal maintenance console.

---

## Learning Objective

Recognize information disclosure through exposed internal tools.

---

## Skills

* Reading application output
* Source inspection
* Understanding debug information

---

## Intended Solution

1. Read `config.old`.
2. Discover the maintenance endpoint.
3. Visit the maintenance console.
4. Recover Treasure Piece #2.
5. Notice the clue toward the Ghost Ship.

---

## Defensive Lesson

Never expose maintenance or debugging interfaces in production.

---

# Challenge 3 — Ghost Ship

## Story

The Captain deleted a file and believed it disappeared forever.

Git had other ideas.

---

## Learning Objective

Understand that deleting a file does not remove it from Git history.

---

## Skills

* Git history
* Commit inspection
* Repository analysis

---

## Intended Solution

1. Discover the abandoned repository.
2. Recover the Git history.
3. Inspect previous commits.
4. Recover the deleted treasure file.

---

## Defensive Lesson

Treat Git history as sensitive data.

Never expose repositories publicly.

---

# Challenge 4 — Watch Tower

## Story

The guards believed anyone claiming to be local.

The Watch Tower trusted HTTP headers.

---

## Learning Objective

Understand the risks of trusting forwarded headers without validation.

---

## Skills

* HTTP requests
* Proxy headers
* Request manipulation

---

## Intended Solution

Manipulate trusted headers to access restricted functionality or reveal Treasure Piece #4.

---

## Defensive Lesson

Only trust forwarding headers added by known reverse proxies.

---

# Challenge 5 — Treasure Vault

## Story

The Captain believed secrets were safe simply because they were hidden.

---

## Learning Objective

Understand secure secret management.

---

## Skills

* Configuration review
* Information disclosure
* Secure deployment practices

---

## Intended Solution

Discover an exposed secret through a realistic misconfiguration and recover the final treasure piece.

---

## Defensive Lesson

Never expose secrets through configuration files, source code, or debugging output.

Use dedicated secret management solutions where appropriate.

---

# Difficulty Curve

| Challenge        | Difficulty | Primary Skill          |
| ---------------- | ---------- | ---------------------- |
| Forgotten Harbor | ⭐          | Enumeration            |
| Captain's Cabin  | ⭐⭐         | Information Disclosure |
| Ghost Ship       | ⭐⭐⭐        | Git                    |
| Watch Tower      | ⭐⭐⭐⭐       | Reverse Proxy          |
| Treasure Vault   | ⭐⭐⭐⭐⭐      | Secrets Management     |

---

# Design Rules

Every future challenge should follow these principles:

* One primary learning objective.
* One realistic vulnerability.
* Story supports the challenge.
* No guessing required.
* Multiple clues before the solution.
* Educational takeaway after completion.
* Easy reset for organizers.

---

*"Every treasure piece is a lesson disguised as a flag."*
