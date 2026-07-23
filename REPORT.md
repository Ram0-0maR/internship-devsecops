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
- **Date de Soumission :** Juillet 2026
- **Version du Document :** v3.0.0

---

<div style="page-break-after: always;"></div>


# Table des Matières

1. [Phase 1 : Architecture Réseau et Environnement de Travail Isolés](#1-architecture-réseau-et-environnement-de-travail-isolés)
   - [1.1 Topologie de Réseau Virtuelle et Segmentation](#11-topologie-de-réseau-virtuelle-et-segmentation)
   - [1.2 Isolation de l'Espace Hôte (CachyOS)](#12-isolation-de-lespace-hôte-cachyos)
   - [1.3 Méthodologie de Validation du Réseau](#13-méthodologie-de-validation-du-réseau)
2. [Phase 2 : Couche Applicative et Optimisation Inter-Conteneur](#2-couche-applicative-et-optimisation-inter-conteneur-phase-2)
   - [2.1 Communication Haute Performance via Socket de Domaine UNIX (UDS)](#21-communication-haute-performance-via-socket-de-domaine-unix-uds)
   - [2.2 Implémentation Technique et Fichiers de Configuration](#22-implémentation-technique-et-fichiers-de-configuration)
   - [2.3 Méthodologie de Validation de la Couche Web](#23-méthodologie-de-validation-de-la-couche-web)
3. [Phase 3 : Répartition de Charge Ingress (HAProxy)](#3-couche-dentrée-edge-ingress-et-terminaison-tls-phase-3)
   - [3.1 Proxy Inverse et Terminaison SSL/TLS Globale](#31-proxy-inverse-et-terminaison-ssltls-globale)
   - [3.2 Préservation de l'Identité Client (Header Injection)](#32-préservation-de-lidentité-client-header-injection)
   - [3.3 Implémentation Technique et Configurations de l'Ingress](#33-implémentation-technique-et-configurations-de-lingress)
   - [3.4 Méthodologie de Validation de l'Ingress et de la Sécurité](#34-méthodologie-de-validation-de-lingress-et-de-la-sécurité)
4. [Phase 4 : Couche de Données Orientée Persistance et Cache Privé](#4-couche-de-données-orientée-persistance-et-cache-privé-phase-4)
   - [4.1 Architecture et Segmentation Réseau de la Couche de Données](#41-architecture-et-segmentation-réseau-de-la-couche-de-données)
   - [4.2 Déploiement Déclaratif (`backend-data/docker-compose.yml`)](#42-déploiement-déclaratif-backend-datadocker-composeyml)
   - [4.3 Validation de l'Isolation du Subnet (`db-net`)](#43-validation-de-lisolation-du-subnet-db-net)

   
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
