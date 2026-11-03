<?php

class Expand_notification_enums
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
            "ALTER TABLE notifications MODIFY type ENUM('reservation_submitted','reservation_approved','reservation_rejected','reservation_cancelled','asset_checkout','asset_return','reservation_reminder','maintenance_update','maintenance_updated') NOT NULL"
        );
        $this->_lava->db->raw(
            "ALTER TABLE notifications MODIFY related_type VARCHAR(50) NULL"
        );
    }

    public function down()
    {
        $this->_lava->db->raw(
            "ALTER TABLE notifications MODIFY type ENUM('reservation_approved','reservation_rejected','asset_checkout','asset_return','reservation_reminder','maintenance_update') NOT NULL"
        );
        $this->_lava->db->raw(
            "ALTER TABLE notifications MODIFY related_type VARCHAR(50) NULL"
        );
    }
}
