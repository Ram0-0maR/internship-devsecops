# 🏗️ System Architecture

## Overview

Hackademy CTF is built as a small multi-container infrastructure that mirrors a simplified production deployment.

Instead of running a single web server, requests travel through multiple layers, allowing participants to learn how modern web applications are deployed and how security mistakes can appear at each layer.

---

# High-Level Architecture

```text
                        Internet
                            │
                            ▼
                  HAProxy (Ingress)
                    HTTPS Termination
                            │
                    ctf-dmz-net
                            │
                            ▼
                   Caddy Web Server
               Static Files + FastCGI
                            │
                    Unix Socket
                            │
                            ▼
                      PHP-FPM Engine
                            │
                     Application Files
```

---

# Components

## HAProxy

### Purpose

Acts as the edge proxy for the platform.

### Responsibilities

* Accept HTTP/HTTPS traffic
* Redirect HTTP → HTTPS
* Terminate TLS
* Forward requests to Caddy
* Add forwarding headers
* Perform health checks

---

## Caddy

### Purpose

Serves static assets and forwards PHP requests to PHP-FPM.

### Responsibilities

* Serve HTML
* Serve CSS
* Serve images
* Forward PHP execution through FastCGI
* Produce structured access logs

---

## PHP-FPM

### Purpose

Executes PHP scripts.

### Responsibilities

* Process dynamic requests
* Generate HTML responses
* Keep application execution isolated from the web server

Communication with Caddy occurs over a Unix Domain Socket.

---

# Docker Networks

## ctf-dmz-net

Public-facing network.

Connected containers:

* HAProxy
* Caddy

Purpose:

Separates the ingress layer from the rest of the infrastructure.

---

## ctf-app-net

Application network.

Connected containers:

* Caddy
* PHP-FPM

Purpose:

Allows web and application layers to communicate.

---

## ctf-db-net

Reserved for future expansion.

Potential services:

* Database
* Storage
* Internal APIs

Keeping this network from the beginning makes future extensions easier without redesigning the topology.

---

# Request Flow

A typical request follows this path:

1. Browser connects using HTTPS.
2. HAProxy terminates TLS.
3. HAProxy forwards the request to Caddy.
4. Caddy serves static files or forwards PHP requests.
5. PHP-FPM executes the requested PHP script.
6. The response travels back through Caddy and HAProxy to the client.

---

# Security Layers

The platform intentionally separates responsibilities.

| Layer       | Responsibility                       |
| ----------- | ------------------------------------ |
| HAProxy     | Edge proxy, TLS termination, routing |
| Caddy       | Web server, static content           |
| PHP-FPM     | PHP execution                        |
| Application | Challenge logic                      |

This separation reflects a common production architecture and allows security lessons to be demonstrated at different layers.

---

# Challenge Mapping

Each challenge targets a specific layer or security concept.

| Challenge        | Focus Area             |
| ---------------- | ---------------------- |
| Forgotten Harbor | File exposure          |
| Captain's Cabin  | Information disclosure |
| Ghost Ship       | Version control        |
| Watch Tower      | Reverse proxy trust    |
| Treasure Vault   | Secret management      |

---

# Design Principles

The project was designed with the following goals:

* Keep the infrastructure simple enough for beginners.
* Demonstrate realistic deployment patterns.
* Isolate responsibilities between containers.
* Make every vulnerability intentional and educational.
* Preserve the original infrastructure while developing the CTF independently.

---

# Future Improvements

Planned enhancements include:

* Centralized logging
* Database-backed scoreboard
* Automated challenge reset scripts
* Monitoring dashboard
* Optional CTFd integration
* Additional challenge containers

---

*"Good architecture protects systems. Great architecture also teaches."*

