SondagePro est une application complète de gestion de sondages en ligne permettant de créer, partager et analyser des sondages en temps réel.

## 🎯 Caractéristiques principales

- **Création de sondages** - Interface intuitive pour créer des sondages avec options multiples
- **Gestion des dates** - Planifiez vos sondages avec dates de début et fin personnalisées
- **Votes en temps réel** - Collectez les votes avec tracking par IP pour éviter les doublons
- **Analyse des résultats** - Visualisez les résultats avec des statistiques détaillées
- **Widget embeddable** - Intégrez vos sondages sur d'autres sites via un code widget
- **Tableau de bord admin** - Gérez tous vos sondages depuis une interface centralisée
- **Export PDF** - Téléchargez les résultats au format PDF
- **Design responsive** - Interface moderne et adaptée à tous les appareils

## 📋 Structure du projet

sondages/
├── public/                      # Fichiers accessibles publiquement
│   ├── index.php               # Page d'accueil
│   ├── creer.php               # Création de sondages
│   ├── voter.php               # Page de vote
│   ├── resultats.php           # Affichage des résultats
│   ├── admin.php               # Tableau de bord administrateur
│   ├── widget.php              # Code d'intégration du widget
│   ├── embed.php               # Widget embeddable
│   ├── export_pdf.php          # Export en PDF
│   ├── config/
│   │   └── database.php        # Configuration base de données
│   ├── includes/
│   │   ├── header.php          # En-tête HTML
│   │   └── footer.php          # Pied de page
│   ├── js/
│   │   ├── main.js             # Scripts principaux
│   │   └── widget.js           # Scripts du widget
│   ├── css/
│   │   └── style.css           # Feuille de styles
│   └── ajax/
│       └── get_results.php     # API AJAX pour résultats en direct
├── app/
│   └── layout.tsx              # Layout Next.js
├── components/
│   ├── ui/                     # Composants UI shadcn/ui
│   └── theme-provider.tsx      # Provider de thème
├── install/
│   └── setup.sql               # Schéma de base de données
├── package.json                # Dépendances npm
└── tsconfig.json               # Configuration TypeScript
```

## 🛠️ Technologies utilisées

### Backend
- **PHP** - Langage backend
- **MySQL/PDO** - Base de données avec PDO
- **Session PHP** - Gestion des sessions utilisateur

### Frontend
- **Next.js 16.1** - Framework React
- **React 19.2** - Bibliothèque UI
- **Tailwind CSS 4.2** - Framework CSS

## 📦 Installation

### Prérequis
- PHP 7.4+
- MySQL 5.7+
- Node.js 18+




2. **Configuration de la base de données**
   - Créez une base de données MySQL nommée `sondages`
   - Importez le fichier `public/install/setup.sql`
```bash
mysql -u root sondages < public/install/setup.sql
```

3. **Configuration PHP**
   - Modifiez `public/config/database.php` avec vos identifiants MySQL
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sondages');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/sondages');
```

4. **Installation des dépendances npm**
```bash
npm install
# ou avec pnpm
pnpm install
```

5. **Lancez le serveur de développement**
```bash
npm run dev
# ou avec pnpm
pnpm dev
```

L'application sera accessible à `http://localhost:3000`

## 🚀 Utilisation

### Créer un sondage
1. Accédez à la page d'accueil
2. Cliquez sur "Nouveau sondage"
3. Remplissez la question et les options de réponse
4. Définissez les dates de début et fin (optionnel)
5. Validez la création

### Voter
1. Sélectionnez un sondage actif
2. Cliquez sur "Voter"
3. Choisissez votre réponse préférée
4. Votre vote est enregistré avec votre IP pour éviter les doublons

### Consulter les résultats
- Cliquez sur "Résultats" pour voir les statistiques détaillées
- Les résultats se mettent à jour en temps réel
- Téléchargez un rapport PDF si désiré

### Intégrer le widget
1. Rendez-vous sur la page du sondage
2. Cliquez sur "Widget"
3. Copiez le code d'intégration
4. Collez-le sur votre site web

## 📊 Schéma de base de données

### Table `sondages`
- `id` - Identifiant unique
- `question` - Question du sondage
- `description` - Description optionnelle
- `date_debut` - Date de début
- `date_fin` - Date de fin (optionnel)
- `actif` - Statut actif/inactif
- `date_creation` - Timestamp de création

### Table `options`
- `id` - Identifiant unique
- `sondage_id` - Référence au sondage
- `texte` - Texte de l'option
- `ordre` - Ordre d'affichage

### Table `votes`
- `id` - Identifiant unique
- `option_id` - Référence à l'option
- `ip_address` - Adresse IP du votant
- `timestamp` - Date/heure du vote

## 🔒 Sécurité

- **Protection IP** - Un vote par IP pour éviter les doublons
- **Validation des données** - Toutes les entrées sont validées et échappées
- **Préparation des requêtes** - Utilisation de requêtes préparées PDO
- **Session PHP** - Gestion sécurisée des sessions utilisateur

## 🎨 Personnalisation

### Couleurs du thème
Modifiez les couleurs dans `public/config/database.php` :
```php
define('COLOR_PRIMARY', '#121e84');
define('COLOR_SECONDARY', '#8f225a');
```

### Styles CSS
Personnalisez l'apparence en éditant `public/css/style.css`

## 📝 Fichiers principaux

| Fichier | Description |
|---------|-------------|
| `public/index.php` | Page d'accueil avec liste des sondages |
| `public/creer.php` | Formulaire de création de sondage |
| `public/voter.php` | Interface de vote |
| `public/resultats.php` | Affichage des résultats |
| `public/admin.php` | Tableau de bord administrateur |
| `public/widget.php` | Générateur de code widget |
| `public/export_pdf.php` | Export des résultats en PDF |
| `public/ajax/get_results.php` | Endpoint AJAX pour résultats temps réel |

## 🐛 Dépannage

### Erreur de connexion à la base de données
- Vérifiez que MySQL est en cours d'exécution
- Vérifiez les identifiants dans `public/config/database.php`
- Assurez-vous que la base de données `sondages` existe

### Les votes ne s'enregistrent pas
- Vérifiez que les tables sont créées (`public/install/setup.sql`)
- Vérifiez les permissions d'écriture sur la base de données
- Contrôlez les logs PHP pour les erreurs

### Problèmes de style CSS
- Videz le cache du navigateur
- Vérifiez que `public/css/style.css` est bien chargé
- Vérifiez la console du navigateur pour les erreurs

