<?php

class Create_asset_categories_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('asset_categories')) {
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
                    'constraint' => 100,
                    'null'       => FALSE,
                    'unique'     => TRUE,
                ],
                'description' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'borrow_limit_days' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 7,
                ],
                'late_fee_per_day' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => FALSE,
                    'default'    => 10.00,
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
            ->add_key('name', unique: TRUE)
            ->add_key('is_active')
            ->create_table('asset_categories');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('asset_categories');
    }
}
