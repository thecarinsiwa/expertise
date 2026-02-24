# Expertise – Database (MySQL)

Schéma MySQL unifié pour la plateforme Expertise. **Un seul fichier** : `schema.sql` (toutes les anciennes migrations y sont intégrées).

## Fichiers

| Fichier | Rôle |
|--------|------|
| `schema.sql` | Schéma complet (tables, contraintes, données de seed). Source unique. |
| `00_create_database.sql` | Création de la base (optionnel, si vous n’utilisez pas l’API `?action=init_db`). |

## Modules

| # | Module | Main tables |
|---|--------|-------------|
| 1 | Organizational management | `organisation`, `organisational_entity`, `department`, `service`, `unit`, `position`, `organisational_hierarchy` |
| 2 | User and staff management | `user`, `role`, `permission`, `profile`, `staff`, `assignment`, `contract`, `position_history`, `user_group` |
| 3 | Project management | `portfolio`, `programme`, `project`, `bailleur`, `project_bailleur`, `project_phase`, `task`, `sub_task`, `deliverable`, `milestone`, `project_budget`, `project_resource`, `project_assignment` |
| 4 | Mission management | `mission_type`, `mission_status`, `mission`, `mission_bailleur`, `mission_order`, `objective`, `mission_plan`, `mission_report`, `mission_expense`, `mission_assignment` |
| 5 | Communication | `channel`, `channel_member`, `conversation`, `conversation_participant`, `message`, `notification`, `announcement`, `comment`, `attachment`, `communication_history` |
| 6 | Security | `session`, `activity_log`, `audit`, `authentication`, `authorization` |
| 7 | Document management | `document_category`, `document`, `document_version`, `archiving` |
| 8 | Planning & tracking | `calendar`, `event`, `schedule`, `performance_indicator` |

## Setup

1. Create the database (optional):

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS expertise CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

2. Run the schema:

```bash
mysql -u root -p expertise < database/schema.sql
```

Or from MySQL:

```sql
USE expertise;
SOURCE /path/to/expertise/database/schema.sql;
```

## Conventions

- **Charset:** `utf8mb4` / `utf8mb4_unicode_ci`
- **Engine:** InnoDB
- **IDs:** `INT UNSIGNED AUTO_INCREMENT` (or `BIGINT UNSIGNED` for high-volume logs)
- **Timestamps:** `created_at`, `updated_at` where relevant
- **Soft logic:** `is_active` flags where applicable
