<?php

class Create_asset_borrowings_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('asset_borrowings')) {
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
                'reservation_asset_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                ],
                'user_id' => [
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
                'checkout_date' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                ],
                'expected_return_date' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                ],
                'actual_return_date' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'condition_on_checkout' => [
                    'type'       => 'ENUM',
                    'constraint' => "'excellent','good','fair','damaged'",
                    'null'       => FALSE,
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
                'late_fee' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => FALSE,
                    'default'    => 0.00,
                ],
                'damage_cost' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '10,2',
                    'null'       => FALSE,
                    'default'    => 0.00,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => "'checked-out','returned','overdue','resolved'",
                    'null'       => FALSE,
                    'default'    => 'checked-out',
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
            ->add_key('reservation_asset_id')
            ->add_key('user_id')
            ->add_key('asset_id')
            ->add_key('status')
            ->add_key('checkout_date')
            ->add_foreign_key('reservation_asset_id', 'reservation_assets', 'id', 'CASCADE', 'CASCADE')
            ->add_foreign_key('user_id', 'users', 'id', 'CASCADE', 'CASCADE')
            ->add_foreign_key('asset_id', 'assets', 'id', 'RESTRICT', 'RESTRICT')
            ->create_table('asset_borrowings');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('asset_borrowings');
    }
}
