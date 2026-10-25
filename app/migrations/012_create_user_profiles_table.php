<?php

class Create_user_profiles_table {

    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('user_profiles')) {
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
                    'unique'     => TRUE,
                ],
                'first_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => TRUE,
                ],
                'last_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => TRUE,
                ],
                'phone' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => TRUE,
                ],
                'department' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => TRUE,
                ],
                'student_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => TRUE,
                    'unique'     => TRUE,
                ],
                'faculty_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => TRUE,
                    'unique'     => TRUE,
                ],
                'staff_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => TRUE,
                    'unique'     => TRUE,
                ],
                'profile_picture' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => TRUE,
                ],
                'bio' => [
                    'type'    => 'TEXT',
                    'null'    => TRUE,
                ],
                'violations_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'is_suspended' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => TRUE,
                    'null'       => FALSE,
                    'default'    => 0,
                ],
                'suspension_reason' => [
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
            ->add_key('user_id', unique: TRUE)
            ->add_key('student_id', unique: TRUE)
            ->add_key('faculty_id', unique: TRUE)
            ->add_key('staff_id', unique: TRUE)
            ->add_foreign_key('user_id', 'users', 'id', 'CASCADE', 'CASCADE')
            ->create_table('user_profiles');
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('user_profiles');
    }
}
