<?php

class Create_audit_logs_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('audit_logs')) {
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
                    'null'       => TRUE,
                ],
                'action' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => FALSE,
                ],
                'entity_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => FALSE,
                ],
                'entity_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => TRUE,
                ],
                'old_values' => [
                    'type'    => 'JSON',
                    'null'    => TRUE,
                ],
                'new_values' => [
                    'type'    => 'JSON',
                    'null'    => TRUE,
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => TRUE,
                ],
                'user_agent' => [
                    'type'    => 'TEXT',
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
            ->add_key('action')
            ->add_key('entity_type')
            ->add_key('created_at')
            ->add_foreign_key('user_id', 'users', 'id', 'SET NULL', 'CASCADE')
            ->create_table('audit_logs');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('audit_logs');
    }
}
