DmytrofImportBundle
====================

This bundle helps you to import data (JSON, XML, RSS, CSV) into your Symfony application.

## Requirements

* PHP 8.4 or higher
* Symfony 6.4, 7.x or 8.x
* Doctrine ORM 3 with DoctrineBundle 2.13+ or 3.x
* dmytrof/models-management-bundle 1.12+

## Installation

### Step 1: Install the bundle

    $ composer require dmytrof/import-bundle

### Step 2: Enable the bundle

    <?php
        // config/bundles.php

        return [
            // ...
            Dmytrof\ModelsManagementBundle\DmytrofModelsManagementBundle::class => ['all' => true],
            Dmytrof\ImportBundle\DmytrofImportBundle::class => ['all' => true],
        ];

### Step 3: Register the entities mapping

The bundle entities (`Task`, `Item`) are mapped with PHP attributes:

    # config/packages/doctrine.yaml
    doctrine:
        orm:
            mappings:
                DmytrofImportBundle:
                    is_bundle: false
                    type: attribute
                    dir: '%kernel.project_dir%/vendor/dmytrof/import-bundle/src/Entity'
                    prefix: 'Dmytrof\ImportBundle\Entity'
                    alias: DmytrofImport

Then create the tables with Doctrine migrations:

    $ php bin/console doctrine:migrations:diff
    $ php bin/console doctrine:migrations:migrate

The `Task` entity uses Gedmo `Blameable` and `Timestampable` traits, so make sure
the Gedmo `BlameableListener` and `TimestampableListener` are registered
(for example via `stof/doctrine-extensions-bundle` or `DmytrofModelsManagementBundle`).

## Usage

Import scheduled tasks:

    $ php bin/console dmytrof:import:tasks

Import scheduled items:

    $ php bin/console dmytrof:import:items --all

## Upgrading from 1.x (Symfony 4/5)

* Doctrine mapping switched from annotations to PHP attributes: set `type: attribute`
  for the bundle mapping (see Step 3).
* Validation constraints switched from annotations to PHP attributes.
* The Doctrine listener is now registered with the `doctrine.event_listener` tag
  instead of the deprecated `doctrine.event_subscriber` tag.
* Custom form types, data transformers, readers and importers extending the bundle
  classes must use return types compatible with Symfony 6.4
  (e.g. `configureOptions(): void`, `getParent(): ?string`, `transform(mixed $value): mixed`).

## Upgrading to PHP 8.4 / Doctrine ORM 3

* `EntityManager::merge()` (removed in ORM 3) is no longer used: after an import the task (and an item failed
  to import) is re-fetched from the entity manager when the importer has detached it.
  `PostImportTaskEvent` receives the managed task.
* `Entity\Task\Task::$active` is typed `bool` and `Entity\Item\Item::$target` is typed `?array`
  (as in ModelsManagementBundle 1.12 traits): redeclare them with the same types in your subclasses.
* `ImportableFields::set()`, `ImportableFieldsOptions::set()` and `add()` follow Doctrine Collections 2/3
  signatures (`set(string|int $key, mixed $value): void`): they are not fluent anymore.
* Services use `!tagged_iterator` (the `!tagged` YAML tag was removed in Symfony 8).
* `symfony/translation` is required (readers and importers use the translator).
* On PHP 8.4 with DoctrineBundle 2.x enable native lazy objects:

        # config/packages/doctrine.yaml
        doctrine:
            orm:
                enable_native_lazy_objects: true
