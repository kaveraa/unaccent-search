# Contribuer / Contributing

**Français** - [English](#english)

## Français

Merci de votre aide ! Toute modification passe par une **Pull Request** : la branche `main` est protégée et la CI doit être verte pour fusionner.

### 1. Préparer le projet

```bash
git clone https://github.com/kaveraa/unaccent-search.git
cd unaccent-search
composer install
```

Il faut PHP 8.2 ou plus, avec les extensions `mbstring` et `pdo_sqlite`.

### 2. Créer une branche

```bash
git checkout -b fix/nom-court-du-changement
```

Préfixes conseillés : `feat/` (nouveauté), `fix/` (correction), `docs/` (documentation).

### 3. Lancer les tests

```bash
composer test
```

Par défaut, les tests tournent sur SQLite. Pour tester les autres bases avec Docker :

```bash
docker compose up -d
UNACCENT_DB=mysql UNACCENT_DB_PORT=33306 composer test
UNACCENT_DB=mariadb UNACCENT_DB_PORT=33307 composer test
UNACCENT_DB=pgsql UNACCENT_DB_PORT=35432 composer test
```

La CI lance aussi les tests sur les 4 bases et sur PHP 8.2, 8.3 et 8.4.

### 4. Règles du projet

- **Tests** : toute correction ou nouveauté est accompagnée d'un test. Un cas de recherche qui doit marcher pour Laravel et Doctrine va dans `tests/Support/Dataset.php`.
- **Documentation** : mettez à jour `README.md` (français) **et** `README.en.md` (anglais simple), ainsi que le `CHANGELOG.md` (section en haut, en français et en anglais).
- **Commits** : en anglais simple, compréhensible par un débutant. Phrases courtes, pas de jargon.
- **Caractères** : uniquement des caractères du clavier dans les fichiers et les commits : `-` (pas de tiret long), `"` (pas de guillemets français), `->` (pas de flèche), pas d'emoji ni d'icône. Les lettres accentuées du français sont acceptées.
- **Compatibilité** : le code de `src/` doit rester compatible PHP 8.2 et ne dépendre d'aucun framework en dehors des dossiers `Laravel/`, `Doctrine/` et `Symfony/`.

### 5. Ouvrir la Pull Request

Poussez votre branche, ouvrez une PR vers `main` et remplissez la checklist proposée. La PR peut être fusionnée quand le contrôle **All tests passed** est vert.

### Publier une version (mainteneur)

Après la fusion : mettre à jour le `CHANGELOG.md`, puis créer un tag `vX.Y.Z` sur `main`. Packagist publie la version automatiquement.

---

## English

Thank you for your help! Every change goes through a **Pull Request**: the `main` branch is protected, and the CI must be green before merge.

### 1. Set up the project

```bash
git clone https://github.com/kaveraa/unaccent-search.git
cd unaccent-search
composer install
```

You need PHP 8.2 or more, with the `mbstring` and `pdo_sqlite` extensions.

### 2. Create a branch

```bash
git checkout -b fix/short-name-of-the-change
```

Suggested prefixes: `feat/` (new feature), `fix/` (bug fix), `docs/` (documentation).

### 3. Run the tests

```bash
composer test
```

By default, the tests use SQLite. To test the other databases with Docker:

```bash
docker compose up -d
UNACCENT_DB=mysql UNACCENT_DB_PORT=33306 composer test
UNACCENT_DB=mariadb UNACCENT_DB_PORT=33307 composer test
UNACCENT_DB=pgsql UNACCENT_DB_PORT=35432 composer test
```

The CI also runs the tests on the 4 databases and on PHP 8.2, 8.3 and 8.4.

### 4. Project rules

- **Tests**: every fix or new feature comes with a test. A search case that must work with Laravel and Doctrine goes in `tests/Support/Dataset.php`.
- **Documentation**: update `README.md` (French) **and** `README.en.md` (simple English), and the `CHANGELOG.md` (section at the top, in French and English).
- **Commits**: in simple English, easy to read for a beginner. Short sentences, no jargon.
- **Characters**: only keyboard characters in files and commits: `-` (no long dash), `"` (no French quotes), `->` (no arrow), no emoji or icon. French accented letters are fine.
- **Compatibility**: the code in `src/` must stay compatible with PHP 8.2 and must not use a framework outside the `Laravel/`, `Doctrine/` and `Symfony/` folders.

### 5. Open the Pull Request

Push your branch, open a PR to `main` and fill in the checklist. The PR can be merged when the **All tests passed** check is green.

### Release a version (maintainer)

After the merge: update the `CHANGELOG.md`, then create a `vX.Y.Z` tag on `main`. Packagist publishes the version automatically.
