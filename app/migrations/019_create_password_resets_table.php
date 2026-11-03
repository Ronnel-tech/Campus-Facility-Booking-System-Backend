<?php

class Create_password_resets_table
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
    }

    public function up()
    {
        $this->_lava->db->create_table('password_resets', [
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'primary_key' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'not_null' => true],
            'token' => ['type' => 'VARCHAR', 'constraint' => 255, 'not_null' => true],
            'expires_at' => ['type' => 'DATETIME', 'not_null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'default' => 'CURRENT_TIMESTAMP'],
        ]);

        $this->_lava->db->add_foreign_key(
            'password_resets',
            'user_id',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->_lava->db->add_index('password_resets', 'token', 'UNIQUE');
        $this->_lava->db->add_index('password_resets', 'user_id');
        $this->_lava->db->add_index('password_resets', 'expires_at');
    }

    public function down()
    {
        $this->_lava->db->drop_table('password_resets');
    }
}
