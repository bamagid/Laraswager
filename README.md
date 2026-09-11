# bamagid/laraswagger

## 🌍 Documentation Multilingue

[ENGLISH](#english) | [FRANÇAIS](#français)

## ENGLISH

## Introduction

`bamagid/laraswagger` is a Laravel package that automatically generates Swagger/OpenAPI documentation for your API. It reads your routes, controllers, and validation rules, and turns them into a documentation page — no YAML to write, no annotations to maintain by hand.

### 🎉 Features

- **Automatic documentation**: your API docs stay in sync with your code, with no extra commands to run
- **Reads your existing validation**: `FormRequest` classes and inline `$request->validate([...])` calls are turned into request body schemas
- **Custom summaries**: describe an endpoint with a simple doc comment
- **Real-time updates**: regenerated automatically on every request by default

### ✅ Requirements

- PHP 8.1+
- Laravel 10 and above (any future major version included — the constraint isn't capped)

### 📦 Installation

```bash
composer require bamagid/laraswagger
```

That's it — no service provider to register, no `.env` changes required. The package works out of the box.

## 🛠️ How to use it

### 1. Generating the documentation

By default, the documentation regenerates automatically on every request (see [Controlling when it regenerates](#controlling-when-it-regenerates) to change this). To generate it manually at any time:

```bash
php artisan swagger:generate
```

This writes the OpenAPI spec to `public/api-docs/api-docs.json` and copies the Swagger UI assets alongside it.

### 2. Viewing the documentation

Once generated, the documentation is browsable at:

```
/api/documentation
```

This route and page are registered directly by the package — you don't need an `api.php` routes file for it to work, and it won't touch yours. If you want to customize the page itself, publish it and edit your own copy:

```bash
php artisan vendor:publish --tag=laraswagger-views
```

### 3. Adding a summary to an endpoint

Document what an endpoint does with a `@summary` line in its doc comment:

```php
/**
 * @summary Creates a new article
 */
public function store(Request $request)
{
    // ...
}
```

That text shows up next to the endpoint in the generated documentation.

### 4. Excluding a route from the documentation

Add `@swagger-ignore` to a method's doc comment to leave it out entirely — handy for internal or admin-only endpoints you don't want in the public spec:

```php
/**
 * @swagger-ignore
 */
public function destroy(int $id)
{
    // Not documented.
}
```

### 5. How request bodies get their fields

For `POST`, `PUT` and `PATCH` routes, the generator figures out the request body schema in this order:

1. **A `FormRequest` type-hinted on the action.** Your `rules()` method is called as-is, so whatever it returns is what gets documented.
2. **An inline validation call written directly in the method** — `$request->validate([...])`, `Validator::make($data, [...])`, or a `$rules = [...]` array passed to `validate()`. This is read without running your controller code, so it works safely even outside of a real HTTP request.
3. **Neither of the above? Falls back to your database.** The generator guesses the table from the controller's name (`ArticleController` → `articles`) and documents its columns instead. Columns Laravel manages itself — `id`, `created_at`, `updated_at`, `deleted_at`, `remember_token`, `email_verified_at` — are always left out, since an API client never submits those.

**What rules are understood:** all the standard ones (`required`, `string`, `integer`, `between:`, `in:`, `date`, `mimes:`, ...), plus the common `Rule::` helpers (`Rule::in()`, `Rule::unique()`, `Rule::exists()`, `Rule::enum()`, `Rule::email()`, `Rule::file()`, and similar) when they come from a `FormRequest`.

**If a rule can't be understood** — a custom rule class, a closure, or something built dynamically at runtime — the field is simply documented as a generic string instead. Nothing breaks and nothing is skipped; you just get a slightly less precise type for that one field. Prefer literal rule arrays or a `FormRequest` if you want everything typed exactly.

### 6. Controlling when it regenerates

By default, the documentation regenerates automatically after every artisan command runs. To turn that off and regenerate only when you run `swagger:generate` yourself, add this to your `.env`:

```env
AUTO_GENERATE_DOCS=false
```

### 7. Customizing the title and description

The generated spec's title and description come from your `.env` by default:

```env
APP_NAME=My API
APP_DESCRIPTION=The description of your API
```

For more control, publish the config file and edit it directly:

```bash
php artisan vendor:publish --tag=laraswagger-config
```

```php
// config/laraswagger.php
return [
    'title' => env('APP_NAME', config('app.name')),
    'description' => env('APP_DESCRIPTION', ''),
    'auto_generate' => env('AUTO_GENERATE_DOCS', true),
];
```

### 8. When something can't be documented

Most of the time, `swagger:generate` finishes without saying anything. It only speaks up when a route or field is left genuinely **undocumented** — for example, a controller that no longer exists, or a whole validation array built dynamically at runtime that can't be read:

```
⚠ 1 avertissement(s) bloquant(s) rencontré(s) pendant la génération de la documentation.

 ➜ UserController::store
    Un appel de validation a été trouvé, mais son tableau de règles est construit dynamiquement (variable) et ne peut pas être lu sans exécuter de code.
    → Utilisez un tableau littéral ou un FormRequest.
```

Each entry names the exact controller/method, the field involved, and a short fix — the rest of your documentation is still generated normally. If [`laravel/prompts`](https://github.com/laravel/prompts) is installed (`composer require laravel/prompts`), this renders as a responsive table instead of the plain block above; it's entirely optional.

If something truly unexpected happens, the command prints a short error (type, message, and where it occurred) and exits with a non-zero status, instead of a raw stack trace. Run with `-v` to see the full trace.

## FRANÇAIS

## Introduction

`bamagid/laraswagger` est un package Laravel qui génère automatiquement la documentation Swagger/OpenAPI de votre API. Il lit vos routes, vos contrôleurs et vos règles de validation, et en fait une page de documentation — sans YAML à écrire, sans annotations à maintenir à la main.

### 🎉 Fonctionnalités

- **Documentation automatique** : votre doc reste synchronisée avec votre code, sans commande supplémentaire
- **Lecture de vos validations existantes** : les classes `FormRequest` et les appels `$request->validate([...])` inline sont transformés en schémas de requête
- **Résumés personnalisables** : décrivez un endpoint avec un simple commentaire
- **Mises à jour en temps réel** : régénérée automatiquement à chaque requête par défaut

### ✅ Prérequis

- PHP 8.1+
- Laravel 10 et supérieur (toutes les majeures futures incluses — la contrainte n'a pas de plafond)

### 📦 Installation

```bash
composer require bamagid/laraswagger
```

C'est tout — aucun service provider à enregistrer, aucune modification du `.env` requise. Le package fonctionne dès l'installation.

## 🛠️ Comment l'utiliser

### 1. Générer la documentation

Par défaut, la documentation est régénérée automatiquement à chaque requête (voir [Contrôler quand elle se régénère](#6-contrôler-quand-elle-se-régénère) pour changer ce comportement). Pour la générer manuellement à tout moment :

```bash
php artisan swagger:generate
```

Cela écrit la spécification OpenAPI dans `public/api-docs/api-docs.json` et copie les assets de Swagger UI à côté.

### 2. Voir la documentation

Une fois générée, la documentation est consultable à l'adresse :

```
/api/documentation
```

Cette route et cette page sont enregistrées directement par le package — vous n'avez pas besoin d'un fichier `routes/api.php` pour que ça fonctionne, et le package ne touchera pas au vôtre. Pour personnaliser la page elle-même, publiez-la et modifiez votre propre copie :

```bash
php artisan vendor:publish --tag=laraswagger-views
```

### 3. Ajouter un résumé à un endpoint

Décrivez ce que fait un endpoint avec une ligne `@summary` dans son commentaire :

```php
/**
 * @summary Crée un nouvel article
 */
public function store(Request $request)
{
    // ...
}
```

Ce texte apparaît à côté de l'endpoint dans la documentation générée.

### 4. Exclure une route de la documentation

Ajoutez `@swagger-ignore` au commentaire d'une méthode pour l'exclure entièrement — pratique pour les endpoints internes ou réservés aux admins que vous ne voulez pas dans la spec publique :

```php
/**
 * @swagger-ignore
 */
public function destroy(int $id)
{
    // Non documenté.
}
```

### 5. D'où viennent les champs du corps de requête

Pour les routes `POST`, `PUT` et `PATCH`, le générateur détermine le schéma du corps de requête dans cet ordre :

1. **Un `FormRequest` typé sur l'action.** Votre méthode `rules()` est appelée telle quelle, donc tout ce qu'elle retourne est documenté.
2. **Un appel de validation inline écrit directement dans la méthode** — `$request->validate([...])`, `Validator::make($data, [...])`, ou un tableau `$rules = [...]` passé à `validate()`. C'est lu sans exécuter votre code de contrôleur, donc ça fonctionne même en dehors d'une vraie requête HTTP.
3. **Ni l'un ni l'autre ? Repli sur votre base de données.** Le générateur devine la table à partir du nom du contrôleur (`ArticleController` → `articles`) et documente ses colonnes à la place. Les colonnes gérées par Laravel lui-même — `id`, `created_at`, `updated_at`, `deleted_at`, `remember_token`, `email_verified_at` — sont toujours exclues, puisqu'un client API ne les soumet jamais.

**Règles comprises :** toutes les règles standard (`required`, `string`, `integer`, `between:`, `in:`, `date`, `mimes:`, ...), plus les principaux helpers `Rule::` (`Rule::in()`, `Rule::unique()`, `Rule::exists()`, `Rule::enum()`, `Rule::email()`, `Rule::file()`, et similaires) quand ils viennent d'un `FormRequest`.

**Si une règle n'est pas comprise** — une classe de règle personnalisée, une closure, ou quelque chose construit dynamiquement à l'exécution — le champ est simplement documenté comme une chaîne générique à la place. Rien ne casse et rien n'est ignoré ; vous obtenez juste un type un peu moins précis pour ce champ-là. Préférez des tableaux de règles littéraux ou un `FormRequest` si vous voulez que tout soit typé exactement.

### 6. Contrôler quand elle se régénère

Par défaut, la documentation se régénère automatiquement après chaque commande artisan. Pour désactiver ça et ne la régénérer que lorsque vous lancez `swagger:generate` vous-même, ajoutez ceci à votre `.env` :

```env
AUTO_GENERATE_DOCS=false
```

### 7. Personnaliser le titre et la description

Le titre et la description de la spec générée viennent de votre `.env` par défaut :

```env
APP_NAME=Mon API
APP_DESCRIPTION=La description de votre API
```

Pour plus de contrôle, publiez le fichier de configuration et modifiez-le directement :

```bash
php artisan vendor:publish --tag=laraswagger-config
```

```php
// config/laraswagger.php
return [
    'title' => env('APP_NAME', config('app.name')),
    'description' => env('APP_DESCRIPTION', ''),
    'auto_generate' => env('AUTO_GENERATE_DOCS', true),
];
```

### 8. Quand quelque chose ne peut pas être documenté

La plupart du temps, `swagger:generate` termine sans rien afficher de particulier. Il ne se manifeste que lorsqu'une route ou un champ reste réellement **non documenté** — par exemple un contrôleur qui n'existe plus, ou un tableau de validation entier construit dynamiquement à l'exécution et impossible à lire :

```
⚠ 1 avertissement(s) bloquant(s) rencontré(s) pendant la génération de la documentation.

 ➜ UserController::store
    Un appel de validation a été trouvé, mais son tableau de règles est construit dynamiquement (variable) et ne peut pas être lu sans exécuter de code.
    → Utilisez un tableau littéral ou un FormRequest.
```

Chaque entrée indique le contrôleur/la méthode exacts, le champ concerné, et une correction courte — le reste de votre documentation est généré normalement. Si [`laravel/prompts`](https://github.com/laravel/prompts) est installé (`composer require laravel/prompts`), ce rendu devient un tableau adaptatif au lieu du bloc ci-dessus ; c'est entièrement optionnel.

Si quelque chose de vraiment imprévu se produit, la commande affiche une erreur courte (type, message, et où ça s'est produit) et se termine avec un code de sortie non nul, plutôt qu'une pile d'appels brute. Lancez avec `-v` pour voir la pile complète.
