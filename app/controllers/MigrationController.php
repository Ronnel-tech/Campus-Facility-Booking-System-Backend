<?php

 class MigrationController extends Controller {

     public function __construct()
     {
         parent::__construct();
         $this->call->library('migration');
     }

     // ==========================================================
     //  CREATE
     // ==========================================================

     /**
     * Create a new migration file.
     *
     * Route: create-migration/(:any)
     *        create-migration/(:any)/(:any)   (with type)
     *
     * @param string      $migration_class
     * @param string|null $type  create|modify|drop
     */
     public function create_migration($migration_class, $type = 'create')
     {
         $this->migration->create_migration($migration_class, $type);
     }

     // ==========================================================
     //  MIGRATE
     // ==========================================================

     /**
     * Run all pending migrations.
     *
     * Route: migrate
     */
     public function migrate()
     {
         $this->migration->migrate(false);
     }

     public function migrate_dry()
     {
         $this->migration->migrate(true);
     }

     /**
     * Migrate up to a specific version.
     *
     * Route: migrate-to/(:num)
     *
     * @param int $version
     */
     public function migrate_to($version)
     {
         $this->migration->migrate_to($version);
     }

     // ==========================================================
     //  ROLLBACK
     // ==========================================================

     /**
     * Rollback the most recent batch (or N batches).
     *
     * Route: rollback
     *        rollback/(:num)
     *
     * @param int $steps  Number of batches to rollback (default: 1)
     */
     public function rollback($steps = 1)
     {
         $this->migration->rollback($steps);
     }

     /**
     * Rollback down to a specific version.
     *
     * Route: rollback-to/(:num)
     *
     * @param int $version
     */
     public function rollback_to($version)
     {
         $this->migration->rollback_to($version);
     }

     /**
     * Rollback every applied migration.
     *
     * Route: rollback-all
     */
     public function rollback_all()
     {
         $this->migration->rollback_all();
     }

     // ==========================================================
     //  REFRESH / RESET / FRESH
     // ==========================================================

     /**
     * Rollback all + re-run all migrations.
     *
     * Route: refresh
     */
     public function refresh()
     {
         $this->migration->refresh();
     }

     /**
     * Rollback all migrations WITHOUT re-running them.
     *
     * Route: reset
     */
     public function reset()
     {
         $this->migration->reset();
     }

     /**
     * Drop ALL tables and re-run all migrations.
     *
     * Route: fresh
     */
     public function fresh()
     {
         $this->migration->fresh();
     }

     // ==========================================================
     //  INSPECTION
     // ==========================================================

     /**
     * Show the migration status table.
     *
     * Route: status
     */
     public function status()
     {
         $this->migration->status();
     }

     /**
     * Preview UP code for pending migrations.
     *
     * Route: preview
     */
     public function preview()
     {
         $this->migration->preview();
     }
 }