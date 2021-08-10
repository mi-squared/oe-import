
This is an OpenEMR module that contains "generic" tools for importing data into OpenEMR.
This is a framework only, and you will need to create another module that implements "ImportProvider" and one or more "Importers"

For example:
https://github.com/mi-squared/oe-crisisprep/tree/main/src/Importer

And in your module's bootstrap:

// Initialize the CPR import provider (plugin for the Import module)
$importProvider = new \Mi2\CrisisPrep\Importer\ImportProvider($eventDispatcher);

The module adds a menu item and UI to the Administration menu called "Import"
