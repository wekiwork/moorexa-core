# Drivers
This directory contains all supported drivers, each folder contains 5 files namely;
- Driver.php
- Builder.php
- Query.php
- Schema.php
- Table.php

### Driver.php
This file provides access to the database with that driver using PDO. It also registers a default query builder for the driver. 

### Query.php
This file provides some helper methods for creating, reading, inserting, updating and deleting records with that driver

### Builder.php
This file provides access to the query builder registered via Driver.php

### Schema.php
This file provides driver support for creating and managing database tables

### Table.php
This file provides some helper methods for your database tables.