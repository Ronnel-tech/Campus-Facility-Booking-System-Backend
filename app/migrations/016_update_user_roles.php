<?php

class Update_user_roles
{
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->database();
    }

    public function up()
    {
        $this->_lava->db->raw(
            "ALTER TABLE users MODIFY role ENUM('student','faculty','staff','admin') NOT NULL DEFAULT 'student'"
        );
    }

    public function down()
    {
        $this->_lava->db->raw(
            "ALTER TABLE users MODIFY role ENUM('admin','moderator','user') NOT NULL DEFAULT 'user'"
        );
    }
}
