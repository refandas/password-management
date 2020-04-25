<?php
class Migration_access_list extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'access_level' => array(
                    'type'      => 'INT',
                    'unsigned'  => TRUE
                ),
                'id_feature' => array(
                    'type'      => 'INT',
                    'unsigned'  => TRUE
                )
            )
        );

        $this->dbforge->add_field('CONSTRAINT fk_al_ar FOREIGN KEY (access_level) REFERENCES access_rights(access_level)');
        $this->dbforge->add_field('CONSTRAINT fk_al_ft FOREIGN KEY (id_feature) REFERENCES feature(id_feature)');
        $this->dbforge->create_table('access_list');

        $data = array(
            array(
                'access_level'  => 1,
                'id_feature'    => 1
            ),
            array (
                'access_level'  => 2,
                'id_feature'    => 2
            )
        );

        $this->db->insert_batch('access_list', $data);
    }
    
    public function down() {
        $this->dbforge->drop_table('access_list');
    }
}
        