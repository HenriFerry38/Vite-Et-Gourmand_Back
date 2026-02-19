# 🏷️ Vite & Gourmand – Backend API Symfony

## 📌 Présentation
Vite & Gourmand est une application web Full-Stack développée dans le cadre de l' Evaluation en cours de formation.  
Elle simule une plateforme moderne de commande de menus gastronomiques avec gestion complète des utilisateurs, des commandes et des avis clients.

L’application permet aux clients de découvrir des menus thématiques, passer commande en ligne, suivre l’évolution de leur prestation et laisser un avis après livraison.  
Elle intègre également un espace employé permettant la gestion des menus, des commandes et la validation des avis.
Elle possède aussi un espace administrateur permettant la création de comptes employés et l'analyse des statistiques administrative

Bien que le contexte soit fictif, le projet reproduit des problématiques réelles d’un service de restauration événementielle : gestion de stock, workflow de commande, authentification sécurisée, gestion des rôles et statistiques administratives.

---

## 🌍 Objectifs du projet

- Concevoir une architecture Full-Stack (Frontend SPA + Backend API REST)
- Mettre en place une authentification sécurisée par token
- Implémenter une gestion complète des commandes avec contrôle de stock
- Développer un système d’avis client modéré par les employés
- Séparer données transactionnelles (MySQL) et analytiques (MongoDB)
- Implémenter des notifications email automatiques
- Concevoir une interface responsive adaptée aux appareils mobiles
- Appliquer des bonnes pratiques d’architecture et de séparation des responsabilités

Cette API est une API REST BackEnd développée avec Symfony.

Elle gère :

- L’authentification via token API
- La gestion des utilisateurs
- Les commandes
- Les menus
- Les avis clients
- La gestion des rôles (Client / Employé / Admin)
- L’envoi d’emails (confirmation, validation, refus)

Cette API est consommée par une application Frontend SPA développée séparément.

---

## 🏗️ Stack technique

- Symfony 7.4.3
- PHP 8.4.14 (CLI)
- MySQL 8.0.44
- MongoDB
- Doctrine ORM
- Migrations Doctrine
- Authentification personnalisée (ApiTokenAuthenticator)
- Symfony Mailer
- Mailpit (en développement)
- Swagger / OpenAPI (NelmioApiDocBundle)
- NelmioCorsBundle

---

## Préparation préalable 

- Verifiez avant lancement de l'API que MySQL et MongoDB fonctionnent :

Windows puis Services.

Dans les services, verifiez que :

MYSQL80 -> est en cours d'execution, sinon activez le
MONGODB Server -> est en cours d'execution, sinon activez le

- Créez un dossier ViteEtGourmand en local 

ou 

- si la parite Front a été installée au préalable dans le Dossier ViteEtGourmand 

## 🚀 Installation en local

### 1️⃣ Cloner le repository

Depuis VsCode ou un terminal positionez vous dans le dossier ViteEtGourmand et ensuite :

```bash
git clone 'https://github.com/HenriFerry38/Vite-Et-Gourmand_Back'
```

Et après la fin du téléchargement :

```bash
cd Vite-Et-Gourmand_Back
```

---

### 2️⃣ Installer les dépendances

```bash
composer install
```

---

### 3️⃣ Configurer l’environnement

Copier le fichier `.env` vers `.env.local` :

```bash
copy .env .env.local
```
Puis modifier les variables suivantes dans .env.local :

```env
DATABASE_URL="mysql://USER:PASSWORD@127.0.0.1:3306/nomdeBDD?serverVersion=8.0.32&charset=utf8mb4"

MAILER_DSN=smtp://localhost:1025 --> Décommentez la ligne en supprimant les ###.
```
Ajouter aussi ces variables a la fin du fichier.
```env
MONGODB_URI="mongodb://127.0.0.1:27017"
MONGODB_DB="nomBDDnoSQL"
```
Vous pouvez adapter "nomdeBDD" pour renommer la BDD que vous allez creer.
Vous pouvez adapter "nomdeBDDnoSQL" pour renommer la BDD MongoDB que vous allez creer.
Adapter USER et PASSWORD selon votre configuration MySQL.

---

### 4️⃣ Créer la base de données

```bash
php bin/console doctrine:database:create
```

---

### 5️⃣ Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

---

### 6️⃣ Charger les données de test

Les données de test ne sont pas chargées via Doctrine Fixtures dans ce livrable.
Un script SQL dédié est fourni afin de garantir un jeu de données stable et cohérent.
Les scripts SQL sont disponibles dans le dossier /sql à la racine du backend.

Importer le fichier 02_seed.sql depuis un terminal:

```bash
mysql -u root -p nomdeBDD --default-character-set=utf8mb4 < sql\02_seed.sql
```

(Attention pour terminal PowerShell)
```bash
cmd /c "chcp 65001>nul & mysql --default-character-set=utf8mb4 -u root -p nomdeBDD < sql\02_seed.sql"
```
Les données sont fournies via 02_seed.sql contenant des INSERT réels.

L’export a été généré à partir d’une base fonctionnelle afin de garantir la cohérence des contraintes (PK/FK).

Les tables de jointure (ManyToMany) sont alimentées via SQL.

nomdeBDD étant le nom de la BDD défini dans les variables d'environnement.
root étant le profil de connection de base de MySQL il vous sera amener a changé le nom root pour votre configuration MySQL.

---

## ✉️ Gestion des emails

Le projet utilise Symfony Mailer.

Installez Mailpit au préalable cela permetra le bon fonctionnement du site.

(sinon : changez la variable MAILER_DSN=smtp://localhost:1025 en MAILER_DSN=null://null)
(cela permettra a l'application de tourner sans problème mais vous ne constaterez pas l'envoie de mail).

En environnement de développement, les emails sont interceptés par Mailpit.

Lancer Mailpit :

- dans une fenètre PowerShell ou terminal

```bash
CD <CheminDAccesDossierMailpit> 
.\mailpit.exe
```

Interface web disponible sur :

```
http://localhost:8025
```

---

### 7️⃣ Lancer le serveur Symfony

```bash
symfony server:start
```

L’API sera accessible à l’adresse :

```
http://127.0.0.1:8000
```

---

## 👤 Identifiants de démonstration

Les comptes suivants sont présents dans le seed :

### 🔐 Administrateur

- Email : admin@email.com

- Mot de passe : (hashé – voir base de données)

- Rôles :

    - ROLE_USER

    - ROLE_ADMIN

    - ROLE_EMPLOYEE

### 🛠 Employé

- Email : employee@email.com

- Mot de passe : (hashé – voir base de données)

- Rôle :

    - ROLE_EMPLOYEE

### 👤 Utilisateur

- Email : utilisateur@email.com

- Mot de passe : (hashé – voir base de données)

- Rôle :

  - ROLE_USER

### Mot de Passe :

Dans un soucis de sécurité, j'ai volontairement affichés les mots de passe "hashés". Il est nécessaire de garder ces informations secretes.
Cependant lors de la création d'un utilisateur son mot de passe est enregistré et ensuite hashé avant stockage en BDD.

Dans le cadre de ce projet,pour l'utilisation locale, et la correction : j'ai mis les mot de passes dans la copie a rnedre avec l'evaluation. Libre a vous de creez votre compte
user et de créez des comptes Employés avec le compte Admin.

---

## 📚 Documentation API

La documentation Swagger est disponible via :

```
http://127.0.0.1:8000/api/doc
```

Elle permet de tester les endpoints directement depuis le navigateur.

---

## 🔐 Authentification

L’API utilise une authentification par token personnalisée via :

```
ApiTokenAuthenticator.php
```

Le token doit être envoyé dans le header :

```
X-AUTH-TOKEN: votre_token
```

Les rôles disponibles :

- ROLE_USER
- ROLE_EMPLOYEE
- ROLE_ADMIN

Les contrôles d’accès sont réalisés côté backend via `isGranted()`.

---

## 🧠 Fonctionnalités principales

- Inscription utilisateur
- Connexion via token API
- Création de commande
- Gestion du stock
- Historique des commandes
- Dépôt d’avis lié à une commande terminée
- Validation / refus des avis par un employé
- Notifications email automatiques
- Dashboard administrateur avec statistiques MongoDB

---

## 🏛️ Architecture

Le backend est conçu selon une architecture REST :

- Entités Doctrine (MySQL)
- Collections MongoDB (statistiques)
- Contrôleurs API
- Services métier
- Enum pour la gestion des statuts
- Authentification Custom

Le projet respecte une séparation claire entre :

- Logique métier (Backend)
- Interface utilisateur (Frontend SPA)
- Données Transactionnelles (MySQL)
- Données analytiques (MongoDB)

---

## 📦 Environnement de développement recommandé

- PHP 8.4+
- Symfony CLI
- MySQL 8
- MongoDB
- Composer
- Mailpit

---

## 📌 Remarques

Ce projet fait partie d’une architecture Full-Stack.

Le frontend et le backend sont maintenus dans des dépôts séparés.
