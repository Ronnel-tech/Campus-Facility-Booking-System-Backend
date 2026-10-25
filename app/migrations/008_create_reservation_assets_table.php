<?php

class Create_reservation_assets_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('reservation_assets')) {
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
                'reservation_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'asset_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'quantity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 1,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'pending','allocated','checked-out','returned','cancelled'",
                    'null'       => FALSE,
                    'default'    => 'pending',
                ],
                'checkout_date' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'return_date' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'condition_on_checkout' => [
                    'type'       => 'ENUM',
                    'constraint' => "'excellent','good','fair','damaged'",
                    'null'       => TRUE,
                ],
                'condition_on_return' => [
                    'type'       => 'ENUM',
                    'constraint' => "'excellent','good','fair','damaged'",
                    'null'       => TRUE,
                ],
                'damage_report' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
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
            ->add_key('reservation_id')
            ->add_key('asset_id')
            ->add_key('status')
            ->add_foreign_key('reservation_id', 'reservations', 'id', 'CASCADE', 'CASCADE')
            ->add_foreign_key('asset_id', 'assets', 'id', 'RESTRICT', 'RESTRICT')
            ->create_table('reservation_assets');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('reservation_assets');
    }
}
