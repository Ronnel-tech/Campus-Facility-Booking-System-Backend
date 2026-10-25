<?php

class Create_facilities_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('facilities')) {
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
                'name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => FALSE,
                ],
                'category_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'description' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'capacity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'location' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => TRUE,
                ],
                'amenities' => [
                    'type'    => 'JSON',
                    'null'    => TRUE,
                ],
                'rules' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'active','inactive','maintenance'",
                    'null'       => FALSE,
                    'default'    => 'active',
                ],
                'booking_limit_hours' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 4,
                ],
                'max_bookings_per_day' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 2,
                ],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
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
            ->add_key('category_id')
            ->add_key('status')
            ->add_key('is_active')
            ->add_foreign_key('category_id', 'facility_categories', 'id', 'CASCADE', 'CASCADE')
            ->create_table('facilities');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('facilities');
    }
}
