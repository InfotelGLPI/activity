# Documentation — Plugin Activity pour GLPI

**Licence :** GNU GPL v3+  
**Auteur :** Infotel (Xavier CAILLAUD)  
**Dépôt :** https://github.com/InfotelGLPI/activity

---

## Table des matières

1. [Présentation](#présentation)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Fonctionnalités](#fonctionnalités)
   - [Planning et activités](#planning-et-activités)
   - [Congés (demande et validation)](#congés-demande-et-validation)
   - [Jours fériés](#jours-fériés)
   - [Compte Rendu d'Activité (CRA)](#compte-rendu-dactivité-cra)
   - [Statistiques et rapports](#statistiques-et-rapports)
   - [Compteurs de congés](#compteurs-de-congés)
   - [Snapshots de documents](#snapshots-de-documents)
5. [Gestion des droits](#gestion-des-droits)
6. [Préférences utilisateur](#préférences-utilisateur)
7. [Notifications](#notifications)
8. [Désinstallation](#désinstallation)

---

## Présentation

Le plugin **Activity** enrichit GLPI avec la gestion complète des activités des techniciens :

- Suivi des **activités** dans le planning GLPI (événements externes colorés par type)
- Gestion des **demandes de congés** avec circuit de validation
- Affichage des **jours fériés** dans le planning
- Génération d'un **Compte Rendu d'Activité (CRA)** mensuel exportable en PDF
- **Statistiques** de temps par technicien, catégorie d'événement ou projet
- **Compteurs de congés** (CP, RTT, …) par utilisateur
- Intégration optionnelle avec le plugin **manageentities**

---

## Installation

1. Télécharger le plugin depuis [GitHub](https://github.com/InfotelGLPI/activity) ou la marketplace GLPI.
2. Décompresser l'archive dans le dossier `plugins/` (ou `marketplace/`) de votre installation GLPI.
3. Exécuter `composer install --no-dev` dans le répertoire du plugin.
4. Se connecter à GLPI en tant qu'administrateur.
5. Aller dans **Configuration › Plugins**, cliquer sur **Installer** puis **Activer** pour *Activity*.

---

## Configuration

Accès : **Configuration › Plugins › Activity › Configurer**

| Option | Description |
|--------|-------------|
| **Remplacer le nom de l'activité par le type** | Affiche le type d'activité comme libellé dans le planning |
| **Utiliser la répartition du temps** | Active la vue de répartition du temps par technicien |
| **Vue par homme/jour dans le planning** | Affiche les activités en journées-homme (visible si répartition activée) |
| **Utiliser les créneaux pairs** | Autorise uniquement les demi-journées paires (matin/après-midi) |
| **Autoriser uniquement les horaires entiers** | Bloque les demi-journées partielles |
| **Autoriser les activités le week-end** | Permet de saisir des événements le samedi et le dimanche |
| **Client principal** | Libellé du client principal, affiché dans l'en-tête du CRA |
| **Adresse e-mail pour les congés** | Adresse de notification pour les demandes de congés |
| **Pied de page du CRA** | Texte libre affiché en bas de chaque CRA PDF |
| **Utiliser le responsable de groupe comme valideur** | Le responsable de groupe reçoit automatiquement les demandes de congés |
| **Pourcentage de validation par défaut** | Valeur pré-remplie dans le formulaire de validation (ex. : 100 %) |
| **CRA par défaut** | Active le CRA par défaut pour les activités |
| **Utiliser les projets** | Permet d'associer des événements à des projets GLPI |
| **CRA par défaut – Projets** | Active le CRA pour les projets (visible si projets activés) |
| **Utiliser les heures (non les demi-journées) sur le CRA** | Saisie en heures au lieu de demi-journées |
| **Limiter les heures/jour via les heures de planning** | Utilise le planning d'activité pour contraindre la saisie quotidienne |
| **Utiliser les sous-catégories d'événements** | Active les sous-catégories sur les événements externes du planning |
| **Afficher l'entité de l'événement sur le CRA** | Affiche la colonne entité dans le CRA |
| **Choisir le projet lors des événements externes** | Permet de sélectionner un projet sur chaque événement et de l'afficher dans le CRA |

---

## Fonctionnalités

### Planning et activités

Le plugin enrichit le **planning GLPI** avec des types d'événements supplémentaires, chacun affiché avec une couleur distincte :

| Couleur | Type |
|---------|------|
| Bleu (#7DAEDF) | Congé |
| Vert (#84BE6A) | Activité (événement externe) |
| Cyan (#08A5AC) | Manageentities |
| Orange (#E85F0C) | Ticket |

Les techniciens peuvent saisir leurs activités directement depuis le planning. Chaque événement peut être associé à une **catégorie d'événement** (issue de la liste GLPI) et, si l'option est activée, à une **sous-catégorie** et à un **projet**.

---

### Congés (demande et validation)

#### Demande de congé

Un utilisateur disposant du droit `plugin_activity_can_requestholiday` peut soumettre une demande de congé depuis :
- Le menu **Outils › Activity** (interface centrale)
- Le menu latéral de l'interface simplifiée

Informations saisies :
- **Type de congé** — dropdown (CP, RTT, ou type personnalisé)
- **Période** — dropdown de périodes prédéfinies (optionnel)
- **Date de début / Date de fin**
- **Demi-journées** — matin, après-midi, ou journée complète
- **Commentaire** libre

La demande est intégrée dans le planning GLPI avec la couleur bleue.

#### Circuit de validation

Un valideur (`plugin_activity_can_validate`) reçoit une notification par e-mail et peut :
- **Accepter** la demande (en précisant le pourcentage de validation)
- **Refuser** la demande avec un motif

L'historique de validation est consultable dans l'onglet de la demande. Une notification est envoyée au demandeur après la décision.

#### Types de congés

Gérés sous **Configuration › Dropdowns › Types de congés**. Les types supportent les constantes prédéfinies :
- `CP` — Congés payés
- `RTT` — Réduction du temps de travail

#### Périodes de congés

Gérées sous **Configuration › Dropdowns › Périodes de congés**. Chaque période possède un nom court, une date de début et une date de fin.

---

### Jours fériés

Les **jours fériés** (`PublicHoliday`) sont affichés dans le planning GLPI. Ils peuvent être saisis manuellement ou importés. Ils apparaissent dans tous les plannings utilisateurs et dans le CRA.

---

### Compte Rendu d'Activité (CRA)

Le **CRA** est un rapport mensuel de l'activité d'un technicien. Il agrège :
- Les **activités/événements** saisis dans le planning
- Les **congés** validés
- Les **tâches de tickets** (si l'option est activée)
- Les **tâches de projets** (si l'option projets est activée)

#### Génération

Accès : **Outils › Activity › CRA**

L'utilisateur sélectionne :
- Le mois et l'année
- L'utilisateur (pour les profils ayant le droit `plugin_activity_all_users`)

Le CRA peut être :
- Affiché à l'écran sous forme de tableau interactif
- **Exporté en PDF** (via la bibliothèque FPDF)

#### Structure du CRA PDF

- En-tête : nom du technicien, mois, client principal
- Tableau des jours ouvrés avec, pour chaque jour : activités saisies (en demi-journées ou heures)
- Section congés : liste des absences du mois
- Total des jours travaillés / jours d'absence
- Pied de page personnalisable

---

### Statistiques et rapports

Accès réservé au droit `plugin_activity_statistics`.

Les statistiques permettent de visualiser la répartition du temps par :
- Technicien
- Catégorie d'événement
- Projet (si l'option est activée)
- Période (semaine, mois, trimestre, année)

---

### Compteurs de congés

La classe `HolidayCount` maintient des **compteurs** de jours de congés par utilisateur (jours acquis, jours pris, solde). Les administrateurs peuvent ajuster manuellement les compteurs depuis le profil utilisateur.

---

### Snapshots de documents

Lorsqu'un document GLPI est supprimé (`Document::purge`), le plugin purge automatiquement les snapshots associés via la classe `Snapshot`.

---

## Gestion des droits

Accès : **Administration › Profils › [profil] › onglet Activities**

| Droit | Description |
|-------|-------------|
| `plugin_activity` | Accès complet aux activités et au planning (lecture, écriture, suppression, admin) |
| `plugin_activity_can_requestholiday` | Autorisation de soumettre une demande de congé |
| `plugin_activity_can_validate` | Autorisation de valider ou refuser des demandes de congés |
| `plugin_activity_statistics` | Accès aux statistiques et rapports |
| `plugin_activity_all_users` | Voir et gérer les activités de tous les utilisateurs |

À l'installation, le profil Super-Admin reçoit tous les droits.

---

## Préférences utilisateur

Un onglet **Activities** est ajouté dans **Préférences utilisateur** pour les utilisateurs ayant au moins un droit plugin. Ils peuvent y configurer :
- Leur délégué pour la validation des congés
- L'affichage des filtres dans le planning

---

## Notifications

Le plugin envoie des notifications par e-mail aux étapes suivantes du circuit de congés :

| Événement | Destinataire |
|-----------|--------------|
| Nouvelle demande de congé | Valideur(s) désignés |
| Demande acceptée | Demandeur |
| Demande refusée | Demandeur |

Les notifications utilisent le système de mail GLPI (SMTP configuré dans **Configuration › Notifications**). L'adresse de notification peut être surchargée via le champ **Adresse e-mail pour les congés** dans la configuration du plugin.

---

## Désinstallation

1. Aller dans **Configuration › Plugins**.
2. Cliquer sur **Désactiver** puis **Désinstaller** pour *Activity*.

> **Attention :** La désinstallation supprime toutes les tables du plugin et les données associées (activités, demandes de congés, compteurs, snapshots).
