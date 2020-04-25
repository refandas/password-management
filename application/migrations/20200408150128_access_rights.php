<?php
class Migration_access_rights extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'access_level' => array(
                    'type'      => 'INT',
                    'unsigned'  => TRUE
                ),
                'status_access' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 32
                )
            )
        );

        $this->dbforge->add_key('access_level', TRUE);
        $this->dbforge->create_table('access_rights');

        $data = array(
            array(
                'access_level'  => 1,
                'status_access' => 'admin'
            ),
            array(
                'access_level'  => 2,
                'status_access' => 'user'
            )
        );
        $this->db->insert_batch('access_rights', $data);
    }
    
    public function down() {
        $this->dbforge->drop_table('access_rights');
    }
}
        