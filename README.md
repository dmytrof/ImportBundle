DmytrofImportBundle
====================

This bundle helps you to import data for your Symfony 4/5 application

## Installation

### Step 1: Install the bundle

    $ composer require dmytrof/import-bundle 
    
### Step 2: Enable the bundle

    <?php
        // config/bundles.php
        
        return [
            // ...
            Dmytrof\ImportBundle\DmytrofImportBundle::class => ['all' => true],
        ];
        
        