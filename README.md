# Plugin iCalendar pour Jeedom

[![Jeedom Compatibility](https://img.shields.io/badge/Jeedom-v4.4%2B-blue.svg)](https://www.jeedom.com)
[![License: AGPL v3](https://img.shields.io/badge/License-AGPLv3-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-purple.svg)](https://php.net)
[![Version](https://img.shields.io/badge/version-1.6.2-orange.svg)](plugin_info/info.json)

Le plugin **iCalendar** permet d'intégrer, visualiser et exploiter vos calendriers distants (**fichiers ICS** ou serveurs **CalDAV**) directement au cœur de votre domotique **Jeedom**.

Au-delà d'un simple affichage, **iCalendar transforme votre agenda en un puissant moteur d'automatisation**, capable d'exécuter des actions, des scénarios ou des interactions au fil de vos événements (début, fin, ou en amont via les scénarios).

---

## Sommaire

- [Fonctionnalités principales](#-fonctionnalités-principales)
- [Compatibilité](#-compatibilité)
- [Installation](#-installation)
- [Configuration](#-configuration)
  - [1. Ajouter un agenda (ICS ou CalDAV)](#1-ajouter-un-agenda-ics-ou-caldav)
  - [2. Paramètres de l'agenda](#2-paramètres-de-lagenda)
  - [3. Commandes de projection (J0 à J6)](#3-commandes-de-projection-j0-à-j6)
- [Déclenchement d'actions depuis l'agenda](#-déclenchement-dactions-depuis-lagenda)
  - [Syntaxe dans la description de l'événement](#syntaxe-dans-la-description-de-lévénement)
  - [Exemples concrets](#exemples-concrets)
  - [Assistant graphique de génération de trame](#assistant-graphique-de-génération-de-trame)
- [Utilisation avancée dans les scénarios Jeedom](#-utilisation-avancée-dans-les-scénarios-jeedom)
  - [La commande ExecuteFunction](#la-commande-executefunction)
  - [Exemple : Déclencher une action en amont d'un événement](#exemple--déclencher-une-action-en-amont-dun-événement)
- [Le Panel iCalendar](#-le-panel-icalendar)
- [Crédits & Auteurs](#-crédits--auteurs)
- [Licence](#-licence)

---

## 🚀 Fonctionnalités principales

* **Multi-fournisseurs & Protocoles** :
  * Synchronisation de flux **ICS** (URL publique ou privée, Google Calendar, Apple iCloud, Microsoft 365, etc.).
  * Synchronisation bidirectionnelle/authentifiée **CalDAV** (Nextcloud, Synology Calendar, Baïkal, ownCloud, etc.) avec découverte automatique des calendriers via boîte modale.
* **Affichage interactif (FullCalendar)** :
  * Intégration sur le Dashboard Jeedom et dans un **Panel dédié** plein écran.
  * Vues multi-échelles : **Mois**, **Semaine**, **4 Jours**, **Journée**.
  * Clic sur un événement pour afficher le détail (dates, horaires, lieu, description, actions associées).
* **Automatisations événementielles** :
  * Lancement automatique d'**actions**, de **scénarios** ou d'**interactions** au début (`DA`) ou à la fin (`FA`) d'un événement.
  * Passage dynamique de variables aux scénarios ou de valeurs aux commandes actions.
* **Commandes de projection (J0 à J6)** :
  * Génération de commandes d'information pour le jour J (`J0`) et les jours futurs jusqu'à `J+6`.
  * Idéal pour anticiper des besoins (ex : vérifier la veille au soir s'il y a un événement le lendemain matin pour allumer le chauffage).
* **Historisation et traçabilité** :
  * Tableau récapitulatif des actions exécutées par journée, avec purge simplifiée.

---

## 💻 Compatibilité

* **Jeedom** : v4.0 à v4.4+ (pleine compatibilité testée avec Jeedom 4.4, thèmes Dark/Light, modales Bootstrap/jQuery).
* **PHP** : PHP 7.4, 8.0, 8.1, 8.2+.
* **Plateformes** : Debian x86/ARM (Raspberry Pi, Smart, Atlas, Luna, VM, Docker).

---

## 📦 Installation

### Depuis GitHub (Jeedom 4) :
1. Dans Jeedom, rendez-vous dans le menu **Réglages > Système > Configuration**, onglet **Mises à jour/Market**.
2. Activez l'option **Activer les fonctionnalités GitHub**.
3. Allez dans **Plugins > Gestion des plugins**, puis cliquez sur **Sources de mise à jour (GitHub)**.
4. Renseignez :
   - **Type de source** : GitHub
   - **ID logique du plugin** : `iCalendar`
   - **Utilisateur ou organisation** : `alt-erre`
   - **Nom du dépôt** : `plugin-iCalendar`
   - **Branche** : `main`
5. Enregistrez et lancez l'installation / mise à jour.

---

## ⚙️ Configuration

### 1. Ajouter un agenda (ICS ou CalDAV)
1. Allez dans **Plugins > Organisation > iCalendar**.
2. Cliquez sur **Ajouter** pour créer un nouvel équipement (ex: *Agenda Familial*).
3. Activez et rendez visible l'équipement.
4. Dans l'onglet **Commandes**, cliquez sur **Ajouter un agenda** :
   - **Type d'agenda** :
     - `Récupération d'un fichier ics` : collez l'URL HTTP/HTTPS de votre calendrier `.ics`.
     - `Lecture d'un agenda CalDav` : indiquez l'URL du serveur CalDAV, le nom d'utilisateur et le mot de passe.
   - Si vous utilisez CalDAV, cliquez sur la loupe <sup><i class="fa fa-question-circle"></i></sup> à côté de **Nom agenda** pour interroger le serveur et sélectionner votre calendrier parmi la liste détectée.

### 2. Paramètres de l'agenda
* **Format donnée** :
  * *Événements sur la journée* : remonte l'ensemble des événements du jour.
  * *Événements sur l'heure à venir* : filtre les événements débutant dans les 60 prochaines minutes.
  * *Événements courants* : remonte uniquement l'événement actif en ce moment.
* **Période à traiter** : permet de créer les commandes complémentaires de projection (`+ lendemain`, `+ 2 jours`, `1 semaine`).
* **Fréquence synchro** : intervalle de rafraîchissement avec le calendrier distant (de 30 minutes à 24 heures).
* **Autoriser les actions/scénarios** : cochez pour que Jeedom exécute les ordres inscrits dans la description des événements.
* **Autoriser les interactions** : permet à Jeedom d'interpréter le titre de l'événement comme une phrase d'interaction.

### 3. Commandes de projection (J0 à J6)
Pour chaque agenda configuré :
* **`J0`** : correspond à la commande principale (jour J).
* **`J1` à `J6`** : sous-commandes créées automatiquement selon la *Période à traiter* choisie (J+1 à J+6). Elles permettent d'interroger facilement les journées à venir dans vos scénarios ou widgets.

---

## ⚡ Déclenchement d'actions depuis l'agenda

Le plugin peut exécuter des commandes ou des scénarios automatiquement en lisant le contenu du champ **Description** de votre événement (depuis Google Calendar, Outlook, etc.).

### Syntaxe dans la description de l'événement

Chaque ordre s'écrit sur une ligne dédiée, sous la forme :

```text
QUAND|TYPE=CIBLE|PARAMÈTRES
```

* **QUAND** :
  * `DA` : **D**ébut **A**ction (exécuté à la 1ère minute de l'événement).
  * `FA` : **F**in **A**ction (exécuté à la dernière minute de l'événement).
* **TYPE** :
  * `act` : Commande Action Jeedom (identifiée par son ID ou son nom complet `#[Objet][Equipement][Commande]#`).
  * `sc` : Scénario Jeedom (identifié par son ID).
* **PARAMÈTRES** *(optionnels)* :
  * `message=titre|message` : pour les commandes de type message.
  * `slider=valeur` : pour les commandes de type curseur / slider.
  * `nom_variable=valeur` : pour affecter une variable transmise au scénario.
  * `#active` / `#desactive` : pour activer ou désactiver un équipement ou un scénario.

### Exemples concrets

#### 1. Allumer une lumière au début de l'événement et l'éteindre à la fin :
```text
DA|act=#[Salon][Lumière][On]#
FA|act=#[Salon][Lumière][Off]#
```

#### 2. Définir une consigne de thermostat à 21°C au début :
```text
DA|act=#[Chambre][Thermostat][Consigne]#|slider=21
```

#### 3. Lancer le scénario ID 12 en lui passant une variable `mode=vacances` :
```text
DA|sc=12|mode=vacances
```

#### 4. Envoyer une notification Telegram :
```text
DA|act=#[Communication][Telegram][Aurelien]#|message=Rappel|Réunion dans 5 minutes !
```

### Assistant graphique de génération de trame
Pour vous simplifier la vie, un assistant est intégré dans le plugin :
- Rendez-vous sur la configuration de votre équipement.
- Cliquez sur le bouton **Assistant d'écriture** pour construire visuellement vos commandes et copier la trame générée directement dans votre calendrier.

---

## 💡 Utilisation avancée dans les scénarios Jeedom

### La commande ExecuteFunction
Chaque agenda dispose d'une commande système de type Action/Message appelée `ExecuteFunction`. Elle permet d'interroger dynamiquement les détails d'un événement depuis un bloc Scénario Jeedom :

| Fonction | Description | Arguments (1 par ligne) |
| :--- | :--- | :--- |
| `getTimeStart` | Récupère l'heure de début d'un événement | `title=Nom` ou `id=UID`<br/>`date=Hi` (format PHP)<br/>`jour=J0` |
| `getTimeEnd` | Récupère l'heure de fin d'un événement | `title=Nom` ou `id=UID`<br/>`date=Hi`<br/>`jour=J0` |
| `getTitle` | Récupère le titre d'un événement par son ID | `id=UID`<br/>`jour=J0` |
| `getLocation` | Récupère le lieu de l'événement | `title=Nom` ou `id=UID`<br/>`jour=J0` |
| `getDaySimple` | Récupère la liste brute des événements du jour | `jour=J0` (J0, J1, ...) |

Le résultat est automatiquement stocké dans une variable Jeedom nommée `[NomFonction]_[IdCommande]`.

### Exemple : Déclencher une action en amont d'un événement
Pour déclencher le préchauffage d'une pièce **30 minutes avant le début** d'un événement :

1. Créez un scénario programmé chaque matin à **04h00**.
2. Ajoutez une action appelant `ExecuteFunction` :
   - **Titre** : `getTimeStart`
   - **Message** :
     ```text
     title=Télétravail
     date=Hi
     jour=J0
     ```
   *(La variable Jeedom `getTimeStart_123` recevra par exemple `0830` pour 08h30).*
3. Ajoutez ensuite un bloc **`A` (Programmer)** :
   - **Heure** : `time_op(variable(getTimeStart_123), -30)`
   - **Action** : `#[Bureau][Chauffage][Confort]#`

---

## 📅 Le Panel iCalendar

Accessible via le menu **Accueil > iCalendar** (ou **Panel** selon votre configuration) :
* **Vue Calendrier** : Affiche l'ensemble de vos événements avec code couleur par calendrier, gestion du fuseau horaire local, et pop-up d'informations au clic.
* **Vue Actions** : Affiche l'historique complet des actions et scénarios exécutés par le plugin, triés par date, avec possibilité de déplier les paramètres et de supprimer l'historique d'une journée en un clic.

---

## 👥 Crédits & Auteurs

* **Auteur original** : [A. Barrau (abarrau)](https://github.com/abarrau)
* **Maintenance & Évolutions (Jeedom 4.4+)** : [Alterre](https://github.com/alt-erre)

---

## 📄 Licence

Ce projet est sous licence libre **GNU Affero General Public License v3.0 (AGPLv3)**.  
Consultez le fichier [LICENSE](LICENSE) pour prendre connaissance de l'ensemble de vos droits de copie, modification et distribution.