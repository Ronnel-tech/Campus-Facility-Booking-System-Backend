<?php

class Create_reservations_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('reservations')) {
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
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'facility_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'reservation_type' => [
                    'type'       => 'ENUM',
                    'constraint' => "'facility','asset','combined'",
                    'null'       => FALSE,
                ],
                'purpose' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => FALSE,
                ],
                'start_date' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                ],
                'end_date' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                ],
                'attendee_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'pending','approved','rejected','cancelled','completed'",
                    'null'       => FALSE,
                    'default'    => 'pending',
                ],
                'admin_remarks' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'approved_at' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'approved_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
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
            ->add_key('user_id')
            ->add_key('facility_id')
            ->add_key('status')
            ->add_key('start_date')
            ->add_foreign_key('user_id', 'users', 'id', 'CASCADE', 'CASCADE')
            ->add_foreign_key('facility_id', 'facilities', 'id', 'SET NULL', 'CASCADE')
            ->add_foreign_key('approved_by', 'users', 'id', 'SET NULL', 'CASCADE')
            ->create_table('reservations');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('reservations');
    }
}
