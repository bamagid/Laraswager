# bamagid/laraswagger

## 🌍 Documentation Multilingue

[ENGLISH](#english) | [FRANÇAIS](#français)

## ENGLISH

## Introduction

`bamagid/laraswagger` is a Laravel package designed to automate the generation of Swagger documentation. Once installed, it requires no additional configuration. This package ensures that your API documentation is always up to date with minimal effort.

### 🎉 Features

- **Automatic Documentation**: Generate API documentation seamlessly without running extra commands
- **Customizable Descriptions**: Add custom descriptions to your endpoints via comments
- **Real-time Updates**: Keep your documentation updated in real-time (default behavior)

### 📦 Installation

```bash
composer require bamagid/laraswagger
```

### ⚙️ Configuration

No additional setup is required post-installation. The package works out of the box, using sensible defaults from your `.env` file — it no longer writes to `.env` for you.

```env
APP_NAME=My API
APP_DESCRIPTION=The description of your API
AUTO_GENERATE_DOCS=true
```

If you need more control, publish the config file and edit `config/laraswagger.php` directly:

```bash
php artisan vendor:publish --tag=laraswagger-config
```

```php
return [
    'title' => env('APP_NAME', config('app.name')),
    'description' => env('APP_DESCRIPTION', ''),
    'auto_generate' => env('AUTO_GENERATE_DOCS', true),
];
```

### 🛠️ Usage

#### Adding Descriptions to Endpoints

To document an endpoint, use a @summary comment above the corresponding function:

```php
/**
 * @summary This endpoint performs a specific action
 * Additional comments can go here.
 */
public function exampleFunction() {
    // Your code
}
```

#### Excluding a route from the documentation

Add `@swagger-ignore` to a method's doc comment to skip it entirely — useful for internal/admin-only endpoints you don't want in the public spec:

```php
/**
 * @swagger-ignore
 */
public function destroy(int $id)
{
    // Not documented.
}
```

#### Environment Variable

Control real-time documentation updates with the `AUTO_GENERATE_DOCS` environment variable:

- `true` (default): Real-time documentation updates
- `false`: Manual documentation generation

Add to your .env file:

```env
AUTO_GENERATE_DOCS=true
```

#### Command

When `AUTO_GENERATE_DOCS` is set to false, generate documentation using:

```bash
php artisan swagger:generate
```

#### Understanding validation rules

`swagger:generate` reads your validation rules from two sources, checked in this order:

1. **A `FormRequest` type-hinted on the action** — any class extending `Illuminate\Foundation\Http\FormRequest`, whatever its name (`StoreUserRequest`, `UpdateArticleRequest`, ...). `rules()` is actually called (it's your own code, running normally), so anything it can compute, the generator sees. If `rules()` throws (for example because it relies on `$this->route()` or `auth()` outside of a real HTTP request), that specific route is reported in the warning table instead of crashing the whole command.
2. **An inline `$request->validate([...])`, `Validator::make($data, [...])`, or `$rules = [...]; $request->validate($rules);` written as a literal array** in the method body. This path is read statically (via `nikic/php-parser`) — your controller code is *not* executed — because these can appear in *any* method, not just ones behind a `FormRequest`.

All standard Laravel validation rules (`required`, `string`, `integer`, `between:`, `in:`, `date`, `mimes:`, ...) are mapped to their OpenAPI type/format/constraint equivalent.

**`Rule` objects** coming from a `FormRequest` are real PHP objects at that point, so the generator resolves the ones it can:

- `Rule::in()`, `Rule::notIn()`, `Rule::unique()`, `Rule::exists()`, `Rule::date()`, `Rule::numeric()`, `Rule::array()`, `Rule::dimensions()`, `Rule::requiredIf()`, `Rule::excludeIf()`, `Rule::prohibitedIf()` — converted via their own `__toString()` (Laravel's own mechanism) and parsed like any other rule string.
- `Rule::enum(SomeEnum::class)` — read via reflection to list the enum's actual cases as the schema's `enum`.
- `Rule::email()` and `Rule::file()` / `Rule::imageFile()` — recognized directly (`format: email` / `format: binary`).
- `Rule::can(...)`, `Rule::when(...)`, `Rule::unless(...)`, `Rule::forEach(...)`, and `Password::...()` rules are authorization/conditional/composite logic with no fixed type to infer: they fall back to `"type": "string"` with a warning, same as a genuinely custom rule.

The same `Rule::...()` calls written *inline* inside `$request->validate([...])` can't be resolved this way — evaluating them would mean executing your code — so they're always reported as a warning there, with a suggestion to move that validation into a `FormRequest`.

**Closures and other rule objects with custom logic** (a rule written as an inline closure, or a custom `Rule`/`ValidationRule` class without a meaningful `__toString()`) genuinely can't be turned into a type: the field falls back to `"type": "string"` and a warning names exactly which field and controller/method it came from, rather than the whole command crashing.

Rules built dynamically at runtime and passed to `validate()` (assigned from a variable that isn't itself a literal array, computed in another method, etc.) can't be read without executing your code either, so they are reported as a warning and skipped for that field. Prefer literal rule arrays or a `FormRequest` to keep a route fully documentable.

#### Troubleshooting generation errors

If something couldn't be documented, `swagger:generate` prints a summary table instead of a generic error, naming exactly what happened and what to do about it:

```
⚠ 2 avertissement(s) rencontré(s) pendant la génération de la documentation :
+-----------------------------+--------+---------------------------------------------+------------------------------------------------+
| Contrôleur::méthode / Route | Champ  | Problème                                     | Recommandation                                  |
+-----------------------------+--------+-----------------------------------------------+------------------------------------------------+
| ArticleController::store    | phone  | Règle de validation non reconnue: phone_be   | ... le champ est documenté en "string" par défaut. |
| UserController::store       | -      | ... construit dynamiquement (variable) ...   | Passez un tableau littéral, ou utilisez un FormRequest. |
+-----------------------------+--------+-----------------------------------------------+------------------------------------------------+
```

Each row names the controller/method, the field involved, the precise reason, and a concrete fix — the documentation is still generated for every other route even when some fields couldn't be resolved.

#### Accessing the Documentation

The generated Swagger documentation is available at the following route:

```bash
/api/documentation
```

This page and route are registered directly by the package's service provider (it doesn't modify your `routes/api.php` or copy files into `resources/views` anymore) — it works whether or not you have an `api.php` routes file. To customize the page, publish it and edit the copy:

```bash
php artisan vendor:publish --tag=laraswagger-views
```

## FRANÇAIS

## Introduction

`bamagid/laraswagger` est un package Laravel conçu pour automatiser la génération de documentation Swagger. Une fois installé, il ne nécessite aucune configuration supplémentaire. Ce package garantit que votre documentation API reste toujours à jour avec un minimum d'effort.

### 🎉 Fonctionnalités

- **Documentation automatique** : Génération de documentation API sans commandes supplémentaires
- **Descriptions personnalisables** : Ajout de descriptions aux endpoints via commentaires
- **Mises à jour en temps réel** : Documentation automatiquement mise à jour (par défaut)

### 📦 Installation

```bash
composer require bamagid/laraswagger
```

### ⚙️ Configuration

Aucune configuration supplémentaire requise après l'installation : le package utilise les valeurs de votre fichier `.env` par défaut, et n'y écrit plus automatiquement.

```env
APP_NAME=Mon API
APP_DESCRIPTION=La description de votre API
AUTO_GENERATE_DOCS=true
```

Pour plus de contrôle, publiez le fichier de configuration et modifiez `config/laraswagger.php` :

```bash
php artisan vendor:publish --tag=laraswagger-config
```

### 🛠️ Utilisation

#### Description des endpoints

```php
/**
 * @summary Cet endpoint exécute une action spécifique
 * D'autres commentaires peuvent être ajoutés ici.
 */
public function exempleFunction() {
    // Votre code
}
```

#### Variable d'environnement

```env
AUTO_GENERATE_DOCS=true
```

#### Commande

```bash
php artisan swagger:generate
```

#### Comprendre l'analyse des règles de validation

`swagger:generate` lit vos règles de validation de façon statique (il n'exécute jamais le code de vos contrôleurs), dans cet ordre :

1. Un `FormRequest` typé sur l'action (`rules()` est appelé directement).
2. Un `$request->validate([...])`, `Validator::make($data, [...])`, ou `$rules = [...]; $request->validate($rules);` **écrit comme un tableau littéral** dans le corps de la méthode.

Toutes les règles de validation standard de Laravel (`required`, `string`, `integer`, `between:`, `in:`, `date`, `mimes:`, ...) sont converties vers le type/format/contrainte OpenAPI correspondant. Une règle non reconnue (règle personnalisée, objet `Rule::...()`) n'est pas une erreur fatale : le champ est documenté en `"type": "string"` et un avertissement est ajouté au tableau récapitulatif, plutôt que de faire planter toute la commande.

Pour garder une route documentable, préférez des tableaux de règles littéraux ou un `FormRequest`. Les règles construites dynamiquement à l'exécution (variable qui n'est pas elle-même un tableau littéral, calculée dans une autre méthode, etc.) ne peuvent pas être lues sans exécuter votre code : elles sont donc signalées comme avertissement et ignorées pour ce champ.

#### Diagnostiquer les erreurs de génération

Si un élément n'a pas pu être documenté, `swagger:generate` affiche un tableau récapitulatif au lieu d'une erreur générique, en nommant précisément ce qui s'est passé et comment le corriger :

```
⚠ 2 avertissement(s) rencontré(s) pendant la génération de la documentation :
+-----------------------------+--------+---------------------------------------------+------------------------------------------------+
| Contrôleur::méthode / Route | Champ  | Problème                                     | Recommandation                                  |
+-----------------------------+--------+-----------------------------------------------+------------------------------------------------+
| ArticleController::store    | phone  | Règle de validation non reconnue: phone_be   | ... le champ est documenté en "string" par défaut. |
| UserController::store       | -      | ... construit dynamiquement (variable) ...   | Passez un tableau littéral, ou utilisez un FormRequest. |
+-----------------------------+--------+-----------------------------------------------+------------------------------------------------+
```

Chaque ligne indique le contrôleur/la méthode, le champ concerné, la raison précise et une correction concrète — la documentation reste générée pour toutes les autres routes même si certains champs n'ont pas pu être résolus.

#### Accéder à la Documentation

La documentation Swagger générée est disponible à l'adresse suivante :

```bash
/api/documentation
```

Cette page et cette route sont enregistrées directement par le service provider du package (il ne modifie plus votre `routes/api.php` ni ne copie de fichier dans `resources/views`) — ça fonctionne que vous ayez un fichier `api.php` ou non. Pour personnaliser la page, publiez-la et modifiez la copie :

```bash
php artisan vendor:publish --tag=laraswagger-views
```
