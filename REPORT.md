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
- **Version du Document :** v2.0.0

---

<div style="page-break-after: always;"></div>

# Table des Matières

1. [Phase 1 : Architecture Réseau et Environnement de Travail Isolés](#1-architecture-réseau-et-environnement-de-travail-isolés)
   - [1.1 Topologie de Réseau Virtuelle et Segmentation](#11-topologie-de-réseau-virtuelle-et-segmentation)
   - [1.2 Isolation de l'Espace Hôte (CachyOS)](#12-isolation-de-lespace-hôte-cachyos)
   - [1.3 Méthodologie de Validation du Réseau](#13-méthodologie-de-validation-du-réseau)
2. Phase 2 : Serveur Web de l'Application (Caddy & PHP-FPM) *(À venir)*
3. Phase 3 : Répartition de Charge Ingress (HAProxy) *(À venir)*

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
