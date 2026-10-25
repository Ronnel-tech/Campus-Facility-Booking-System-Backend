<?php

class Create_maintenance_records_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('maintenance_records')) {
            return;
        }

        $this->_lava->dbforge
            ->add_field([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => TRUE,
                    'auto_increment' => TRUE,
                    'null'           => FALSE,
                ],
                'asset_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'issue_description' => [
                    'type'    => 'TEXT',
                    'null'    => FALSE,
                ],
                'priority' => [
                    'type'       => 'ENUM',
                    'constraint' => "'low','medium','high','critical'",
                    'null'       => FALSE,
                    'default'    => 'medium',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'pending','in-progress','completed','cancelled'",
                    'null'       => FALSE,
                    'default'    => 'pending',
                ],
                'reported_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'assigned_to' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'reported_at' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'start_date' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'completion_date' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'resolution_notes' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'cost' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => FALSE,
                    'default'    => 0.00,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
                'updated_at' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                    'on_update' => 'CURRENT_TIMESTAMP',
                ],
            ])
            ->add_key('id', primary: TRUE)
            ->add_key('asset_id')
            ->add_key('status')
            ->add_key('priority')
            ->add_foreign_key('asset_id', 'assets', 'id', 'CASCADE', 'CASCADE')
            ->add_foreign_key('reported_by', 'users', 'id', 'SET NULL', 'CASCADE')
            ->add_foreign_key('assigned_to', 'users', 'id', 'SET NULL', 'CASCADE')
            ->create_table('maintenance_records');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('maintenance_records');
    }
}
