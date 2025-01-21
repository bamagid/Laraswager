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

No additional setup is required post-installation. The package works out of the box.
However, if you'd like to customize the title or description of your API in the Swagger documentation, you can do so by editing the .env file.

```env
APP_NAME: The title of your API
APP_DESCRIPTION: The description of your APP
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

#### Accessing the Documentation

The generated Swagger documentation is available at the following route:

```bash
/api/documentation
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

Aucune configuration supplémentaire requise après l'installation.

Si vous souhaitez personnaliser le titre ou la description de votre API dans la documentation Swagger, vous pouvez le faire en modifiant votre fichier .env.

```env
APP_NAME: The title of your API
APP_DESCRIPTION: The description of your APP
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

#### Accéder à la Documentation

La documentation Swagger générée est disponible à l'adresse suivante :

```bash
/api/documentation
```
