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

- **Réalisé par :** Omar Elqouas
- **Encadrant Pédagogique :** [Nom de ton encadrant]
- **Date de Soumission :** Juillet 2026
- **Version du Document :** v1.0.0

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
