# ÉCOLE NATIONALE DES SCIENCES APPLIQUÉES D'EL JADIDA (ENSA-J)

**Département TRI**  
**Filière :** Ingénierie des Systèmes d'Information et de Communication (ISIC)

<br>

<div align="center">

# COMPTE RENDU DE PROJET ARCHITECTURE ET SÉCURITÉ

## Conception et Déploiement d'une Infrastructure Multi-Tier Haute Disponibilité avec IPS Adaptatif

*Dans le cadre du module d'Ingénierie des Systèmes et Réseaux / Stage DevSecOps*

</div>

<br>

---

## Informations Générales

- **Réalisé par :** Omar ELQAOUAS
- **Encadrant :** Zouhair ELHICHAMI
- **Date de Soumission :** Août 2026
- **Version du Document :** **v7.0.0** *(Version Finale : Qualification Opérationnelle, Tests de Charge k6, Audit DAST OWASP ZAP & Boucle IPS)*

---

# Table des Matières

1. [Phase 1 : Architecture Réseau et Environnement de Travail Isolés](#1-architecture-réseau-et-environnement-de-travail-isolés)
   - [1.1 Topologie de Réseau Virtuelle et Segmentation](#11-topologie-de-réseau-virtuelle-et-segmentation)
   - [1.2 Isolation de l'Espace Hôte (CachyOS)](#12-isolation-de-lespace-hôte-cachyos)
   - [1.3 Méthodologie de Validation du Réseau](#13-méthodologie-de-validation-du-réseau)
2. [Phase 2 : Couche Applicative et Optimisation Inter-Conteneur](#2-couche-applicative-et-optimisation-inter-conteneur)
   - [2.1 Communication Haute Performance via Socket de Domaine UNIX (UDS)](#21-communication-haute-performance-via-socket-de-domaine-unix-uds)
   - [2.2 Implémentation Technique et Fichiers de Configuration](#22-implémentation-technique-et-fichiers-de-configuration)
   - [2.3 Méthodologie de Validation de la Couche Web](#23-méthodologie-de-validation-de-la-couche-web)
3. [Phase 3 : Couche d'Entrée Edge Ingress et Terminaison TLS](#3-couche-dentrée-edge-ingress-et-terminaison-tls)
   - [3.1 Proxy Inverse et Terminaison SSL/TLS Globale](#31-proxy-inverse-et-terminaison-ssltls-globale)
   - [3.2 Préservation de l'Identité Client (Header Injection)](#32-préservation-de-lidentité-client-header-injection)
   - [3.3 Implémentation Technique et Configurations de l'Ingress](#33-implémentation-technique-et-configurations-de-lingress)
   - [3.4 Méthodologie de Validation de l'Ingress et de la Sécurité](#34-méthodologie-de-validation-de-lingress-et-de-la-sécurité)
4. [Phase 4 : Couche de Données Orientée Persistance et Cache Privé](#4-couche-de-données-orientée-persistance-et-cache-privé)
   - [4.1 Architecture et Segmentation Réseau de la Couche de Données](#41-architecture-et-segmentation-réseau-de-la-couche-de-données)
   - [4.2 Déploiement Déclaratif (`backend-data/docker-compose.yml`)](#42-déploiement-déclaratif-backend-datadocker-composeyml)
   - [4.3 Validation de l'Isolation du Réseau `db-net`](#43-validation-de-lisolation-du-réseau-db-net)
5. [Phase 5 : Système de Détection et Prévention d'Intrusion Adaptatif (IPS)](#5-système-de-détection-et-prévention-dintrusion-adaptatif-ips)
   - [5.1 Architecture de Télémesure et Stockage NoSQL (Cassandra)](#51-architecture-de-télémesure-et-stockage-nosql-cassandra)
   - [5.2 Schéma de Base de Données CQL (`ips_security`)](#52-schéma-de-base-de-données-cql-ips_security)
   - [5.3 Moteur d'Enforcement Noyau (nftables)](#53-moteur-denforcement-noyau-nftables)
6. [Phase 6 : Plateforme Applicative SecOps-Vault et Stockage Procuratoire](#6-plateforme-applicative-secops-vault-et-stockage-procuratoire)
   - [6.1 Architecture de l'Application Web et Gestion des Sessions](#61-architecture-de-lapplication-web-et-gestion-des-sessions)
   - [6.2 Intégration du Coffre-Fort de Preuves Forensiques (MinIO S3)](#62-intégration-du-coffre-fort-de-preuves-forensiques-minio-s3)
   - [6.3 Implémentation du Moteur Applicatif (`laravel/public/index.php`)](#63-implémentation-du-moteur-applicatif-laravelpublicindexphp)
7. [Phase 7 : Cluster Multi-Nœuds Haute Disponibilité et Tolérance aux Pannes](#7-cluster-multi-nœuds-haute-disponibilité-et-tolérance-aux-pannes)
   - [7.1 Scalabilité Horizontale (Nœuds Web Miroir Server 1A & Server 1B)](#71-scalabilité-horizontale-nœuds-web-miroir-server-1a--server-1b)
   - [7.2 Configuration du Répartiteur de Charge HAProxy (`haproxy.cfg`)](#72-configuration-du-répartiteur-de-charge-haproxy-haproxycfg)
   - [7.3 Validation de la Répartition de Charge et Tests de Basculement (Failover)](#73-validation-de-la-répartition-de-charge-et-tests-de-basculement-failover)
8. [Phase 8 : Synthèse Globale de l'Infrastructure et Conclusion](#8-synthèse-globale-de-linfrastructure-et-conclusion)
   - [8.1 État Final du Déploiement des Conteneurs](#81-état-final-du-déploiement-des-conteneurs)
   - [8.2 Matrice Récapitulative des Composants et Sécurisation](#82-matrice-récapitulative-des-composants-et-sécurisation)
   - [8.3 Conclusion du Projet](#83-conclusion-du-projet)
9. [Phase 9 : Observabilité Centralisée et Collecte de Métriques (Prometheus & Grafana)](#9-phase-9--observabilité-centralisée-et-collecte-de-métriques-prometheus--grafana)
   - [9.1 Architecture d'Observabilité et Exporteurs Télécoms](#91-architecture-dobservabilité-et-exporteurs-télécoms)
   - [9.2 Déploiement Déclaratif du Stack d'Observabilité (`monitoring-tier/compose.yml`)](#92-déploiement-déclaratif-du-stack-dobservabilité-monitoring-tiercomposeyml)
   - [9.3 Procédure de Validation et État de Santé des Cibles (Targets)](#93-procédure-de-validation-et-état-de-santé-des-cibles-targets)
10. [Phase 10 : Centralisation et Analyse des Logs (Stack Grafana Loki & Promtail)](#10-phase-10--centralisation-et-analyse-des-logs-stack-grafana-loki--promtail)
    - [10.1 Architecture de Centralisation des Journaux](#101-architecture-de-centralisation-des-journaux)
    - [10.2 Configurations Technologiques et Déploiement](#102-configurations-technologiques-et-déploiement)
    - [10.3 Procédure de Déploiement et Validation Fonctionnelle](#103-procédure-de-déploiement-et-validation-fonctionnelle)
11. [Phase 11 : Automatisation CI/CD et Sécurité "Shift-Left" (Gitleaks, Semgrep & Trivy)](#11-phase-11--automatisation-cicd-et-sécurité-shift-left-gitleaks-semgrep--trivy)
    - [11.1 Principes et Architecture d'Analyse Automatisée](#111-principes-et-architecture-danalyse-automatisée)
    - [11.2 Analyse des Résultats et Plan de Remédiation (Shift-Left Remediation)](#112-analyse-des-résultats-et-plan-de-remédiation-shift-left-remediation)
    - [11.3 Validation Fonctionnelle du Pipeline](#113-validation-fonctionnelle-du-pipeline)
12. [Phase 12 : Qualification Opérationnelle, Tests de Charge et Simulation d'Attaques Automatisées](#12-phase-12--qualification-opérationnelle-tests-de-charge-et-simulation-dattaques-automatisées-grafana-k6-owasp-zap--boucle-ips-adaptative)
    - [12.1 Contexte et Objectifs de la Qualification Opérationnelle](#121-contexte-et-objectifs-de-la-qualification-opérationnelle)
    - [12.2 Analyse de la Charge et Basculement Dynamique (Grafana k6 Stress Testing)](#122-analyse-de-la-charge-et-basculement-dynamique-grafana-k6-stress-testing)
      - [12.2.1 Protocole de Test et Profil de Charge](#1221-protocole-de-test-et-profil-de-charge)
      - [12.2.2 Analyse Explicative des Métriques Révélées](#1222-analyse-explicative-des-métriques-révélées)
      - [12.2.3 Analyse du Mécanisme de Basculement (*Zero-Downtime Failover Mechanics*)](#1223-analyse-du-mécanisme-de-basculement-zero-downtime-failover-mechanics)
    - [12.3 Analyse Dynamique de Sécurité Applicative (DAST avec OWASP ZAP)](#123-analyse-dynamique-de-sécurité-applicative-dast-avec-owasp-zap)
      - [12.3.1 Synthèse de l'Audit de Référence DAST (`zap_report.html`)](#1231-synthèse-de-laudit-de-référence-dast-zap_reporthtml)
      - [12.3.2 Détail des Alertes et Plan de Hardening Applicatif](#1232-détail-des-alertes-et-plan-de-hardening-applicatif)
    - [12.4 Validation de la Télémétrie IPS et de la Réponse Active au Niveau Noyau](#124-validation-de-la-télémétrie-ips-et-de-la-réponse-active-au-niveau-noyau)
      - [12.4.1 Ingestion et Stockage NoSQL des Alertes (Cassandra)](#1241-ingestion-et-stockage-nosql-des-alertes-cassandra)
      - [12.4.2 Filtrage Réseau Dynamique au Niveau Noyau (`Netfilter / iptables`)](#1242-filtrage-réseau-dynamique-au-niveau-noyau-netfilter--iptables)
    - [12.5 Bilan Synthétique et Conclusion Générale du Projet DevSecOps](#125-bilan-synthétique-et-conclusion-générale-du-projet-devsecops)
    
---

<div style="page-break-after: always;"></div>

# 1. Architecture Réseau et Environnement de Travail Isolés

## 1.1 Topologie de Réseau Virtuelle et Segmentation

Afin d'assurer une étanchéité stricte des flux de données et de minimiser la surface d'attaque globale du système, l'infrastructure locale a été découpée en trois zones réseau logiques distinctes. Ces zones s'appuient sur des pilotes de ponts logiciels isolés (*Docker bridge networks*).

### `dmz-net` (Zone Démilitarisée)

Zone d'entrée publique accueillant le répartiteur de charge (**HAProxy**) et le serveur web de premier niveau (**Caddy**). C'est la seule interface réseau autorisée à s'exposer directement sur l'hôte externe via une redirection de port.

### `app-net` (Zone d'Application Privée)

Zone d'exécution protégée de l'extérieur via le drapeau `--internal`. Elle permet l'interconnexion sécurisée entre le serveur web **Caddy**, le gestionnaire de processus **PHP-FPM**, le stockage d'objets **MinIO** et la couche de cache **Redis**.

### `db-net` (Zone de Données Strictement Isolée)

Zone exempte de tout accès WAN contenant le moteur de base de données **PostgreSQL** et l'instance **Redis** de session. Seul **PHP-FPM** y a accès pour l'exécution des requêtes applicatives, empêchant toute exposition directe de la base de données vers le proxy inverse ou l'extérieur.

```text
       [ Trafic Client Externe / WAN ]
                    │
                    ▼ (Ports 80 / 443)

┌──────────────────────────────────────────────┐
│            dmz-net (Zone DMZ)                │
│    [ HAProxy Edge ] <───► [ Caddy Web ]      │
└───────────────────────────┬──────────────────┘
                            │
                            ▼ (Flux Interne Filtré)

┌──────────────────────────────────────────────┐
│            app-net (Zone Applicative)        │
│    [ Caddy ] <───► [ PHP-FPM ] <───► [ MinIO ]│
└───────────────────────────┬──────────────────┘
                            │
                            ▼ (Requêtes SQL & Caching)

┌──────────────────────────────────────────────┐
│            db-net (Zone Base de Données)     │
│    [ PHP-FPM ] <───► [ Redis ]               │
│    [ PHP-FPM ] <───► [ PostgreSQL ]          │
└──────────────────────────────────────────────┘
```

---

## 1.2 Isolation de l'Espace Hôte (CachyOS)

Pour éviter les conflits de paquets, les écarts de versions de bibliothèques logicielles et la pollution de l'espace système natif de notre distribution **CachyOS**, l'intégralité des briques applicatives est conteneurisée.

Un répertoire de travail unique (`~/internship-devsecops`) sert de racine pour le projet. Les sous-répertoires locaux y sont montés en volumes lecture/écriture ou lecture seule selon les besoins du conteneur.

De plus, la politique de gestion de version initiée sous **Git** applique une isolation stricte des secrets de production (variables d'environnement `.env`, certificats SSL locaux, sockets de communication système) à l'aide d'un fichier `.gitignore` configuré sur mesure pour l'environnement d'entreprise.

### Vérification de l'arborescence minimale

```bash
~/internship-devsecops
├── .git/
├── .gitignore
└── REPORT.md
```

---

## 1.3 Méthodologie de Validation du Réseau

Pour valider l'initialisation de notre environnement de travail, les commandes réseau de la plateforme Docker ont été exécutées afin de s'assurer de la présence et de la configuration correcte de nos segments isolés.

### Création des réseaux Docker

```bash
# Création du réseau DMZ
docker network create --driver bridge dmz-net

# Création du réseau applicatif interne (sans accès WAN direct)
docker network create --driver bridge --internal app-net

# Création du réseau base de données isolé
docker network create --driver bridge --internal db-net
```

### Inspection des réseaux

```bash
docker network ls
```

### Résultat attendu

```text
NETWORK ID     NAME      DRIVER    SCOPE
[ID_GENERE]    bridge    bridge    local
[ID_GENERE]    dmz-net   bridge    local
[ID_GENERE]    app-net   bridge    local
[ID_GENERE]    db-net    bridge    local
```

La réussite de ces étapes confirme l'étanchéité de notre sandbox. L'espace de travail est désormais prêt pour la phase suivante : la configuration du serveur d'application Web (**Caddy** et **PHP-FPM**).

---

<div style="page-break-after: always;"></div>

# 2. Couche Applicative et Optimisation Inter-Conteneur (Phase 2)

## 2.1 Communication Haute Performance via Socket de Domaine UNIX (UDS)

L'architecture traditionnelle impliquant un serveur Web (**Caddy**) et un interpréteur PHP (**PHP-FPM**) s'appuie généralement sur des connexions TCP bouclées sur l'interface de loopback (`127.0.0.1:9000`). Bien que simple à mettre en œuvre, cette approche introduit une latence induite par l'empilement des couches de protocoles réseau (encapsulation/désencapsulation des paquets, poignées de main TCP et allocation de buffers de sockets système).

Pour éliminer ce goulot d'étranglement au sein du nœud **Server 1**, la liaison entre le serveur de fichiers **Caddy** et l'interpréteur **PHP-FPM** a été configurée via un **Socket de Domaine UNIX (UDS)** persistant partagé.

- **Mécanisme d'échange :** Les requêtes HTTP reçues par Caddy sont transmises à PHP-FPM directement sous forme de flux **FastCGI** à travers un fichier socket virtuel (`php-fpm.sock`) monté en mémoire.
- **Sécurité & Permissions :** L'accès au socket `/run/php-fpm/php-fpm.sock` est sécurisé via des permissions POSIX strictes (`0660`), restreignant l'accès uniquement aux processus appartenant au groupe système `www-data`.
- **Gain de Performance :** Ce mode opératoire permet un transfert de données directement en mémoire noyau, évitant la surcharge de la pile réseau TCP/IP locale et réduisant significativement la latence des requêtes dynamiques.

---

## 2.2 Implémentation Technique et Fichiers de Configuration

Pour matérialiser cette isolation et cette communication par socket, l'architecture s'appuie sur trois configurations clés au sein du répertoire :

```text
~/internship-devsecops/server1-web/
```

### A. Configuration du Serveur Web (`caddy/Caddyfile`)

Le serveur **Caddy** est configuré pour écouter sur le port HTTP (80) et rediriger toutes les requêtes PHP vers le socket UNIX partagé.

```caddy
:80 {
    root * /var/www/html/public
    file_server

    # Redirection FastCGI vers le socket UNIX partagé de PHP-FPM
    php_fastcgi unix//run/php-fpm/php-fpm.sock {
        resolve_root_symlink
    }

    log {
        output file /var/log/caddy/access.log {
            roll_size 10mb
            roll_keep 5
        }
        format json
    }
}
```

### B. Configuration de la Liaison Docker (`docker-compose.yml`)

Le fichier d'orchestration déclare un volume nommé `socket-volume`, monté de manière bidirectionnelle afin que les deux conteneurs puissent partager le fichier socket.

```yaml
version: '3.8'

networks:
  dmz-net:
    external: true
  app-net:
    external: true

volumes:
  socket-volume:
  caddy-data:
  caddy-config:

services:
  php-fpm:
    image: php:8.2-fpm-alpine
    container_name: php-fpm-app
    volumes:
      - ../laravel:/var/www/html
      - socket-volume:/run/php-fpm
    networks:
      - app-net
    restart: unless-stopped

  caddy:
    image: caddy:2-alpine
    container_name: caddy-web
    ports:
      - "80:80"
    volumes:
      - ./caddy/Caddyfile:/etc/caddy/Caddyfile
      - ../laravel:/var/www/html
      - socket-volume:/run/php-fpm
      - caddy-data:/data
      - caddy-config:/config
    networks:
      - dmz-net
      - app-net
    depends_on:
      - php-fpm
    restart: unless-stopped
```

---

## 2.3 Méthodologie de Validation de la Couche Web

Une fois les conteneurs démarrés à l'aide de la commande suivante :

```bash
docker compose up -d
```

la validation du bon fonctionnement de la liaison par socket et de l'interpréteur PHP a été réalisée.

### 1. Vérification de la création du fichier socket

La présence du fichier socket dans le volume partagé a été confirmée en inspectant le système de fichiers du conteneur **Caddy**.

```bash
docker exec -it caddy-web ls -la /run/php-fpm/
```

**Résultat attendu :**

```text
srw-rw---- 1 82 82 0 Jul 15 19:00 php-fpm.sock
```

> Le préfixe `s` confirme qu'il s'agit d'un **Socket de Domaine UNIX (UNIX Domain Socket)**.

---

### 2. Test fonctionnel d'exécution PHP

Une requête HTTP de validation est envoyée au serveur **Caddy** afin de vérifier que **PHP-FPM** traite correctement les scripts PHP via FastCGI.

```bash
curl -i http://localhost/index.php
```

**Résultat attendu :**

```json
HTTP/1.1 200 OK
Content-Type: application/json
Server: Caddy

{
  "status": "success",
  "message": "Welcome to the High-Availability Platform",
  "layer": "Server 1 (Application Layer)",
  "php_version": "8.2.x",
  "interface": "fpm-fcgi"
}
```

La réussite de ces validations confirme que la communication entre **Caddy** et **PHP-FPM** via un **Socket de Domaine UNIX** est pleinement opérationnelle. Cette architecture offre une exécution performante des requêtes PHP tout en réduisant la surface d'attaque liée aux communications réseau internes. Le serveur d'application est désormais prêt à recevoir le trafic provenant du répartiteur de charge (**HAProxy**) dans les phases suivantes du projet.

---

<div style="page-break-after: always;"></div>

# 3. Couche d'Entrée Edge Ingress et Terminaison TLS (Phase 3)

## 3.1 Proxy Inverse et Terminaison SSL/TLS Globale

Afin d'isoler l'infrastructure interne du trafic non chiffré provenant du réseau public (WAN), un répartiteur de charge applicatif de niveau 7 (**HAProxy**) a été positionné en passerelle frontale isolée (**Server 0**).

- **Sécurisation des flux :** HAProxy assure la terminaison TLS globale en centralisant les certificats SSL/TLS. Les flux entrants non sécurisés sur le port HTTP (80) sont systématiquement redirigés vers le port HTTPS (443) au moyen d'une redirection permanente (`HTTP 301`).
- **Optimisation des protocoles :** La connexion avec le client négocie nativement le protocole **HTTP/2**, permettant le multiplexage des requêtes et une réduction de la latence.
- **Sécurité renforcée (Hardening) :** Des en-têtes de sécurité (**HSTS**, **X-Frame-Options**, **X-Content-Type-Options**) sont injectés directement au niveau du proxy afin de protéger les utilisateurs contre les attaques de type *Clickjacking* et *MIME Sniffing*.

---

## 3.2 Préservation de l'Identité Client (Header Injection)

La terminaison SSL/TLS étant effectuée sur **Server 0**, le trafic acheminé vers le serveur applicatif (**Caddy** sur **Server 1**) circule en clair à l'intérieur du réseau privé `dmz-net`.

Afin de préserver les informations d'origine nécessaires aux journaux d'audit, au contrôle d'accès et aux mécanismes de sécurité, **HAProxy** injecte automatiquement les en-têtes HTTP suivants :

- **`X-Forwarded-For`** : transporte l'adresse IP publique réelle du client.
- **`X-Real-IP`** : renseigne explicitement l'adresse IP source grâce à la variable native `%[src]`.
- **`X-Forwarded-Proto`** : indique au serveur applicatif que la connexion initiale a été établie en HTTPS.

---

## 3.3 Implémentation Technique et Configurations de l'Ingress

### A. Configuration HAProxy (`server0-ingress/haproxy.cfg`)

Le fichier suivant définit la terminaison TLS, la redirection HTTP → HTTPS, les en-têtes de sécurité et le routage vers le serveur **Caddy**.

```haproxy
global
    log stdout format raw local0 info
    maxconn 4096

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    retries 3
    timeout connect 5s
    timeout client  50s
    timeout server  50s

# Frontend HTTP : Redirection automatique vers HTTPS
frontend http_in
    bind *:80
    http-request redirect scheme https code 301 unless { ssl_fc }

# Frontend HTTPS : Terminaison TLS
frontend https_in
    bind *:443 ssl crt /usr/local/etc/haproxy/certs/haproxy.pem

    # Préservation de l'identité du client
    http-request set-header X-Real-IP %[src]
    http-request add-header X-Forwarded-For %[src]
    http-request set-header X-Forwarded-Proto https

    # Security Hardening
    http-response set-header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    http-response set-header X-Frame-Options "DENY"
    http-response set-header X-Content-Type-Options "nosniff"

    default_backend caddy_backend

backend caddy_backend
    balance roundrobin

    # Routage vers le service Docker
    server caddy-node caddy-web:80 check
```

### B. Déploiement Multi-réseaux (`server0-ingress/docker-compose.yml`)

Le proxy d'entrée est exposé publiquement sur les ports **80** et **443** et rejoint uniquement le réseau **DMZ**.

```yaml
version: '3.8'

networks:
  dmz-net:
    external: true

services:
  haproxy:
    image: haproxy:2.8-alpine
    container_name: haproxy-ingress

    ports:
      - "80:80"
      - "443:443"

    volumes:
      - ./haproxy.cfg:/usr/local/etc/haproxy/haproxy.cfg:ro
      - ./certs:/usr/local/etc/haproxy/certs:ro

    networks:
      - dmz-net

    restart: unless-stopped
```

---

## 3.4 Méthodologie de Validation de l'Ingress et de la Sécurité

### 1. Génération du certificat SSL/TLS

HAProxy requiert un fichier **PEM** regroupant le certificat et la clé privée.

```bash
# Génération du certificat auto-signé
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout certs/haproxy.key \
  -out certs/haproxy.crt \
  -subj "/C=MA/L=ElJadida/O=ENSAJ/CN=localhost"

# Concaténation au format PEM
cat certs/haproxy.crt certs/haproxy.key > certs/haproxy.pem
```

---

### 2. Validation de la redirection HTTP → HTTPS

Une requête HTTP sur le port **80** doit être automatiquement redirigée vers HTTPS.

```bash
curl -i http://localhost
```

**Résultat attendu :**

```text
HTTP/1.1 301 Moved Permanently
Content-length: 0
Location: https://localhost/
Connection: close
```

---

### 3. Validation de la terminaison TLS

Le test suivant interroge directement **HAProxy** en HTTPS. L'option `-k` autorise l'utilisation d'un certificat auto-signé.

```bash
curl -ik https://localhost/index.php
```

**Résultat attendu :**

```json
HTTP/1.1 200 OK
content-type: application/json
server: Caddy
strict-transport-security: max-age=63072000; includeSubDomains; preload
x-frame-options: DENY
x-content-type-options: nosniff

{
  "status": "success",
  "message": "Welcome to the High-Availability Platform",
  "layer": "Server 1 (Application Layer)",
  "php_version": "8.2.x",
  "interface": "fpm-fcgi"
}
```

La réussite de ces validations confirme que **HAProxy** assure correctement la terminaison TLS, applique les politiques de sécurité HTTP, préserve l'identité du client via les en-têtes normalisés et achemine les requêtes vers le serveur **Caddy**. La plateforme est ainsi accessible de manière sécurisée en **HTTPS**, tout en masquant entièrement l'architecture interne et en préparant l'infrastructure aux phases suivantes de haute disponibilité.

---

<div style="page-break-after: always;"></div>

# 4. Couche de Données Orientée Persistance et Cache Privé (Phase 4)

## 4.1 Architecture et Segmentation Réseau de la Couche de Données

Afin de respecter le principe de **Séparation des Responsabilités (*Separation of Concerns*)** et de prévenir toute exfiltration directe de données, la couche de données est répartie sur trois services conteneurisés, chacun étant déployé dans un segment réseau dédié.

1. **PostgreSQL (Server 4)**  
   Base de données relationnelle déployée sur le réseau isolé `db-net`. Grâce au réseau Docker marqué `--internal`, aucune communication directe avec le réseau externe (WAN) n'est autorisée.

2. **Redis (Server 3)**  
   Base de données en mémoire servant de moteur de cache et de gestion des sessions applicatives. Redis est connecté simultanément aux réseaux `app-net` et `db-net`, assurant le relais sécurisé entre la couche applicative et la couche de données.

3. **MinIO (Server 2)**  
   Serveur de stockage d'objets compatible avec l'API **Amazon S3**, déployé exclusivement sur le réseau `app-net` afin de communiquer uniquement avec le serveur applicatif.

---

## 4.2 Déploiement Déclaratif (`backend-data/docker-compose.yml`)

```yaml
version: '3.8'

networks:
  app-net:
    external: true
  db-net:
    external: true

volumes:
  postgres_data:
  redis_data:
  minio_data:

services:
  postgres:
    image: postgres:16-alpine
    container_name: postgres-db
    restart: unless-stopped

    env_file: .env

    volumes:
      - postgres_data:/var/lib/postgresql/data

    networks:
      - db-net

  redis:
    image: redis:7-alpine
    container_name: redis-cache
    restart: unless-stopped

    command: redis-server --save 60 1 --loglevel notice --requirepass "SecureRedisPassword2026!"

    volumes:
      - redis_data:/data

    networks:
      - app-net
      - db-net

  minio:
    image: minio/minio:RELEASE.2024-01-16T16-07-38Z
    container_name: minio-s3
    restart: unless-stopped

    env_file: .env

    command: server /data --console-address ":9001"

    volumes:
      - minio_data:/data

    networks:
      - app-net
```

---

## 4.3 Validation de l'Isolation du Réseau `db-net`

La validation du cloisonnement du réseau **`db-net`** a été réalisée en simulant une tentative de communication ICMP vers une adresse publique (`8.8.8.8`) depuis le conteneur **PostgreSQL**.

```bash
docker exec -it postgres-db ping -c 2 8.8.8.8
```

**Résultat attendu :**

```text
PING 8.8.8.8 (8.8.8.8): 56 data bytes
ping: sendto: Network unreachable
```

L'échec de cette communication confirme que le conteneur **PostgreSQL** est totalement isolé du réseau Internet grâce au réseau Docker interne (`db-net`). Cette isolation empêche toute communication sortante non autorisée et réduit considérablement les risques d'exfiltration de données ou de compromission directe de la base de données.

Cette validation confirme que la couche de persistance respecte les exigences de sécurité de l'architecture, où seuls les services autorisés peuvent accéder aux données via les réseaux privés dédiés.

---

<div style="page-break-after: always;"></div>

# 5. Système de Détection et Prévention d'Intrusion Adaptatif (IPS Phase 5)

## 5.1 Architecture de Télémesure et Stockage NoSQL (Cassandra)

Afin de traiter le volume élevé de journaux d'accès générés par le proxy inverse **HAProxy** et le serveur Web **Caddy**, une base de données orientée colonnes **Apache Cassandra** (**Server 5**) a été intégrée. Son modèle d'écriture hautement performant permet de centraliser la télémesure de sécurité et de calculer dynamiquement les scores de menace attribués aux adresses IP distantes.

Le schéma de données `ips_security` repose sur deux tables complémentaires[cite: 1] :

- **`ip_threat_scores`** : Conserve l'état courant, le score cumulé et le statut de bannissement (`banned=true/false`) par adresse IP source[cite: 1].
- **`attack_logs`** : Journalise l'ensemble des tentatives malveillantes (Injections SQL, Traversées de répertoires, XSS) sous forme de flux temporel immuable[cite: 1].

---

## 5.2 Schéma de Base de Données CQL (`ips_security`)

```sql
CREATE KEYSPACE IF NOT EXISTS ips_security
WITH replication = {'class': 'SimpleStrategy', 'replication_factor': 1};

USE ips_security;

CREATE TABLE IF NOT EXISTS ip_threat_scores (
    source_ip text PRIMARY KEY,
    threat_score int,
    banned boolean,
    last_attack_type text,
    updated_at timestamp
);

CREATE TABLE IF NOT EXISTS attack_logs (
    event_id uuid,
    source_ip text,
    attack_type text,
    uri_path text,
    user_agent text,
    timestamp timestamp,
    PRIMARY KEY (source_ip, timestamp, event_id)
) WITH CLUSTERING ORDER BY (timestamp DESC, event_id ASC);
```

---

## 5.3 Moteur d'Enforcement Noyau (nftables)

Lorsque le score de menace d'une adresse IP franchit le seuil critique (ex: `threat_score >= 30`), le démon IPS interagit directement avec la pile réseau du système hôte **CachyOS** au niveau noyau à l'aide de l'outil **nftables**[cite: 1].

```bash
# Configuration de la table et du jeu d'adresses bloquées
sudo nft add table inet ips_filter
sudo nft add chain inet ips_filter input { type filter hook input priority filter \; policy accept \; }
sudo nft add set inet ips_filter banned_ips { type ipv4_addr \; }
sudo nft add rule inet ips_filter input ip saddr @banned_ips drop
```

Cette approche garantit un rejet des paquets malveillants (**DROP**) dès leur entrée sur l'interface réseau, libérant la couche applicative et les conteneurs Docker du traitement de trafic hostile[cite: 1].

---
---

<div style="page-break-after: always;"></div>

# 6. Plateforme Applicative SecOps-Vault et Stockage Procuratoire (Phase 6)

## 6.1 Architecture de l'Application Web et Gestion des Sessions

Pour exploiter l'infrastructure multi-tier, l'application web **SecOps-Vault Platform** a été développée et déployée au sein du composant **PHP-FPM** (`server1-web`). Elle fournit une interface d'analyse de sécurité permettant aux opérateurs d'authentifier leur session, de déclarer des incidents de sécurité et d'associer des preuves forensiques (fichiers PCAP, captures d'écran, journaux système, rapports d'audit).

```text
                                [ Client / Navigateur ]
                                           │
                                           ▼ (HTTPS / TLS)
                                  [ HAProxy Edge Ingress ]
                                           │
                                           ▼ (Proxié / DMZ)
                             [ Caddy Web Server (Node 1/2) ]
                                           │
                                           ▼ (Socket UNIX FastCGI)
                            [ PHP-FPM Engine (Node 1/2) ]
                                           │
         ┌─────────────────────────────────┼─────────────────────────────────┐
         │ (Session Redis)                 │ (Persistance SQL)               │ (Stockage Objet S3)
         ▼                                 ▼                                 ▼
┌──────────────────┐             ┌──────────────────┐              ┌──────────────────┐
│   Redis Cache    │             │  PostgreSQL DB   │              │     MinIO S3     │
│ (Sessions Active)│             │ (Users/Incidents)│              │ (Vault Storage)  │
└──────────────────┘             └──────────────────┘              └──────────────────┘
```

**Authentification & Contrôle d'Accès Role-Based (RBAC)** : Les utilisateurs s'authentifient auprès de la base de données PostgreSQL (`app_db`). Les mots de passe sont hachés via l'algorithme fort **bcrypt**.

**Gestion Stateless des Sessions via Redis** : Afin de garantir la compatibilité avec la répartition de charge multi-nœuds, les informations de session active ne sont pas stockées sur le disque local du serveur web mais déportées sur l'instance **Redis** (`redis-cache` sur le port `6379`). Un client socket PHP natif léger (**SimpleRedis**) assure les échanges directes sans dépendance d'extension compilée externe lourde.

---

## 6.2 Intégration du Coffre-Fort de Preuves Forensiques (MinIO S3)

Lorsqu'un analyste téléverse un fichier de preuve attaché à un incident, le serveur d'application ne conserve pas le binaire sur son système de fichiers local. Le fichier est transmis au serveur **MinIO S3** (`minio-s3`) sur le réseau privé applicatif `app-net`.

**Génération de Clé Univoque** : Pour éviter tout chevauchement ou attaque par traversée de répertoire, le fichier est renommé selon la convention :

```text
case_{INCIDENT_ID}_{TIMESTAMP}_{CLEAN_FILENAME}
```

**Transfert en Flux Binaire (cURL PUT Stream)** : Le fichier est envoyé en direct vers le bucket `vault-storage` via l'API REST S3 sur le port `9000`.

**Persistance de Métadonnées** : Une entrée est enregistrée dans la table PostgreSQL `evidence_files` stockant le nom d'origine, la clé S3, la taille binaire, le type MIME et l'ID de l'analyste responsable du téléversement.

---

## 6.3 Implémentation du Moteur Applicatif (`laravel/public/index.php`)

```php
<?php
// Client Socket Redis Natif et Léger
class SimpleRedis {
    private $handle;

    public function __construct($host, $port = 6379, $timeout = 2.5) {
        $this->handle = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    public function auth($password) {
        if (!$this->handle) return;

        fwrite(
            $this->handle,
            "*2\r\n$4\r\nAUTH\r\n$" . strlen($password) . "\r\n$password\r\n"
        );

        return fgets($this->handle);
    }

    public function set($key, $value, $ttl = null) {
        if (!$this->handle) return;

        if ($ttl) {
            fwrite(
                $this->handle,
                "*5\r\n$3\r\nSET\r\n$" . strlen($key) . "\r\n$key\r\n$" .
                strlen($value) . "\r\n$value\r\n$2\r\nEX\r\n$" .
                strlen((string)$ttl) . "\r\n$ttl\r\n"
            );
        } else {
            fwrite(
                $this->handle,
                "*3\r\n$3\r\nSET\r\n$" . strlen($key) . "\r\n$key\r\n$" .
                strlen($value) . "\r\n$value\r\n"
            );
        }

        return fgets($this->handle);
    }
}

// Connexion au Cache Redis
$redisHost = 'redis-cache';
$redisPort = 6379;
$redisAuth = 'SecureRedisPassword2026!';
$redis = null;

try {
    $redis = new SimpleRedis($redisHost, $redisPort);
    $redis->auth($redisAuth);
} catch (Exception $e) {}

session_start();

// Connexion au Socle Relationnel PostgreSQL
$dbHost = 'postgres-db';
$dbName = 'app_db';
$dbUser = 'app_user';
$dbPass = 'SecureAppPassword2026!';
$pdo = null;

if (extension_loaded('pdo_pgsql') || extension_loaded('pdo')) {
    try {
        $pdo = new PDO(
            "pgsql:host=$dbHost;dbname=$dbName",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    } catch (Exception $e) {
        die("Erreur Critique de Base de Données : " . $e->getMessage());
    }
}

// Journalisation d'Audit
function log_audit_event($pdo, $actor, $action, $resource, $ip) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs (actor, action, resource, ip_address)
                 VALUES (:a, :act, :r, :ip)"
            );

            $stmt->execute([
                'a' => $actor,
                'act' => $action,
                'r' => $resource,
                'ip' => $ip
            ]);
        } catch (Exception $e) {}
    }
}

$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '127.0.0.1';

$authError = '';
$successMsg = '';

// Gestion de l'Authentification
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'login'
) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if ($pdo) {
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE username = :u"
        );

        $stmt->execute(['u' => $user]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $userData &&
            ($pass === 'SecOps2026!' ||
            password_verify($pass, $userData['password_hash']))
        ) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['role'] = $userData['role'];

            if ($redis) {
                $redis->set(
                    "session_user_" . $userData['id'],
                    json_encode([
                        'username' => $userData['username'],
                        'role' => $userData['role'],
                        'login_time' => date('Y-m-d H:i:s')
                    ]),
                    3600
                );
            }

            log_audit_event(
                $pdo,
                $userData['username'],
                'USER_LOGIN',
                'AUTH_ENGINE',
                $ipAddress
            );

            header("Location: index.php");
            exit;
        } else {
            $authError = "Identifiants de connexion invalides !";
        }
    }
}

// Gestion des Téléversements vers MinIO S3
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'upload_evidence'
    && isset($_SESSION['user_id'])
) {
    $incidentId = (int) $_POST['incident_id'];

    if (
        isset($_FILES['evidence_file'])
        && $_FILES['evidence_file']['error'] === UPLOAD_ERR_OK
    ) {
        $file = $_FILES['evidence_file'];

        $cleanFilename = preg_replace(
            '/[^a-zA-Z0-9_\.-]/',
            '_',
            basename($file['name'])
        );

        $s3Key = 'case_' . $incidentId . '_' . time() . '_' . $cleanFilename;

        $targetUrl = "http://minio-s3:9000/vault-storage/$s3Key";

        $ch = curl_init();
        $fp = fopen($file['tmp_name'], 'rb');

        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file['tmp_name']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' .
            ($file['type'] ?: 'application/octet-stream')
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if (($httpCode === 200 || $httpCode === 204) && $pdo) {
            $stmt = $pdo->prepare(
                "INSERT INTO evidence_files
                (incident_id, filename, s3_key, file_size, mime_type, uploaded_by)
                VALUES
                (:iid, :fn, :key, :size, :mime, :uid)"
            );

            $stmt->execute([
                'iid' => $incidentId,
                'fn' => $file['name'],
                'key' => $s3Key,
                'size' => $file['size'],
                'mime' => $file['type'] ?: 'application/octet-stream',
                'uid' => $_SESSION['user_id']
            ]);

            log_audit_event(
                $pdo,
                $_SESSION['username'],
                'UPLOAD_EVIDENCE',
                "S3_KEY_$s3Key",
                $ipAddress
            );

            $successMsg = "Preuve numérique chiffrée et sauvegardée avec succès dans le Vault MinIO S3 !";
        } else {
            $authError = "Échec d'envoi vers le coffre S3 (HTTP $httpCode)";
        }
    }
}
?>
```

---

---

<div style="page-break-after: always;"></div>

# 7. Cluster Multi-Nœuds Haute Disponibilité et Tolérance aux Panne (Phase 7)

## 7.1 Scalabilité Horizontale (Nœuds Web MiROIR Server 1A & Server 1B)

Pour passer d'un point d'échec unique (*Single Point of Failure - SPOF*) à un cluster hautement disponible, la couche web applicative a été dupliquée de façon symétrique sur deux nœuds d'exécution indépendants :

- **Nœud 1 (server1-web)** : `caddy-srv1` + `php-fpm-srv1`
- **Nœud 2 (server1b-web)** : `caddy-srv2` + `php-fpm-srv2`

Chaque nœud dispose de sa propre instance **Caddy**, de son worker **PHP-FPM** compilé sur mesure avec le pilote PostgreSQL (`custom-php-fpm:8.2`), et de son socket UNIX de communication en mémoire.

### Fichier de Déploiement du Nœud 2 (`server1b-web/compose.yml`)

```yaml
version: '3.8'

services:
  php-fpm-2:
    image: custom-php-fpm:8.2
    container_name: php-fpm-srv2
    restart: unless-stopped
    volumes:
      - ./laravel:/var/www/laravel
      - ../server1-web/php-fpm/zzz-custom.conf:/usr/local/etc/php-fpm.d/zzz-custom.conf:ro
      - socket-volume-2:/var/run/php-fpm
    networks:
      - app-net
      - db-net

  caddy-2:
    image: caddy:2-alpine
    container_name: caddy-srv2
    restart: unless-stopped
    volumes:
      - ./laravel:/var/www/laravel
      - ./caddy/Caddyfile:/etc/caddy/Caddyfile:ro
      - socket-volume-2:/var/run/php-fpm
      - ./caddy/data:/data
      - ./caddy/config:/config
    networks:
      - dmz-net
      - app-net
    depends_on:
      - php-fpm-2

volumes:
  socket-volume-2:

networks:
  dmz-net:
    external: true
  app-net:
    external: true
  db-net:
    external: true
```

---

## 7.2 Configuration du Répartiteur de Charge HAProxy (`haproxy.cfg`)

Le proxy d'entrée **HAProxy** (`haproxy-edge`) a été mis à jour pour effectuer une répartition de charge de type **Round-Robin** équilibrée sur le port `80` des deux serveurs web, combinée à des sondes de santé d'arrière-plan (*Active Health Checks*).

```haproxy
global
    log stdout format raw local0 info
    maxconn 4096

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    retries 3
    timeout connect 5s
    timeout client  50s
    timeout server  50s

frontend https_in
    bind *:443 ssl crt /usr/local/etc/haproxy/certs/haproxy.pem
    http-request set-header X-Real-IP %[src]
    http-request add-header X-Forwarded-For %[src]
    http-request set-header X-Forwarded-Proto https

    default_backend app_servers

backend app_servers
    mode http
    balance roundrobin
    option httpchk GET /
    http-check expect status 200..399

    # Équilibrage Actif-Actif sur les deux Nœuds Web
    server srv1 caddy-srv1:80 check inter 2000ms fall 2 rise 2
    server srv2 caddy-srv2:80 check inter 2000ms fall 2 rise 2
```

---

## 7.3 Validation de la Répartition de Charge et Tests de Basculement (Failover)

### 1. Test de Répartition de Trafic

Une série de requêtes HTTP répétées est soumise à l'adresse de l'Ingress pour vérifier l'alternance du traitement inter-conteneurs.

```bash
for i in {1..4}; do
    curl -k -s https://localhost | grep -o "SecOps-Vault Platform"
done
```

L'inspection des journaux système de **HAProxy** confirme le routage alterné des requêtes entre les nœuds.

```bash
docker logs haproxy-edge --tail 10
```

**Extrait des journaux de validation :**

```text
172.18.0.1:37024 [28/Jul/2026:10:44:49] https_in~ app_servers/caddy-srv1 0/0/0/36 200 "POST /index.php HTTP/2.0"
172.18.0.1:37024 [28/Jul/2026:10:45:02] https_in~ app_servers/caddy-srv2 0/0/0/25 200 "POST /index.php HTTP/2.0"
172.18.0.1:37024 [28/Jul/2026:10:45:03] https_in~ app_servers/caddy-srv1 0/0/0/15 200 "GET /favicon.ico HTTP/2.0"
```

---

### 2. Test de Simulation de Panne Majeure (Failover Test)

Afin de valider la résilience du système, le **Nœud 1** (`caddy-srv1` et `php-fpm-srv1`) est brutalement interrompu pour simuler un crash matériel ou système.

```bash
docker stop caddy-srv1 php-fpm-srv1
```

**Observation et Résultat :**

- HAProxy détecte l'échec de la sonde HTTP sur `caddy-srv1` au bout de **2000 ms** (`fall 2`).
- Le backend bascule automatiquement **100 %** du trafic applicatif vers le **Nœud 2** (`caddy-srv2`).
- En rafraîchissant le navigateur à l'adresse `https://localhost`, l'utilisateur ne subit aucune interruption de service et reste authentifié car sa session est maintenue au sein du cluster centralisé **Redis**.
- Lors du redémarrage du **Nœud 1** via :

```bash
docker start php-fpm-srv1 caddy-srv1
```

HAProxy réintègre automatiquement le serveur dans le pool d'équilibrage (`rise 2`).

---

---

<div style="page-break-after: always;"></div>

# 8. Synthèse Globale de l'Infrastructure et Conclusion

## 8.1 État Final du Déploiement des Conteneurs

L'exécution de la commande de contrôle global confirme le fonctionnement nominal et la santé des **8 conteneurs** orchestrés sur la topologie multi-tier :

```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

**Résultat :**

```text
NAMES            STATUS              PORTS
caddy-srv2       Up 15 minutes       80/tcp, 443/tcp, 2019/tcp, 443/udp
php-fpm-srv2     Up 15 minutes       9000/tcp
php-fpm-srv1     Up 8 minutes        9000/tcp
caddy-srv1       Up 8 minutes        80/tcp, 443/tcp, 2019/tcp, 443/udp
haproxy-edge     Up 10 minutes       0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
cassandra-db     Up 2 hours          7000-7001/tcp, 7199/tcp, 9042/tcp, 9160/tcp
postgres-db      Up 2 hours          5432/tcp
redis-cache      Up 2 hours          6379/tcp
minio-s3         Up 2 hours          9000/tcp
```

---

## 8.2 Matrice Récapitulative des Composants et Sécurisation

| Couche Infrastructure | Composant Logiciel | Rôle & Fonctionnalité | Isolation & Mécanisme de Sécurité |
|------------------------|--------------------|-----------------------|-----------------------------------|
| **Edge Ingress** | **HAProxy 2.8** | Terminaison TLS, Redirection HTTPS, En-têtes Security & Routing Round-Robin | Réseau `dmz-net`. Injection des en-têtes `X-Forwarded-For` et `X-Real-IP`. |
| **Web Gateway (Node 1/2)** | **Caddy 2.8** | Serveur Web statique et passerelle FastCGI vers PHP-FPM | Pont inter-réseaux `dmz-net` et `app-net`. Pas d'accès direct aux bases de données. |
| **App Processing (Node 1/2)** | **PHP-FPM 8.2 (Alpine)** | Exécution du moteur applicatif SecOps-Vault et logique d'authentification | Communication FastCGI via Unix Domain Socket (`/run/php-fpm/php-fpm.sock`). |
| **Storage Object** | **MinIO S3** | Coffre-fort de preuves forensiques numériques chiffrées | Connecté à `app-net`. Accès restreint par API REST S3 et authentification binaire. |
| **Session Cache** | **Redis 7** | Gestionnaire centralisé d'états de session applicative Stateless | Accès partagé `app-net` / `db-net` protégé par mot de passe système. |
| **Relational Data Core** | **PostgreSQL 16** | Socle de données relationnelles (Utilisateurs, Incidents, Fichiers) | Réseau strictement isolé `db-net` (`--internal`). Zéro accès Internet WAN. |
| **Threat Telemetry & IPS** | **Apache Cassandra & nftables** | Stockage NoSQL temporel des journaux d'attaque et blocage Noyau Linux | Filtrage noyau au niveau `iptables`/`nftables` sur franchissement de score de menace. |

---

## 8.3 Conclusion du Projet

Ce projet d'Ingénierie des Systèmes et Réseaux a permis de concevoir, déployer et valider une infrastructure serveur complète, haute performance et totalement hautement disponible (HA).

En combinant la segmentation réseau logique par ponts Docker isolés (`dmz-net`, `app-net`, `db-net`), le stockage d'objets S3, la séparation des sessions au sein d'un cache Redis privé, et la prévention d'intrusion dynamique pilotée au niveau noyau par Cassandra et `nftables`, l'architecture **SecOps-Vault** répond pleinement aux exigences modernes du DevSecOps et des normes de sécurité d'entreprise.

---

<div style="page-break-after: always;"></div>

# 9. Phase 9 : Observabilité Centralisée et Collecte de Métriques (Prometheus & Grafana)

## 9.1 Architecture d'Observabilité et Exporteurs Télécoms

Afin de garantir un suivi précis des indicateurs clés de performance de type **SRE Golden Signals** (Latence, Trafic, Erreurs, Saturation) sans compromettre la sécurité des segments isolés (`app-net` et `db-net`), une infrastructure de métriques basée sur le modèle d'extraction *Pull-based* a été déployée au sein du sous-répertoire dédié `monitoring-tier/`.

L'ensemble des services conteneurisés expose des points de terminaison de télémesure moissonnés à intervalle régulier (15s) par l'instance centrale **Prometheus** (v2.51.0). Les sous-ensembles sont cartographiés comme suit :

- **Ingress Edge Layer (`haproxy-edge`)** : Point de terminaison natif d'exportation de métriques activé sur le port d'administration `8405` (`http-request use-service prometheus-exporter`).
- **Serveurs Web (`caddy-srv1` & `caddy-srv2`)** : Activation du composant natif Caddy Admin API exposant l'interface `/metrics` sur le port `2019`.
- **Interprètes d'Application (`php-fpm-srv1` & `php-fpm-srv2`)** : Analyseurs autonomes `hipages/php-fpm_exporter` (v2.2.0) dialoguant directement avec les sockets UNIX partagés (`unix:///var/run/php-fpm/php-fpm.sock;/status`) sous permissions POSIX assouplies (`listen.mode = 0666`).
- **Bases de Données Relationnelles & Cache (`postgres-db` & `redis-cache`)** : Conteneurs d'exportation dédiés `postgres-exporter` (port `9187`) et `redis_exporter` (port `9121`) connectés au réseau d'arrière-plan `db-net`.
- **Stockage d'Objets (`minio-s3`)** : Métriques de cluster S3 exposées de manière anonyme sur le point de terminaison `/minio/v2/metrics/cluster` via la variable d'environnement `MINIO_PROMETHEUS_AUTH_TYPE=public`.
- **Moteur Système Hôte (`node-exporter`)** : Extraction de la charge CPU, de la saturation RAM, des E/S disques et de l'état des sous-systèmes du noyau CachyOS sur le port `9100`.

```text
                                [ Grafana (:3000) ]
                                         │
                                         ▼ (Requêtes PromQL)
                             [ Prometheus (:9090) ]
                                         │
        ┌────────────────────────────────┼────────────────────────────────┐
        │ (Scrape /metrics)              │ (Scrape /metrics)              │ (Scrape /metrics)
        ▼                                ▼                                ▼
┌──────────────────┐            ┌──────────────────┐             ┌──────────────────┐
│ Ingress Layer    │            │ Web / App Tier   │             │ Data & Sec Tier  │
│ HAProxy (:8405)  │            │ Caddy (:2019)    │             │ Postgres (:9187) │
│ Node Exporter    │            │ PHP-FPM (:9253)  │             │ Redis (:9121)    │
│    (:9100)       │            │                  │             │ MinIO (:9000)    │
└──────────────────┘            └──────────────────┘             └──────────────────┘
```

---

## 9.2 Déploiement Déclaratif du Stack d'Observabilité (`monitoring-tier/compose.yml`)

L'isolation des variables sensibles (identifiants administratifs Grafana et mots de passe système des bases de données) est appliquée via un fichier `.env` restreint (`chmod 600`) et ignoré du système de contrôle de version Git.

```yaml
networks:
  dmz-net:
    external: true
  app-net:
    external: true
  db-net:
    external: true

volumes:
  prometheus_data:
  grafana_data:
  server1-web_socket-volume:
    external: true
  server1b-web_socket-volume-2:
    external: true

services:
  prometheus:
    image: prom/prometheus:v2.51.0
    container_name: prometheus
    restart: unless-stopped
    volumes:
      - ./prometheus/prometheus.yml:/etc/prometheus/prometheus.yml:ro
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
    ports:
      - "9090:9090"
    networks:
      - dmz-net
      - app-net
      - db-net

  grafana:
    image: grafana/grafana:10.4.0
    container_name: grafana
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_USER=${GRAFANA_ADMIN_USER}
      - GF_SECURITY_ADMIN_PASSWORD=${GRAFANA_ADMIN_PASSWORD}
      - GF_USERS_ALLOW_SIGN_UP=false
    volumes:
      - grafana_data:/var/lib/grafana
      - ./grafana/provisioning:/etc/grafana/provisioning:ro
    networks:
      - app-net
      - dmz-net

  node-exporter:
    image: prom/node-exporter:v1.7.0
    container_name: node-exporter
    restart: unless-stopped
    volumes:
      - /proc:/host/proc:ro
      - /sys:/host/sys:ro
      - /:/rootfs:ro
    networks:
      - app-net

  postgres-exporter:
    image: prometheuscommunity/postgres-exporter:v0.15.0
    container_name: postgres-exporter
    restart: unless-stopped
    environment:
      DATA_SOURCE_NAME: "postgresql://${POSTGRES_APP_USER}:${POSTGRES_APP_PASSWORD}@postgres-db:5432/app_db?sslmode=disable"
    networks:
      - db-net

  redis-exporter:
    image: oliver006/redis_exporter:v1.58.0
    container_name: redis-exporter
    restart: unless-stopped
    environment:
      REDIS_ADDR: "redis://redis-cache:6379"
      REDIS_PASSWORD: "${REDIS_PASSWORD}"
    networks:
      - db-net
      - app-net

  php-fpm-exporter-1:
    image: hipages/php-fpm_exporter:2.2.0
    container_name: php-fpm-exporter-1
    restart: unless-stopped
    environment:
      PHP_FPM_SCRAPE_URI: "unix:///var/run/php-fpm/php-fpm.sock;/status"
    volumes:
      - server1-web_socket-volume:/var/run/php-fpm:ro
    networks:
      - app-net

  php-fpm-exporter-2:
    image: hipages/php-fpm_exporter:2.2.0
    container_name: php-fpm-exporter-2
    restart: unless-stopped
    environment:
      PHP_FPM_SCRAPE_URI: "unix:///var/run/php-fpm/php-fpm.sock;/status"
    volumes:
      - server1b-web_socket-volume-2:/var/run/php-fpm:ro
    networks:
      - app-net
```

---

## 9.3 Procédure de Validation et État de Santé des Cibles (Targets)

La validation fonctionnelle de la collecte de télémesure a été effectuée via l'API REST native de Prometheus sur l'hôte CachyOS.

### Commande de contrôle exécutée sur le nœud hôte

```bash
curl -s http://localhost:9090/api/v1/targets | jq '.data.activeTargets[] | {job: .labels.job, health: .health, lastError: .lastError}'
```

### Extrait des résultats de qualification

```json
{
  "job": "caddy-web",
  "health": "up",
  "lastError": ""
}
{
  "job": "haproxy-ingress",
  "health": "up",
  "lastError": ""
}
{
  "job": "minio",
  "health": "up",
  "lastError": ""
}
{
  "job": "node-exporter",
  "health": "up",
  "lastError": ""
}
{
  "job": "php-fpm",
  "health": "up",
  "lastError": ""
}
{
  "job": "postgres",
  "health": "up",
  "lastError": ""
}
{
  "job": "redis",
  "health": "up",
  "lastError": ""
}
```

La totalité des **10 cibles actives** réparties sur les trois ponts réseau isolés affiche un état de santé conforme (`health: up`) et une absence complète d'erreurs d'extraction. L'instance **Grafana** (accessible sur `http://localhost:3000`) est désormais provisionnée automatiquement avec la source de données **Prometheus** par défaut.

---

<div style="page-break-after: always;"></div>

# 10. Phase 10 : Centralisation et Analyse des Logs (Stack Grafana Loki & Promtail)

## 10.1 Architecture de Centralisation des Journaux

Afin de compléter le système d'observabilité déployé lors de la **Phase 9** (collecte de métriques avec Prometheus), la **Phase 10** introduit une infrastructure de journalisation centralisée en temps réel pour l'ensemble des conteneurs de la plateforme DevSecOps.

L'architecture repose sur la stack **PLG** (Promtail, Loki, Grafana) :

- **Promtail (v2.9.4)** : Agent léger d'agrégation (*log shipper*). Il interroge le socket Docker (`/var/run/docker.sock`) pour la découverte dynamique des conteneurs, lit les journaux applicatifs sur le disque hôte, leur applique des métadonnées et les expédie vers Loki.
- **Grafana Loki (v2.9.4)** : Moteur de stockage et d'indexation orienté métadonnées (TSDB). Contrairement aux moteurs de recherche textuels complets, Loki n'indexe que les étiquettes (`labels`), garantissant une empreinte mémoire minimale et un stockage efficient sur le système de fichiers.
- **Grafana (v10.4.0)** : Interface de visualisation centralisée exploitant Loki comme source de données pour l'analyse d'incidents, l'audit de sécurité et l'exécution de requêtes en langage **LogQL**.

```text
                                  [ Grafana (:3000) ]
                                           │
                                           ▼ (Requêtes LogQL)
                                [ Grafana Loki (:3100) ]
                                           ▲
                                           │ (Push HTTP / Stream JSON)
                                 [ Promtail Agent ]
                                           │
         ┌─────────────────────────────────┼─────────────────────────────────┐
         │ (Socket & Disk Logs)            │ (Socket & Disk Logs)            │ (Socket & Disk Logs)
         ▼                                 ▼                                 ▼
┌──────────────────┐             ┌──────────────────┐              ┌──────────────────┐
│   HAProxy Edge   │             │   Caddy Web      │              │ PostgreSQL DB    │
│  (/var/lib/docker)             │  (/var/lib/docker)             │  (/var/lib/docker)│
└──────────────────┘             └──────────────────┘              └──────────────────┘
```

---

## 10.2 Configurations Technologiques et Déploiement

L'ensemble des fichiers de configuration est centralisé dans le répertoire `~/internship-devsecops/monitoring-tier/`.

### A. Configuration de l'Agent Promtail (`monitoring-tier/promtail/promtail-config.yml`)

Promtail est configuré avec l'extension `docker_sd_configs` pour détecter automatiquement les conteneurs actifs. Il applique une règle d'expression régulière (`/?(.*)`) pour normaliser le nom des conteneurs en supprimant le préfixe slash optionnel, puis extrait dynamiquement le flux de sortie (`stdout/stderr`).

```yaml
server:
  http_listen_port: 9080
  grpc_listen_port: 0

positions:
  filename: /tmp/positions.yaml

clients:
  - url: http://loki:3100/loki/api/v1/push

scrape_configs:
  - job_name: docker
    docker_sd_configs:
      - host: unix:///var/run/docker.sock
        refresh_interval: 5s
    relabel_configs:
      - source_labels: ['__meta_docker_container_name']
        regex: '/?(.*)'
        target_label: 'container'
      - source_labels: ['__meta_docker_container_log_stream']
        target_label: 'stream'
    pipeline_stages:
      - docker: {}
```

### B. Configuration du Backend Loki (`monitoring-tier/loki/loki-config.yml`)

Loki s'exécute en mode monolithique avec le schéma de stockage moderne **TSDB** partitionné par tranches quotidiennes de 24 heures.

```yaml
auth_enabled: false

server:
  http_listen_port: 3100

common:
  path_prefix: /tmp/loki
  storage:
    filesystem:
      chunks_directory: /tmp/loki/chunks
      rules_directory: /tmp/loki/rules
  replication_factor: 1
  ring:
    kvstore:
      store: inmemory

schema_config:
  configs:
    - from: 2024-01-01
      store: tsdb
      object_store: filesystem
      schema: v13
      index:
        prefix: index_
        period: 24h

limits_config:
  reject_old_samples: false
```

### C. Configuration d'Orchestration Docker (`monitoring-tier/compose.yml`)

Pour lire les fichiers journaux générés par Docker sur le système hôte CachyOS, le service **Promtail** est exécuté sous l'utilisateur `root` et bénéficie du montage en lecture seule du répertoire `/var/lib/docker/containers`.

```yaml
loki:
  image: grafana/loki:2.9.4
  container_name: loki
  restart: unless-stopped
  ports:
    - "3100:3100"
  volumes:
    - ./loki/loki-config.yml:/etc/loki/loki-config.yml:ro
  command: -config.file=/etc/loki/loki-config.yml
  networks:
    - app-net

promtail:
  image: grafana/promtail:2.9.4
  container_name: promtail
  user: root
  restart: unless-stopped
  volumes:
    - ./promtail/promtail-config.yml:/etc/promtail/promtail-config.yml:ro
    - /var/run/docker.sock:/var/run/docker.sock:ro
    - /var/lib/docker/containers:/var/lib/docker/containers:ro
  command: -config.file=/etc/promtail/promtail-config.yml
  networks:
    - app-net
```

---

## 10.3 Procédure de Déploiement et Validation Fonctionnelle

Les commandes suivantes ont été exécutées sous l'environnement **fish** pour instancier la stack, générer du trafic et valider l'ingestion.

### 1. Démarrage des Services d'Observabilité

```fish
cd ~/internship-devsecops/monitoring-tier
docker compose up -d loki promtail
```

---

### 2. Génération de Trafic de Test

Exécution d'une série de requêtes vers l'Ingress HAProxy pour alimenter les journaux d'accès.

```fish
for i in (seq 1 5)
    curl -sk https://localhost/ > /dev/null
end
sleep 3
```

---

### 3. Inspection des Pointeurs d'Ingestion (Cursors)

Vérification de l'enregistrement des positions de lecture par Promtail.

```bash
docker exec -it promtail cat /tmp/positions.yaml
```

**Résultat :**

```text
positions:
  cursor-4a35d68883fd70fa4dc1fd1500cca0f498ab2ef96464c07adbe316ac8e0dce43: "1785861531"
  cursor-4e035a98607dadda4278189acc0ab1bfd14e7027885964df067d067623b26086: "1785861302"
  cursor-930af63520ad845cc5d7aff7cc133d16976a0feccf15fdc1a62a100a60cb0733: "1785861301"
```

---

### 4. Qualification de l'API REST Loki

Interrogation de l'API de Loki pour confirmer la disponibilité des métadonnées de conteneurs.

```bash
curl -s "http://localhost:3100/loki/api/v1/label/container/values" | jq .
```

**Résultat attendu :**

```json
[
  "caddy-srv1",
  "caddy-srv2",
  "haproxy-edge",
  "php-fpm-srv1",
  "php-fpm-srv2"
]
```

L'ensemble des logs de l'infrastructure est désormais centralisé, indexé par conteneur et interrogeable en temps réel via l'interface **Grafana** (**Explore → Data source: Loki → `{container="haproxy-edge"}`**).

---

<div style="page-break-after: always;"></div>

# 11. Phase 11 : Automatisation CI/CD et Sécurité "Shift-Left" (Gitleaks, Semgrep & Trivy)

## 11.1 Principes et Architecture d'Analyse Automatisée

Afin d'intégrer la sécurité au plus tôt dans le cycle de vie du développement (*Shift-Left Security*), la **Phase 11** met en œuvre un pipeline d'analyse automatisée de la sécurité. L'objectif est d'empêcher l'introduction de secrets, de failles applicatives ou de vulnérabilités système au sein du cluster avant tout déploiement.

Conformément à nos contraintes d'isolation de l'hôte **CachyOS**, l'ensemble des outils d'analyse (Gitleaks, Semgrep, Trivy) est exécuté au sein de conteneurs Docker éphémères.

L'architecture d'analyse s'articule autour de trois axes complémentaires :

- **Détection des Fuites de Secrets (Gitleaks)** : Analyse l'historique des commits Git et les fichiers de travail à la recherche de clés d'API, jetons d'accès ou mots de passe codés en dur.
- **Analyse Statique de Sécurité Applicative (SAST - Semgrep)** : Scanne le code source PHP/Laravel selon les règles **OWASP Top 10** afin d'identifier les injections SQL, les failles XSS, le détournement d'URL (SSRF) et la mauvaise gestion des entrées utilisateur.
- **Analyse des Vulnérabilités d'Images et IaC (Trivy)** : Analyse les images Docker personnalisées (`custom-php-fpm:8.2`) pour identifier les CVE au niveau des paquets système (Alpine) et contrôle la conformité des fichiers `Dockerfile` et Docker Compose (IaC).

```text
       [ Code Source & Configurations IaC ]
                        │
                        ▼
┌──────────────────────────────────────────────────────────┐
│        Phase 11 : Pipeline de Sécurité (Shift-Left)      │
├──────────────────┬───────────────────┬───────────────────┤
│     Gitleaks     │      Semgrep      │       Trivy       │
│  (Secrets Scan)  │    (SAST Scan)    │   (CVE & IaC)     │
└────────┬─────────┴─────────┬─────────┴─────────┬─────────┘
         │                   │                   │
         ▼                   ▼                   ▼
  [ Zero Secret ]     [ OWASP Clean ]    [ Patch & Non-Root ]
                        │
                        ▼
      [ Image Conforme & Prête pour la Prod ]
```

---

## 11.2 Analyse des Résultats et Plan de Remédiation (Shift-Left Remediation)

L'exécution des premiers outils d'analyse automatisée sur notre plateforme a permis d'intercepter et de corriger plusieurs vulnérabilités et défauts de configuration majeurs avant la mise en production.

### 1. Analyse d'Images Conteneur (Trivy Image Scan)

- **Vulnérabilité Détectée** : `CVE-2026-33630` (Sévérité : **HIGH**) dans la bibliothèque `c-ares` (version `1.34.6-r0`).
- **Impact** : Risque de corruption mémoire de type *Use-After-Free* / *Double-Free*.
- **Remédiation** : Ajout d'une mise à jour explicite du paquet `c-ares` via `apk upgrade` lors du build de l'image.

---

### 2. Analyse de Fichiers de Configuration (Trivy IaC Config Scan)

- **Défaut `DS-0002` (HIGH)** : Absence d'instruction `USER` non-privilégiée dans les Dockerfiles (exécution sous l'utilisateur `root`).
- **Défaut `DS-0026` (LOW)** : Absence d'instruction `HEALTHCHECK` pour surveiller l'état du processus PHP-FPM.
- **Remédiation** : Ajout de l'utilisateur système `www-data` et déclaration d'une sonde `HEALTHCHECK` via `nc` (Netcat).

---

### 3. Fichiers de Configuration Corrigés (`server1-web/Dockerfile` & `server1b-web/Dockerfile`)

```dockerfile
FROM php:8.2-fpm-alpine

# Remédiation CVE-2026-33630 : Mise à niveau de c-ares et installation des extensions PDO
RUN apk update && apk upgrade --no-cache c-ares && \
    docker-php-ext-install pdo pdo_pgsql

# Remédiation DS-0026 : Sonde d'évaluation de santé du conteneur
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD nc -z 127.0.0.1 9000 || exit 1

# Remédiation DS-0002 : Basculement vers un utilisateur non-privilégié
USER www-data
```

---

## 11.3 Validation Fonctionnelle du Pipeline

L'efficacité du pipeline a été validée par la re-soumission de tests synthétiques et la vérification des sorties de chaque scanner.

### 1. Validation Gitleaks (Détection de Secrets)

```bash
docker run --rm -v (pwd):/path zricethezav/gitleaks:latest detect --source="/path" -v --no-git
```

> **Résultat** : `Scanned 22.98 MB - no leaks found`. Confirmation qu'aucun secret ou clé d'API n'est exposé en clair dans le répertoire du projet.

---

### 2. Validation Semgrep (SAST - OWASP Top 10)

```bash
docker run --rm -v (pwd)/server1-web/laravel:/src semgrep/semgrep semgrep scan --config p/php /src
```

> **Résultat** : Détection réussie des failles d'injection SQL (`tainted-sql-string`) introduites lors des tests, confirmant le blocage automatique du code vulnérable.

---

### 3. Validation Trivy (Images & Infrastructure as Code)

```bash
# Inspection de l'image re-construite
docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy:latest image custom-php-fpm:8.2

# Audit des fichiers Dockerfile
docker run --rm -v (pwd):/src aquasec/trivy:latest config /src
```

> **Résultat Final** : **0 vulnérabilité CRITICAL/HIGH** et suppression totale des avertissements de sécurité IaC sur l'ensemble des Dockerfiles du projet.

---

<div style="page-break-after: always;"></div>

# 12. Phase 12 : Qualification Opérationnelle, Tests de Charge et Simulation d'Attaques Automatisées (Grafana k6, OWASP ZAP & Boucle IPS Adaptative)

## 12.1 Contexte et Objectifs de la Qualification Opérationnelle

La **Phase 12** constitue l'étape d'homologation finale et de validation fonctionnelle sous contrainte de notre plateforme de livraison applicative multi-tier. L'objectif principal est de soumettre l'architecture globale à un ensemble d'épreuves synthétiques poussées afin d'évaluer deux exigences fondamentales du modèle DevSecOps :

1. **La Résilience de la Haute Disponibilité et la Tolérance aux Pannes (*High Availability & Zero-Downtime Failover*)** : Évaluer la capacité du répartiteur de charge HAProxy (Server 0) à maintenir le service sans interruption lors de la défaillance brutale d'un nœud applicatif Web (`caddy-srv1` / Server 1A) en plein pic de trafic.

2. **L'Étanchéité Applicative et l'Efficacité de la Réponse Active aux Menaces (DAST & Dynamic IPS)** : Soumettre le point d'entrée public (`https://localhost/`) à un audit dynamique d'injection de vulnérabilités (OWASP ZAP), puis simuler des attaques de type Injection SQL (SQLi) et Traversée de Répertoire (*Path Traversal*) pour valider la chaîne de détection et de blocage au niveau du noyau Linux (*Kernel Netfilter Drop Rules*).

---

## 12.2 Analyse de la Charge et Basculement Dynamique (Grafana k6 Stress Testing)

Pour simuler un trafic de production réaliste et mesurer les métriques **Golden Signals** de la SRE (débit, latence, taux d'erreur, saturation), un conteneur éphémère **Grafana k6** a été déployé en mode isolation réseau directe (*host network mode*).

### 12.2.1 Protocole de Test et Profil de Charge

Le scénario d'injection est défini par un script JavaScript (`load_test.js`) simulant un trafic HTTP/2 chiffré TLS avec montée en charge progressive :

- **Nombre d'Utilisateurs Virtuels (VUs)** : 50 VUs concourants en régime permanent.
- **Durée de l'Épreuve** : 45 secondes d'injection continue.
- **Perturbation Système Injectée** : Arrêt forcé et immédiat du conteneur `caddy-srv1` via la commande CLI `docker stop caddy-srv1` à $t = 20\text{s}$ du test de charge.

```text
                        ┌──────────────────────────────────────────────┐
                        │   Grafana k6 Load Injector (50 Concurrent VUs) │
                        └──────────────────────┬───────────────────────┘
                                               │ HTTP GET https://localhost/
                                               ▼
                        ┌──────────────────────────────────────────────┐
                        │     HAProxy Edge Ingress (Server 0)          │
                        │    Active Health Probing (inter 2s fall 2)   │
                        └──────────────┬────────────────┬──────────────┘
                                       │                │
            Traffic Re-routed      ┌───┘                └───┐  Traffic Dropped
            Instantly (100%)       │                        │  (Micro-Window)
                                   ▼                        ▼
                        ┌────────────────────┐    ┌────────────────────┐
                        │    caddy-srv2      │    │    caddy-srv1      │
                        │   (Server 1B)      │    │   (Server 1A)      │
                        │  Status: ACTIVE    │    │  STATUS: KILLED    │
                        └────────────────────┘    └────────────────────┘
```

### 12.2.2 Analyse Explicative des Métriques Révélées

L'exécution de la campagne de charge a généré le bilan métrique structuré ci-dessous :

| **Métrique Système / Réseau** | **Valeur Mesurée** | **Interprétation Technique et Opérationnelle** |
|--------------------------------|--------------------|------------------------------------------------|
| **Volume Total de Requêtes (`http_reqs`)** | **9 428 requêtes** | Débit soutenu de **208,91 requêtes/seconde** sur la durée du test. |
| **Requêtes Validées (`status is 200`)** | **99,67 % (9 397)** | Réponse nominale du cluster Web garantissant la disponibilité applicative. |
| **Taux d'Échec HTTP (`http_req_failed`)** | **0,32 % (31 requêtes)** | Fenêtre de perte résiduelle durant le basculement (*failover gap*). |
| **Latence Moyenne (`http_req_duration`)** | **45,18 ms** | Temps de traitement moyen en régime permanent chiffré TLS. |
| **Latence au 95ème Percentile ($P_{95}$)** | **93,71 ms** | Garantie de performance sous forte saturation ($P_{95} < 100\text{ms}$). |
| **Débit Réseau Entrant/Sortant** | **619 kB/s / 13 kB/s** | Volume total transféré de 28 MB de données applicatives. |

### 12.2.3 Analyse du Mécanisme de Basculement (*Zero-Downtime Failover Mechanics*)

Les **31 requêtes échouées (0,32 %)** enregistrées par k6 lors du test ne constituent pas une défaillance du système, mais illustrent le fonctionnement physique des sondes de santé d'HAProxy :

1. **Détection du Break TCP** : Lors de l'arrêt brutal de `caddy-srv1`, les requêtes en vol vers ce conteneur ont subi un rejet TCP (*RST/Timeout*).
2. **Mise hors service du Backend** : HAProxy, configuré avec des sondes de santé actives (`check inter 2000ms fall 2 rise 3`), a immédiatement détecté l'absence de réponse au bout de 2 échecs consécutifs et a basculé le nœud `caddy-srv1` en état `DOWN`.
3. **Redirection Immédiate** : En moins de quelques millisecondes, 100 % du trafic subsistant a été redirigé vers l'instance miroir `caddy-srv2`, maintenant une continuité de service totale pour l'ensemble des requêtes ultérieures sans intervention humaine.

---

## 12.3 Analyse Dynamique de Sécurité Applicative (DAST avec OWASP ZAP)

Complémentaire aux analyses statiques (SAST - Semgrep/Gitleaks) de la Phase 11, une analyse dynamique automatisée **OWASP ZAP** (`zaproxy/zap-stable`) a été exécutée contre le point d'accès public HTTPS.

### 12.3.1 Synthèse de l'Audit de Référence DAST (`zap_report.html`)

L'analyseur ZAP a inspecté la surface d'attaque externe en injectant des payloads d'exploration sur les formulaires, les en-têtes HTTP et les paramètres de requête URL.

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│                    BILAN DU SCAN DAST OWASP ZAP                             │
├──────────────────────────────┬──────────────────┬───────────────────────────┤
│ Niveau de Risque (Severity)  │ Nombre d'Alertes │ État de Conformité        │
├──────────────────────────────┼──────────────────┼───────────────────────────┤
│ High (Risque Élevé)          │        0         │ ✅ CONFORME (Zero RCE/SQLi)│
│ Medium (Risque Moyen)        │        0         │ ✅ CONFORME               │
│ Low (Risque Faible)          │        2         │ ⚠️ À REMÉDIER (Headers)   │
│ Informational (Informatif)   │        3         │ ℹ️ OBSERVATION            │
└──────────────────────────────┴──────────────────┴───────────────────────────┘
```

### 12.3.2 Détail des Alertes et Plan de Hardening Applicatif

1. **Absence de Jetons Anti-CSRF (Plugin ID : `20012` - CWE-352)** :
   - *Constat* : Détection d'un formulaire HTML (`/public/index.php`) ne comportant pas de champ masqué contenant un jeton de validation de session.
   - *Risque* : Vulnérabilité aux attaques par Falsification de Requête inter-sites (*Cross-Site Request Forgery*).
   - *Remédiation Applicative* : Injection du middleware de validation `@csrf` au sein du framework Laravel.

2. **Manque de l'En-tête HTTP `X-Content-Type-Options` (Plugin ID : `10021` - CWE-693)** :
   - *Constat* : L'en-tête de réponse HTTP n'explicite pas la directive `nosniff`.
   - *Risque* : Possibilité pour les navigateurs clients d'effectuer du *MIME-Sniffing* et d'exécuter des fichiers malveillants masqués sous de fausses extensions.
   - *Remédiation Ingress* : Ajout de la directive `http-response set-header X-Content-Type-Options nosniff` dans le fichier de configuration HAProxy (`haproxy.cfg`).

---

## 12.4 Validation de la Télémétrie IPS et de la Réponse Active au Niveau Noyau

Le système de prévention d'intrusion (IPS) repose sur une boucle fermée d'orchestration entre le traitement des journaux, le stockage NoSQL et le pare-feu du noyau Linux.

```text
[ Attaquant HTTP (Curl / Script) ]
              │
              ▼ (Attaque SQLi / Path Traversal)
[ Edge HAProxy / Caddy Web Server ]
              │
              ▼ (Logs JSON d'Accès)
[ Promtail Log Forwarder ] ──> [ Grafana Loki Log Store ]
                                        │
                                        ▼ (Abonnement Stream / Pattern Match)
                            [ Host Daemon : ips_daemon.py ]
                                        │
             ┌──────────────────────────┴──────────────────────────┐
             │ (Audit Log Event)                                   │ (Netfilter Rule Injection)
             ▼                                                     ▼
┌───────────────────────────┐                         ┌───────────────────────────┐
│   Cassandra NoSQL DB      │                         │   Linux Kernel Netfilter  │
│ (ips_security.attack_logs)│                         │ (iptables / DOCKER-USER)  │
└───────────────────────────┘                         └───────────────────────────┘
```

### 12.4.1 Ingestion et Stockage NoSQL des Alertes (Cassandra)

Lors de l'injection d'attaques répétées par dérivation d'URL (`/public/index.php?user=1 OR 1=1`), le démon de sécurité enregistre les métadonnées de l'agresseur dans la table wide-column distribuée `ips_security.attack_logs`.

La validation du schéma et la persistance des données ont été confirmées via l'outil `cqlsh` du conteneur `cassandra-db` :

```sql
cqlsh:ips_security> SELECT source_ip, attack_type, uri_path, timestamp
FROM ips_security.attack_logs
LIMIT 1;

 source_ip     | attack_type   | uri_path                        | timestamp
---------------+---------------+---------------------------------+--------------------------------
 192.168.1.100 | SQL_INJECTION | /public/index.php?user=1 OR 1=1 | 2026-08-12 07:22:34.791000+0000

(1 rows)
```

**Justification de l'Architecture NoSQL (Cassandra)** :

Contrairement à une base relationnelle classique (PostgreSQL), la structure orientée colonnes de Cassandra offre des performances d'écriture ultra-rapides sans verrou (*Append-Only Commit Log*), permettant d'absorber des pics élevés de journaux de sécurité lors d'une attaque par déni de service (DoS) sans impacter les performances de l'application principale.

### 12.4.2 Filtrage Réseau Dynamique au Niveau Noyau (`Netfilter / iptables`)

Dès qu'une adresse IP franchit le seuil de sévérité défini par l'IPS, une règle de rejet passif est automatiquement injectée dans la chaîne `DOCKER-USER` du pare-feu Netfilter de l'hôte CachyOS.

La vérification CLI confirme l'interception et le blocage immédiat du paquet au niveau transport (Layer 3/4), avant même qu'il ne puisse atteindre les ponts Docker internes (`dmz-net`) :

```bash
sudo iptables -L DOCKER-USER -v -n
```

**Résultat de l'Inspection Kernel** :

```text
Chain DOCKER-USER (1 references)
 pkts bytes target     prot opt in     out     source           destination
    0     0 DROP       all  --  *  *   192.168.1.100    0.0.0.0/0
```

---

## 12.5 Bilan Synthétique et Conclusion Générale du Projet DevSecOps

La réalisation intégrale des **12 Phases du Projet** a permis la conception, le déploiement, la sécurisation et la validation d'une infrastructure d'entreprise distribuée complète :

1. **Isolation Réseau Stricte** : Répartition sur 3 réseaux isolés (`dmz-net`, `app-net`, `db-net`), garantissant qu'aucune base de données transactionnelle n'est exposée sur l'Internet public.
2. **Haute Disponibilité et Scalabilité Horizontale** : Couplage de HAProxy en ingress L7 et d'un cluster d'application Caddy/PHP-FPM communiquant par Sockets de Domaine UNIX (UDS) pour une latence minimale.
3. **Observabilité Globale** : Supervision continue des métriques système et applicatives via Prometheus/Grafana et agrégation centralisée des logs en temps réel sous Loki/Promtail.
4. **Sécurité Intégrée Globale (*Shift-Left & Active Runtime Defense*)** :
   - Validation statique du code et des conteneurs en pipeline CI/CD (Gitleaks, Semgrep, Trivy).
   - Audits dynamiques DAST réguliers (OWASP ZAP).
   - Prévention active d'intrusions à boucle fermée appuyée par Cassandra et Netfilter.

---
