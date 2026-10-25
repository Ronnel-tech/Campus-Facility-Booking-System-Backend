<?php

class Create_assets_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('assets')) {
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
                'asset_tag' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => FALSE,
                    'unique'     => TRUE,
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
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
                ],
                'quantity_available' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
                ],
                'condition' => [
                    'type'       => 'ENUM',
                    'constraint' => "'excellent','good','fair','damaged'",
                    'null'       => FALSE,
                    'default'    => 'good',
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'available','checked-out','maintenance','damaged','missing'",
                    'null'       => FALSE,
                    'default'    => 'available',
                ],
                'current_location' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => TRUE,
                ],
                'purchase_date' => [
                    'type'    => 'DATE',
                    'null'    => TRUE,
                ],
                'purchase_price' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => TRUE,
                ],
                'is_borrowable' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
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
            ->add_key('asset_tag', unique: TRUE)
            ->add_key('category_id')
            ->add_key('status')
            ->add_key('condition')
            ->add_key('is_active')
            ->add_foreign_key('category_id', 'asset_categories', 'id', 'CASCADE', 'CASCADE')
            ->create_table('assets');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('assets');
    }
}
