<?php

class Add_version_to_assets
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
    }

    public function up()
    {
        $this->_lava->db->add_column('assets', [
            'version' => ['type' => 'INT', 'constraint' => 11, 'default' => 1, 'after' => 'is_active'],
        ]);

        $this->_lava->db->add_index('assets', 'version');
    }

    public function down()
    {
        $this->_lava->db->drop_column('assets', 'version');
    }
}
