maple-syrup-group/dbsampler
===========================

[![Build Status](https://travis-ci.org/MapleSyrupGroup/dbsampler.svg?branch=master)](https://travis-ci.org/MapleSyrupGroup/dbsampler)

A general tool for extracting and cleaning selected tables from a database for use as fixtures. 
Copies a subset of tables from one database to another under the control of a json configuration file. 
The latter database can then be dumped to SQL for use as a fixture file.

Usage
-----

- Create the target database(s) you wish to fill. The tool will only output to existing databases. The content of the Destination Databases **will** be trashed.
- Create `config/credentials.json` with the DB server configuration. 
- Create `config/*.db.json` files to define mappings for each required database
- Run `bin/dbsampler.php`

Configuration formats
---------------------

All config files live in the `config` subdirectory. Files *cannot* contain comments as the JSON format does not support this, but as a convention, fields called "comment" will be ignored where possible.

#### credentials.json

`"driver": "pdo_mysql"` is currently assumed but this may change in future.

##### MySQL

See `config/credentials.dist.json`:

    {
      "driver": "pdo_mysql",
      "dbUser": "root",
      "dbPassword": "SOMEPASSWORD",
      "dbHost": "127.0.0.1"
    }
    
If you need different source and dest servers, this becomes:
    
    {
      "source": {
        "driver": "pdo_mysql",
        "dbUser": "root",
        "dbPassword": "SOMEPASSWORD",
        "dbHost": "sourceDB.example.com"
      },
    
      "dest" : {
        "driver": "pdo_mysql",
        "dbUser": "root",
        "dbPassword": "SOMEPASSWORD",
        "dbHost": "127.0.0.1"
      }
    }
        
If you need to prepare the connections, add an initialSql stanza:
        
    {
      "source": {
        "driver": "pdo_mysql",
        "dbUser": "root",
        "dbPassword": "SOMEPASSWORD",
        "dbHost": "sourceDB.example.com",
        "initialSql": [
          "SET NAMES UTF8"
        ]
      },
      "dest": {
        "driver": "pdo_mysql",
        "dbUser": "root",
        "dbPassword": "SOMEPASSWORD",
        "dbHost": "127.0.0.1",
        "initialSql": [
          "SET NAMES UTF8",
          "SET foreign_key_checks = 0"
        ]
      }
    }        
    
##### Sqlite
    
See `config/credentials.dist.json`:
    
    {
        "driver": "pdo_sqlite",
        "directory": "..\/path\/to\/sqlite-dbs"
    }
    
Paths are assumed to be relative to the config file unless they start with a '/'. Sqlite databases to be migrated are 
assumed to be `*.sqlite` files in this directory

#### *dbname*.db.json
    
    {
      "name": "small-sqlite-test",          # Configuration name
      "sourceDb": "small-source",           # Name of the source DB
      "destDb": "small-dest",               # Name of the destination DB. This DB will get trashed
      "tables": {                           # A set of tables to be copied over. Each table is defined as "table": config
                                            # Every config stanza requires a sampler field. For now, look these up in 
                                            # \Quidco\DbSampler\MigrationConfigProcessor::$samplerMap
                                            # All other fields depend on the specific sampler being used; these should 
                                            # all be documented in their own class files in src/Sample
        "fruits": {
          "sampler": "matched",
          "constraints": {
            "name": [
              "apple",
              "pear"
            ]
          },
          "remember": {
            "id": "fruit_ids"               # Cross-referencing is supported by "remember" stanzas
                                            # These take the field name of which the values are to be remembered
                                            # matched to a variable name in which the values will be stored
                                            # Note: Variable declarations do not include a '$' symbol 
                                            # References MUST be 'remember'ed before being used, there is no
          }                                 # dependency resolution here, so order your config appropriately
        },
        "vegetables": {
          "sampler": "NewestById",
          "idField": "id",
          "quantity": 2
        },
        "fruit_x_basket": {
          "sampler": "matched",
          "constraints": {
            "fruit_id": "$fruit_ids"        # Remembered variables, with $ sign, can be used as cross-references
                                            # This will expand to all ids of the fruits table matched above
          },
          "where" : [
            "basket_id > 1"                 # The matched sampler can also accept a list of arbitrary WHERE clauses
          ],
          "remember": {
            "basket_id": "basket_ids"
          }
        },
        "baskets": {
          "sampler": "matched",             # samplers support field cleaners that are defined in
                                            # \Quidco\DbSampler\FieldCleanerProvider::getCleanerByName
                                            # They modify or replace the content of the field that they are keyed to
          "constraints": {
            "id": "$basket_ids"
          },
          "cleanFields": {
            "name": "fakefullname"
          }
        }
      },
      "views": [                            # view support is experimental
        "some_view"                         # views are specified as name only but format may change
      ]                                     # The destination's CURRENT_USER() is used as the DEFINER for MySQL DBs
    }
    
##### "Faker" cleaners
    
Any 'faker' ([fzaninotto/faker](https://github.com/fzaninotto/Faker)) generator that does not require parameters can be 
used directly in the cleanFields stanza by using `"name": "faker:GENERATOR"`, eg:
   
     "cleanFields": {
       "ip": "faker:ipv4"
     },
   

Extending the project
---------------------
The tool is designed to be extended primarily by adding custom Samplers (which must implement `\Quidco\DbSampler\SamplerInterface`) 
and cleaners (documented in `\Quidco\DbSampler\FieldCleanerProvider::getCleanerByName`).

It is likely that a mechanism to register external cleaners and samplers will be provided.

Currently, only mysql and sqlite databases are supported, but this could also be extended.
