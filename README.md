# 🏴‍☠️ Hackademy CTF – Captain Cachebeard's Lost Treasure

A story-driven Capture The Flag designed to teach practical DevSecOps and web security concepts through exploration rather than guessing.

Instead of solving isolated challenges, players explore Captain Cachebeard's island, uncover forgotten mistakes, recover pieces of a lost treasure map, and ultimately discover the Captain's hidden treasure.

---

## Learning Objectives

This CTF introduces participants to practical security concepts commonly encountered in real environments:

* Directory and file enumeration
* Reverse proxies
* Docker networking
* PHP applications
* Information disclosure
* Git repository exposure
* HTTP headers
* Secret management
* Secure deployment practices

---

## Infrastructure

The platform is built using:

* HAProxy
* Caddy
* PHP-FPM
* Docker Compose
* Linux
* HTTPS (TLS)

---

## Challenge Progression

1. 🏝 Forgotten Harbor — Exposed Backup Files
2. 🏠 Captain's Cabin — Forgotten Maintenance Console
3. 👻 Ghost Ship — Git Repository History
4. 🗼 Watch Tower — Reverse Proxy Trust
5. 💰 Treasure Vault — Secret Management

Each challenge reveals another piece of Captain Cachebeard's story while teaching a practical security lesson.

---

## Repository Structure

```text
server0-ingress/
server1-web/
security-ips/

docs/
challenges/
scripts/
```

---

## Current Status

* ✅ Infrastructure complete
* ✅ Pirate world established
* ✅ Challenge 1 implemented
* 🚧 Remaining challenges under development

---

## Educational Philosophy

The goal is not to trick participants with obscure vulnerabilities.

Every challenge represents a realistic mistake that has occurred in production systems and encourages participants to think like defenders as well as attackers.

---

*"Every forgotten backup tells a story."*
— First Mate
