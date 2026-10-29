# SchoolPay

Application de gestion des paiements scolaires pour les parents et les établissements.

- **Backend** : Laravel 11, Sanctum, PHP 8.2+
- **Frontend** : Vue 3, Vue Router, Vite, Tailwind CSS
- **Base de développement** : SQLite
- **Base recommandée en production** : MySQL ou PostgreSQL

## Prérequis

- PHP 8.2 ou supérieur avec les extensions `mbstring`, `pdo_sqlite`/`pdo_mysql`, `openssl`, `curl`, `intl`
- Composer 2
- Node.js 20 ou supérieur et npm
- Git

## Installation locale

### Windows (PowerShell)

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

### Linux / macOS

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

Puis démarrer le serveur Laravel dans un second terminal :

```bash
php artisan serve
```

L'application est disponible sur `http://127.0.0.1:8000`.

> `php artisan migrate --seed` crée des données de démonstration, dont des comptes de test avec des mots de passe faibles. Cette commande est réservée au développement local. En production, utiliser `php artisan migrate` sans `--seed`, puis créer les comptes administrateurs de manière contrôlée.

## Configuration

Le fichier `.env.example` sert de modèle. Les variables importantes :

| Variable | Rôle |
|---|---|
| `APP_ENV` | `local`, `testing` ou `production` |
| `APP_DEBUG` | Doit rester `false` en production |
| `APP_KEY` | Généré avec `php artisan key:generate` |
| `APP_URL` | URL publique de l'application |
| `FRONTEND_URL` | URL publique du frontend utilisée dans les liens envoyés par email (par défaut, `APP_URL`) |
| `DB_CONNECTION` | `sqlite` en local, `mysql` ou `pgsql` en production |
| `MAIL_MAILER` | `log` en local, SMTP en production |
| `QUEUE_CONNECTION` | `database` ou `redis` |
| `RELANCE_JOURS_AVANT` | Nombre de jours avant une échéance pour déclencher une relance (7 par défaut) |
| `RELANCE_COOLDOWN_JOURS` | Délai minimal entre deux relances d’une même facture (7 par défaut) |
| `RELANCE_HEURE` | Heure de l’envoi automatique quotidien des relances (08:00 par défaut) |
| `AUDIT_RETENTION_JOURS` | Durée de conservation du journal d'audit en jours (365 par défaut, minimum 30) |
| `AUDIT_PAYLOAD_MAX_OCTETS` | Taille maximale du payload expurgé conservé par entrée du journal (8192 octets par défaut) |
| `TELEPHONE_INDICATIF` | Indicatif téléphonique par défaut pour les numéros locaux (`+223`) |
| `TELEPHONE_LONGUEUR_MIN` / `TELEPHONE_LONGUEUR_MAX` | Longueur acceptée d'un numéro local (8 à 9 chiffres par défaut) |
| `OTP_LONGUEUR` | Nombre de chiffres du code SMS (6 par défaut) |
| `OTP_VALIDITE_MINUTES` | Durée de validité d'un code SMS (10 minutes par défaut) |
| `OTP_MAX_TENTATIVES` | Nombre maximal de saisies erronées avant blocage (5 par défaut) |
| `OTP_COOLDOWN_SECONDES` | Délai minimal entre deux envois de code (60 secondes par défaut) |
| `OTP_PURGE_JOURS` | Durée de conservation des codes OTP avant purge (7 jours par défaut) |
| `SMS_DRIVER` | `log` en local, `textbelt` ou `twilio` avec un compte externe |
| `TEXTBELT_KEY` | Clé Textbelt (`textbelt` pour le palier gratuit limité à 1 SMS/jour) |
| `TWILIO_ACCOUNT_SID` / `TWILIO_AUTH_TOKEN` / `TWILIO_FROM` | Identifiants du compte Twilio et numéro émetteur |
| `SANCTUM_TOKEN_EXPIRATION` | Durée de vie des tokens API en minutes (10080 = 7 jours) |
| `SANCTUM_STATEFUL_DOMAINS` | Domaines du frontend si vous passez à l'authentification par cookies |
| `PAYMENT_DRIVER` | `simulation` en local, `cinetpay` pour encaisser réellement |
| `PAYMENT_DEVISE` / `PAYMENT_PAYS` | Devise et pays des paiements (`XOF` / `ML`) |
| `PAYMENT_MONTANT_MIN` / `PAYMENT_MONTANT_MAX` | Bornes applicatives en FCFA (1 000 à 2 500 000 par défaut) |
| `PAYMENT_VERIFICATION_COOLDOWN` | Délai minimal entre deux vérifications d'un même paiement (secondes) |
| `PAYMENT_RETOUR_URL` / `PAYMENT_WEBHOOK_URL` | URL publiques de retour et de notification (120 caractères maximum, HTTPS obligatoire en production) |
| `PAYMENT_SIMULATION_DUREE_MINUTES` | Durée de validité du lien signé du guichet simulé (60 minutes par défaut) |
| `CINETPAY_API_KEY` / `CINETPAY_API_PASSWORD` | Identifiants API v1 du compte marchand CinetPay (sandbox `sk_test_...`, production `sk_live_...`) |
| `CINETPAY_ENVIRONNEMENT` | `sandbox` ou `production` |
| `CINETPAY_BASE_URL` | Forçage optionnel de l'URL API (`https://api.cinetpay.net` ou `https://api.cinetpay.co`) |
| `CINETPAY_PAYS` / `CINETPAY_DEVISE` | Pays et devise du compte marchand (`ML` / `XOF`) |
| `CINETPAY_TIMEOUT` / `CINETPAY_CONNECT_TIMEOUT` | Délais HTTP vers CinetPay en secondes |
| `CINETPAY_CANAL` | Canal CinetPay optionnel ; laisser vide pour laisser le guichet choisir |

## Paiements en ligne (CinetPay)

SchoolPay utilise l’**API v1** de CinetPay, sans SDK Composer ni Checkout v2 :

1. `POST /v1/oauth/login` avec `api_key` et `api_password` pour obtenir un jeton ;
2. `POST /v1/payment` pour créer la transaction et récupérer `payment_url` ;
3. `GET /v1/payment/{merchant_transaction_id}` pour relire le statut canonique.

Le montant et la devise enregistrés en base font foi. Un paiement n’est validé qu’après une réponse canonique `SUCCESS` avec un montant et une devise identiques. Un paiement déjà validé ne redescend jamais vers `echoue`, même si un webhook tardif annonce un échec.

### Mode simulation local

Tant que les clés marchand ne sont pas disponibles, `.env` doit contenir :

```dotenv
PAYMENT_DRIVER=simulation
```

Le parent est alors redirigé vers un guichet local **signé et expirant** qui permet de simuler un succès ou un refus. Aucun montant n’est débité et aucune requête n’est envoyée à CinetPay. Le pilote `simulation` refuse de démarrer lorsque `APP_ENV=production`. Le parcours complet (création, confirmation, statut, reçu) reste testable.

### Activation d’un compte CinetPay

1. Créer un compte marchand sur le panel CinetPay, en sélectionnant le **Mali** et la devise **XOF**.
2. Demander l’activation des opérateurs souhaités (Orange Money Mali, Moov Money Mali, carte bancaire).
3. Récupérer les clés **sandbox** (`sk_test_...`) dans le panel, puis les renseigner uniquement dans `.env` :

```dotenv
PAYMENT_DRIVER=cinetpay
CINETPAY_ENVIRONNEMENT=sandbox
CINETPAY_API_KEY=sk_test_...
CINETPAY_API_PASSWORD=...
```

4. Vérifier la configuration :

```bash
php artisan config:clear
php artisan route:list --path=paiements
php artisan test tests/Feature/CinetPayPaymentTest.php
```

5. Tester une initiation réelle depuis l’espace parent, puis vérifier que le statut est relu auprès de l’API et que le reçu se génère après validation.

### Webhook et retour navigateur

- Retour navigateur : `GET|POST /api/paiements/retour`. La référence sert uniquement à retrouver le paiement ; le statut est toujours revérifié via l’API.
- Webhook : `GET|POST /api/paiements/webhook/cinetpay`. Le `notify_token` reçu est comparé en temps constant à celui stocké lors de l’initialisation, puis le statut canonique est relu avant toute mise à jour.
- Les URL de retour et de notification sont limitées à **120 caractères** et doivent être des URL absolues. En production, **HTTPS est obligatoire**.
- En local, CinetPay ne peut pas joindre `http://127.0.0.1`. Pour tester le webhook, exposez l’application via un tunnel HTTPS et renseignez `PAYMENT_WEBHOOK_URL` et `PAYMENT_RETOUR_URL` avec l’URL publique.

### Limites et prérequis

- Montant minimum applicatif : **1 000 XOF** ; plafond CinetPay par transaction : **2 500 000 XOF**.
- Montant obligatoirement **entier** en XOF.
- Email **et** téléphone vérifiés obligatoires avant toute initiation de paiement (middleware + contrôle dans le service).
- La couverture réelle d’Orange Money Mali et de Moov Money Mali dépend de l’activation des opérateurs sur le compte marchand.
- Ne jamais placer `CINETPAY_API_KEY` ou `CINETPAY_API_PASSWORD` dans le frontend, un dépôt Git ou une capture d’écran.

## Tests

Les tests utilisent une base **SQLite en mémoire** définie dans `phpunit.xml`. Ils ne touchent pas à `database/database.sqlite`.

```bash
php artisan test
```

Pour exécuter uniquement les tests fonctionnels :

```bash
php artisan test --testsuite=Feature
```

## Intégration continue

Le workflow `.github/workflows/ci.yml` exécute, sur chaque push et pull request :

1. l'installation des dépendances PHP ;
2. la génération de la clé d'application ;
3. l'installation des dépendances Node ;
4. le build du frontend ;
5. la suite de tests PHPUnit sur PHP 8.2 et 8.3.

## Sécurité et exploitation

- **Limitation de débit** : 5 tentatives de connexion par minute et par couple email/IP, 5 inscriptions par minute et 60 par heure et par IP, 3 demandes de réinitialisation par minute et par couple email/IP et 10 par heure et par IP, 5 tentatives de réinitialisation par minute et par couple email/IP et 20 par minute et par IP, 3 renvois de vérification d'email par minute et 10 par heure et par utilisateur, 120 requêtes API par minute et par utilisateur, 10 paiements par minute et par utilisateur, 10 modifications de profil par minute et par utilisateur, 30 consultations de rapports par minute, 5 exports CSV par minute, 5 campagnes de relances par minute et par utilisateur, 10 générations de reçus par minute et par utilisateur, 30 consultations du journal d’audit par minute et par utilisateur, 30 vérifications publiques de reçu par minute et 300 par heure et par IP, 30 consultations de la configuration de paiement par minute et par IP, 30 retours de paiement par minute et par IP, 120 webhooks CinetPay par minute et 1 200 par heure et par IP, 20 simulations par minute et par IP, et 30 vérifications de statut de paiement par minute et 300 par heure et par utilisateur.
- **Tokens Sanctum** : expiration après 7 jours par défaut, configurable avec `SANCTUM_TOKEN_EXPIRATION`. Les tokens expirés sont purgés chaque jour par `sanctum:prune-expired`.
- **Mots de passe** : 8 caractères minimum avec lettres et chiffres pour l'inscription publique, le profil parent et la réinitialisation ; 12 caractères avec majuscule, minuscule, chiffre et symbole pour les comptes créés par un administrateur, y compris lors d'une réinitialisation de compte administrateur.
- **Routes sensibles** : `/api/all-classes` est réservé au super administrateur, la route de debug `/api/verify-token` a été supprimée et les routes API inconnues renvoient un 404 JSON.
- **Seeders** : `DatabaseSeeder` et `SuperAdminSeeder` refusent de s'exécuter hors de `local` et `testing`.
- **Historique des paiements et factures** : les suppressions d'écoles, de classes, d'élèves ou de parents sont refusées avec un HTTP 409 dès que des élèves, des factures ou des paiements y sont associés. Les clés étrangères des paiements sont en `RESTRICT`, ce qui protège aussi l'historique contre une suppression SQL directe. Les interfaces parent et super administrateur désactivent la suppression d'un élève qui possède déjà des paiements ou une facture.
- **Profil parent** : le parent peut modifier son nom et son téléphone, et changer son mot de passe en fournissant le mot de passe actuel. L'email reste en lecture seule ; un administrateur peut le modifier, ce qui réinitialise la vérification et envoie un nouveau lien. Un changement de mot de passe révoque toutes les sessions existantes et renvoie un nouveau token.
- **Vérification email** : l'inscription et la création d'un parent par un administrateur envoient un lien signé valable 60 minutes. La connexion reste autorisée pour un compte non vérifié, mais l'ajout, la modification et la suppression d'élèves ainsi que les paiements sont bloqués jusqu'à la confirmation. Un paiement exige en outre un téléphone vérifié. Le renvoi est disponible via `POST /api/email/verification-notification`, limité à 3 demandes par minute et 10 par heure et par utilisateur. Les liens invalides ou expirés redirigent vers la page frontend `/email-verified`.
- **Gestion des élèves par le super administrateur** : la page `/superadmin/eleves` et les routes `GET/POST/PUT/DELETE /api/superadmin/eleves` permettent de lister, rechercher, filtrer par école, classe ou parent, paginer, créer, modifier et supprimer les élèves de toutes les écoles. La liste des comptes parents pour le formulaire est exposée par `GET /api/superadmin/parents`. L’élève doit être rattaché à un compte parent, et sa classe doit appartenir à l’école sélectionnée. Le changement de parent et la suppression sont refusés avec un HTTP 409 dès qu’un historique de paiements ou une facture existe.
- **Annuaire super administrateur** : les endpoints `GET /api/superadmin/ecoles`, `/classes`, `/users` et `/paiements` acceptent une recherche, une pagination (`per_page` de 10 à 100) et des filtres dédiés (école, rôle, statut). Sans `per_page`, ils conservent leur réponse historique sous forme de tableau pour les formulaires et les rapports. Le dashboard charge les options avec `all=1` puis affiche des listes paginées et filtrées. Un filtre de paiement combinant une école et une classe incohérentes est refusé en HTTP 422.
- **Code d’invitation école** : chaque école reçoit automatiquement un code unique de 10 caractères. Le parent doit fournir ce code pour ajouter un élève ou changer un élève d'école ; une modification sans changement d'école n'en demande pas. Le code n'est jamais exposé par `GET /api/ecoles`. Le super administrateur peut le consulter et le régénérer via `POST /api/superadmin/ecoles/{id}/regenerate-code`, ce qui invalide immédiatement l'ancien code.
- **Mot de passe oublié** : `POST /api/forgot-password` renvoie toujours une réponse générique afin de ne pas révéler l'existence d'un compte. Le lien envoyé par email expire après 60 minutes et ne peut être utilisé qu'une seule fois. Après une réinitialisation réussie, toutes les sessions Sanctum sont révoquées et le token de réinitialisation est supprimé. Les erreurs d'envoi d'email sont journalisées sans modifier la réponse publique.
- **Emails transactionnels** : en local, `MAIL_MAILER=log` écrit les emails dans les logs. En production, configurer un fournisseur SMTP et `FRONTEND_URL` (ou `APP_URL`) pour que les liens de réinitialisation et de vérification pointent vers le frontend. La durée de vie des liens de vérification est configurable avec `AUTH_VERIFICATION_EXPIRE`.
- **Années scolaires et frais** : la page `/superadmin/frais` et les routes `GET/POST/PUT/DELETE /api/superadmin/annees-scolaires`, `POST /api/superadmin/annees-scolaires/{id}/activer` ainsi que `GET/POST/PUT/DELETE /api/superadmin/frais-scolaires` permettent de définir les années et les barèmes. Une seule année peut être active ; la première créée est activée automatiquement et les périodes ne peuvent pas se chevaucher. Chaque barème est rattaché à une année, une école et une classe cohérente, avec un libellé unique pour ce contexte. L’échéance, facultative, doit rester comprise dans la période de l’année. La suppression d’une année contenant des frais, des factures ou des paiements, la modification de sa période après émission de factures, la réduction d’une période excluant une échéance, ainsi que la suppression ou le déplacement d’une école ou d’une classe liée à des frais sont refusés en HTTP 409. Le parent consulte en lecture seule les barèmes applicables à ses propres enfants via `GET /api/parent/frais-scolaires`, avec filtre par année ou par élève.
- **Factures, échéances et soldes** : la page `/superadmin/factures` et les routes `GET/POST /api/superadmin/factures`, `POST /api/superadmin/factures/generer`, `GET/DELETE /api/superadmin/factures/{id}` permettent de générer et suivre les factures. Une facture snapshot est créée par élève et par année scolaire à partir des barèmes de sa classe ; la génération est idempotente et une facture existante n’est jamais recalculée. Les lignes conservent leur libellé, leur montant et leur échéance même si le barème est ensuite modifié ou supprimé. Le solde est calculé à partir des paiements validés rattachés à l’année, auxquels s’ajoutent les paiements historiques sans année dont la date tombe dans la période. Les statuts de facture sont `impayee`, `partielle` et `payee` ; les statuts d’échéance sont `en_retard`, `proche`, `a_venir`, `sans_echeance` et `soldee`. La suppression d’une facture est refusée en HTTP 409 si un paiement validé y est rattaché. Le parent consulte uniquement les factures et soldes de ses propres enfants via `GET /api/parent/frais-scolaires`.
- **Relances d’échéances par email** : la page `/superadmin/relances` et les routes `GET /api/superadmin/relances-echeances` et `POST /api/superadmin/relances-echeances/envoyer` permettent de consulter le journal et de déclencher une campagne manuelle. Les relances ciblent les factures non soldées ayant une échéance dépassée ou dans les `RELANCE_JOURS_AVANT` prochains jours ; les factures soldées, sans échéance dans la fenêtre, sans destinataire valide ou déjà relancées pendant le délai de refroidissement sont ignorées et comptabilisées. Une seule relance est envoyée par facture et par campagne : le type est `retard` si au moins une échéance est dépassée, sinon `prochaine`, et l’email regroupe les échéances concernées. Le journal `relances_echeances` conserve des snapshots (facture, élève, année, destinataire, échéances, solde, erreur) même si la facture est supprimée. L’envoi s’appuie sur l’infrastructure email existante et reste synchrone : en production, configurer un fournisseur SMTP réactif et surveiller les échecs dans le journal.
- **Reçus de paiement et vérification publique** : les boutons « Reçu » des espaces parent et super administrateur appellent `POST /api/parent/paiements/{id}/recu` ou `POST /api/superadmin/paiements/{id}/recu`. Un reçu snapshot unique est créé par paiement validé avec l’élève, l’école, la classe, l’année, le montant, le mode, la date et la référence ; il reste inchangé après un renommage. La génération est idempotente et refusée en HTTP 409 pour un paiement non validé sans reçu existant. La page publique `/recus` permet de saisir un code, puis `/recus/:code` affiche un reçu imprimable. La route `GET /api/recus/{code}` est publique, limitée à 30 requêtes par minute et 300 par heure et par IP, et n’expose jamais les coordonnées du parent. Si le paiement est ensuite remis en attente ou marqué échoué, le reçu reste consultable mais est affiché comme non valide.
- **Vérification du téléphone par OTP** : l’espace parent permet d’envoyer un code à 6 chiffres par SMS puis de vérifier le numéro. Les numéros locaux sont normalisés en E.164 avec l’indicatif configuré (`+223` par défaut). Le code est haché, valable 10 minutes, limité à 5 tentatives et à un usage unique ; un nouveau code invalide les précédents et le renvoi est soumis à un cooldown de 60 secondes. Les endpoints `GET /api/parent/telephone/statut`, `POST /api/parent/telephone/envoyer-code` et `POST /api/parent/telephone/verifier-code` sont limités à 3 envois par minute et 10 par heure, et à 10 vérifications par minute et 60 par heure. Toute modification du numéro via le profil parent ou l’administration réinitialise `telephone_verified_at` et invalide les codes en attente. Le pilote `log` écrit le code dans les logs et reste réservé au développement ; il est refusé en production. Les codes sont purgés chaque jour à 03:30 après `OTP_PURGE_JOURS` (7 jours par défaut). Un email **et** un téléphone vérifiés sont obligatoires avant toute initiation de paiement, y compris en mode simulation.
- **Journal d'audit super administrateur** : la page `/superadmin/journal` et les routes `GET /api/superadmin/journal` et `GET /api/superadmin/journal/{id}` permettent de consulter les actions administratives sensibles. Toutes les requêtes `POST`, `PUT`, `PATCH` et `DELETE` sous `/api/superadmin` sont enregistrées après la réponse HTTP, y compris les refus de validation (422), d’autorisation ou de conflit (403/409) et les erreurs serveur. Chaque entrée conserve un snapshot du nom et de l’email de l’administrateur, l’action, la méthode, le chemin, le statut HTTP, l’IP, le user-agent et le payload expurgé ; les mots de passe, tokens et codes secrets ne sont jamais conservés en clair. Si le compte de l’administrateur est supprimé, `user_id` devient nul mais les snapshots restent lisibles. La liste est paginée et filtrable par recherche, utilisateur, action, méthode, statut HTTP, résultat et période ; le payload n’est exposé que dans le détail. Les consultations sont limitées à 30 requêtes par minute et par utilisateur. Une erreur d’écriture du journal est signalée dans les logs mais ne bloque jamais l’action métier. La purge quotidienne s’exécute à 03:15 et refuse une rétention inférieure à 30 jours.
- **Rapports de paiement** : l'espace `/superadmin/rapports` est réservé au super administrateur. Il permet de filtrer les paiements par école, classe, statut et période, d'afficher les totaux et les répartitions, puis d'exporter le résultat en CSV. L'export est authentifié, limité en débit et neutralise les formules tableur pour éviter les injections CSV.
- **Dépendances frontend** : les vulnérabilités restantes de `npm audit` concernent l'outil de build Vite 5/esbuild. Le serveur `npm run dev` ne doit jamais être exposé publiquement. La correction définitive nécessite une montée majeure de Vite.

### Créer un administrateur en production

Ne pas utiliser les seeders de démonstration en production. Utiliser la commande interactive :

```bash
php artisan schoolpay:create-admin
```

La commande demande le nom, l'email et un mot de passe fort, puis crée ou met à jour le compte super administrateur. Les sessions existantes de ce compte sont révoquées.

### Tâches planifiées

En local :

```bash
php artisan schedule:work
```

En production, ajouter la tâche cron suivante :

```cron
* * * * * cd /chemin/vers/schoolpay && php artisan schedule:run >> /dev/null 2>&1
```

La commande planifiée `schoolpay:relancer-echeances` envoie automatiquement les relances chaque jour à `RELANCE_HEURE`. Elle est protégée contre les chevauchements et un verrou par année empêche deux campagnes simultanées. Pour un envoi manuel :

```bash
php artisan schoolpay:relancer-echeances
php artisan schoolpay:relancer-echeances --annee=1 --ecole=2 --classe=3
```

Sans `--annee`, la commande utilise l’année scolaire active. Les options `--ecole` et `--classe` limitent la campagne à un périmètre précis ; la classe doit appartenir à l’école indiquée.

La commande planifiée `schoolpay:purger-journal-audit` supprime chaque jour à 03:15 les entrées du journal d’audit au-delà de `AUDIT_RETENTION_JOURS` (365 jours par défaut). Elle refuse une rétention inférieure à 30 jours. Pour contrôler ou ajuster manuellement la purge :

```bash
php artisan schoolpay:purger-journal-audit --dry-run
php artisan schoolpay:purger-journal-audit --jours=180
```

La commande `schoolpay:purger-otp-telephone` supprime chaque jour à 03:30 les codes OTP et adresses IP au-delà de `OTP_PURGE_JOURS` (7 jours par défaut) :

```bash
php artisan schoolpay:purger-otp-telephone --dry-run
php artisan schoolpay:purger-otp-telephone --jours=3
```

Pour tester l’OTP en local, conserver `SMS_DRIVER=log` : le code est écrit dans `storage/logs/laravel.log` et affiché dans l’interface parent. Pour un envoi réel, configurer `SMS_DRIVER=textbelt` avec `TEXTBELT_KEY` (1 SMS gratuit par jour avec la clé `textbelt`) ou `SMS_DRIVER=twilio` avec les identifiants du compte. La couverture des numéros maliens et les restrictions du palier gratuit doivent être vérifiées auprès du fournisseur avant la production.

## Structure principale

```text
app/Console/Commands/     Commandes de création d'administrateur, de relances et de purge (audit, OTP)
app/Http/Controllers/     Contrôleurs API
app/Http/Middleware/      Contrôle des rôles, de la vérification email et du journal d'audit
app/Models/               Modèles Eloquent
app/Services/             Facturation, soldes, relances, reçus, téléphone et envoi SMS
app/Notifications/        Notifications email (compte et relances d’échéances)
database/factories/       Fabriques utilisées par les tests
database/migrations/      Schéma de la base
resources/js/views/       Écrans Vue (connexion, parent, superadmin)
routes/api.php            Routes API
tests/Feature/            Tests fonctionnels et d'autorisation
```

## Sécurité avant mise en production

- Passer `APP_ENV=production` et `APP_DEBUG=false`.
- Utiliser MySQL ou PostgreSQL plutôt que SQLite.
- Configurer un vrai fournisseur SMTP et un worker de queue.
- Configurer un fournisseur SMS réel (`SMS_DRIVER=textbelt` ou `twilio`) : le pilote `log` est refusé en production.
- Vérifier la couverture des numéros maliens (`+223`) et les quotas du fournisseur SMS avant l’activation des paiements réels.
- Créer le premier administrateur avec `php artisan schoolpay:create-admin`.
- Changer ou supprimer tous les comptes de démonstration.
- Configurer le cron de `php artisan schedule:run` pour la purge des tokens, les relances quotidiennes, la purge du journal d’audit et la purge des codes OTP.
- Vérifier l’envoi des relances dans le journal après le premier passage planifié et ajuster `RELANCE_HEURE`, `RELANCE_JOURS_AVANT` et `RELANCE_COOLDOWN_JOURS` si nécessaire.
- Ajuster `AUDIT_RETENTION_JOURS` selon la politique de conservation, vérifier la purge du journal d’audit et restreindre l’accès aux comptes super administrateur.
- Activer HTTPS, HSTS et un CORS restrictif.
- Sauvegarder la base avant chaque migration.
- **Paiements en ligne** : `PAYMENT_DRIVER=simulation` en local tant que les clés marchand ne sont pas disponibles, puis `PAYMENT_DRIVER=cinetpay` avec les identifiants API v1 renseignés dans `.env`. Le pilote `simulation` est refusé en production.
- Vérifier que `PAYMENT_RETOUR_URL` et `PAYMENT_WEBHOOK_URL` sont des URL HTTPS publiques de moins de 120 caractères et joignables depuis Internet.
- Tester un paiement sandbox de bout en bout (initiation, guichet, retour, webhook, statut et reçu) avant de passer en production.
- Vérifier l’activation des opérateurs Mobile Money sur le compte marchand CinetPay et les plafonds applicables.
- Ne jamais placer de clé de prestataire de paiement dans le frontend.
- Faire une revue de sécurité dédiée avant d'activer les paiements réels.

## État du projet

L’intégration CinetPay **API v1** est implémentée : initiation, redirection, retour navigateur, webhook signé, vérification canonique du statut et anti-régression. Le mode `simulation` permet de tester le parcours complet sans clé marchand ; le mode `cinetpay` nécessite un compte marchand et des clés API renseignées dans `.env`. Le checkout réel en sandbox reste à valider dès réception des identifiants.

La vérification d'email, la vérification du téléphone par code SMS, le code d'invitation école, la gestion paginée des élèves, l’annuaire paginé du super administrateur, les années scolaires, les barèmes de frais, les factures snapshots, les échéances et les soldes, les relances d’échéances par email, les reçus de paiement imprimables et leur vérification publique, le journal d’audit des actions administratives et les paiements en ligne CinetPay sont implémentés. Les rapports de paiement et la récupération de mot de passe oublié sont disponibles pour les comptes existants. Les soldes sont calculés à partir des paiements validés. L’OTP téléphone est implémenté et bloque désormais l’initiation d’un paiement tant que le numéro n’est pas vérifié.
