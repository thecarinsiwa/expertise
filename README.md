# Expertise — Plateforme Organisationnelle

**Expertise Humanitaire et Sociale** est une plateforme logicielle conçue pour structurer et piloter l’organisation : gestion des équipes, des projets, des missions sur le terrain, de la communication interne et des documents, dans un cadre humanitaire et social.

---

## À propos du projet

Ce dépôt regroupe l’écosystème technique d’**Expertise Humanitaire et Sociale** :

- **Base de données** (MySQL) : schéma complet pour tous les modules métier  
- **API** : interface programmatique pour les applications et intégrations  
- **Web** : site public ou portail utilisateur  
- **Admin** : back-office de gestion et de configuration  

L’objectif est de disposer d’un socle unique pour la gestion organisationnelle, les projets, les missions, la communication et la traçabilité, tout en restant évolutif et maintenable.

---

## Modules fonctionnels

| Module | Description |
|--------|-------------|
| **Gestion organisationnelle** | Organisation, entités, départements, services, unités, postes et hiérarchie |
| **Utilisateurs & personnel** | Utilisateurs, rôles, permissions, profils, staff, affectations, contrats, groupes |
| **Gestion de projet** | Portefeuilles, programmes, projets, phases, tâches, livrables, jalons, budgets et ressources |
| **Gestion de mission** | Missions, types, ordres de mission, objectifs, plans, rapports, dépenses et affectations |
| **Communication** | Canaux, conversations, messages, notifications, annonces, commentaires et pièces jointes |
| **Sécurité** | Sessions, journal d’activité, audit, authentification et autorisations |
| **Gestion documentaire** | Catégories, documents, versions et archivage |
| **Planification & suivi** | Calendriers, événements, plannings et indicateurs de performance |

Le schéma de base de données (en anglais) est décrit en détail dans [database/README.md](database/README.md).

---

## Structure du dépôt

```
expertise/
├── README.md           # Ce fichier
├── LICENSE             # Licence MIT
├── database/           # Schéma et scripts MySQL
│   ├── README.md       # Documentation du schéma
│   ├── 00_create_database.sql
│   └── schema.sql
├── api/                # API (à développer)
├── web/                # Application web (à développer)
└── admin/              # Back-office (à développer)
```

---

## Prérequis

- **MySQL** 5.7+ (ou MariaDB 10.2+) avec support `utf8mb4`
- Environnement PHP prévu pour `api/`, `web/` et `admin/` (à configurer selon le stack choisi)

---

## Installation de la base de données

1. Créer la base :

   ```bash
   mysql -u root -p < database/00_create_database.sql
   ```

2. Créer les tables :

   ```bash
   mysql -u root -p expertise < database/schema.sql
   ```

Voir [database/README.md](database/README.md) pour les détails et les conventions du schéma.

---

## Licence

Ce projet est sous licence **MIT**. Voir le fichier [LICENSE](LICENSE) pour le texte complet.

---

*Expertise Humanitaire et Sociale — Plateforme organisationnelle*
