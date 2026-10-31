<?php

class Seed_default_categories
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
            "INSERT INTO facility_categories (name, description, is_active)
             SELECT 'Classroom', 'Classrooms and lecture rooms', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM facility_categories WHERE name = 'Classroom'
             )"
        );
        $this->_lava->db->raw(
            "INSERT INTO facility_categories (name, description, is_active)
             SELECT 'Laboratory', 'Academic and research laboratories', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM facility_categories WHERE name = 'Laboratory'
             )"
        );
        $this->_lava->db->raw(
            "INSERT INTO facility_categories (name, description, is_active)
             SELECT 'Meeting Room', 'Meeting and conference rooms', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM facility_categories WHERE name = 'Meeting Room'
             )"
        );

        $this->_lava->db->raw(
            "INSERT INTO asset_categories (name, description, is_active)
             SELECT 'Computer Equipment', 'Computers and related equipment', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM asset_categories WHERE name = 'Computer Equipment'
             )"
        );
        $this->_lava->db->raw(
            "INSERT INTO asset_categories (name, description, is_active)
             SELECT 'Audio Visual', 'Projectors, speakers, and presentation equipment', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM asset_categories WHERE name = 'Audio Visual'
             )"
        );
        $this->_lava->db->raw(
            "INSERT INTO asset_categories (name, description, is_active)
             SELECT 'Furniture', 'Tables, chairs, and other furniture', 1
             WHERE NOT EXISTS (
                 SELECT 1 FROM asset_categories WHERE name = 'Furniture'
             )"
        );
    }

    public function down()
    {
        $this->_lava->db->raw(
            
            "DELETE FROM facility_categories
             WHERE name IN ('Classroom', 'Laboratory', 'Meeting Room')"
        );
        $this->_lava->db->raw(
            "DELETE FROM asset_categories
             WHERE name IN ('Computer Equipment', 'Audio Visual', 'Furniture')"
        );
    }
}
