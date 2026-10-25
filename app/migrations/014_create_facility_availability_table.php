<?php

class Create_facility_availability_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('facility_availability')) {
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
                'facility_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'date' => [
                    'type'    => 'DATE',
                    'null'    => FALSE,
                ],
                'start_time' => [
                    'type'       => 'TIME',
                    'null'       => FALSE,
                ],
                'end_time' => [
                    'type'       => 'TIME',
                    'null'       => FALSE,
                ],
                'is_available' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
                ],
                'reason' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
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
            ->add_key('facility_id')
            ->add_key('date')
            ->add_key('is_available')
            ->add_foreign_key('facility_id', 'facilities', 'id', 'CASCADE', 'CASCADE')
            ->create_table('facility_availability');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('facility_availability');
    }
}
