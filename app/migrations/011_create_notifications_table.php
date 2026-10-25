<?php

class Create_notifications_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('notifications')) {
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
                'type' => [
                    'type'       => 'ENUM',
                    'constraint' => "'reservation_approved','reservation_rejected','asset_checkout','asset_return','reservation_reminder','maintenance_update'",
                    'null'       => FALSE,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => FALSE,
                ],
                'message' => [
                    'type'    => 'TEXT',
                    'null'    => FALSE,
                ],
                'related_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'related_type' => [
                    'type'       => 'ENUM',
                    'constraint' => "'reservation','asset','maintenance'",
                    'null'       => TRUE,
                ],
                'is_read' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'read_at' => [
                    'type'    => 'DATETIME',
                    'null'    => TRUE,
                ],
                'created_at' => [
                    'type'    => 'DATETIME',
                    'null'    => FALSE,
                    'default' => 'CURRENT_TIMESTAMP',
                ],
            ])
            ->add_key('id', primary: TRUE)
            ->add_key('user_id')
            ->add_key('type')
            ->add_key('is_read')
            ->add_foreign_key('user_id', 'users', 'id', 'CASCADE', 'CASCADE')
            ->create_table('notifications');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('notifications');
    }
}
