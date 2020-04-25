<?php
class Migration_feature extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'id_feature' => array(
                    'type'              => 'INT',
                    'unsigned'          => TRUE,
                    'auto_increment'    => TRUE
                ),
                'feature_name' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 32
                ),
                'url' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 64
                )
            )
        );

        $this->dbforge->add_key('id_feature', TRUE);
        $this->dbforge->create_table('feature');

        $data = array(
            array(
                'feature_name'  => 'user',
                'url'           => '#'
            ),
            array(
                'feature_name'  => 'account',
                'url'           => '#'
            )
        );
        $this->db->insert_batch('feature', $data);
    }
    
    public function down() {
        $this->dbforge->drop_table('feature');
    }
}
        