<?php
class Migration_user extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'id_user' => array(
                    'type'              => 'INT',
                    'unsigned'          => TRUE,
                    'auto_increment'    => TRUE
                ),
                'email' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 64,
                    'unique'        => TRUE
                ),
                'name' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 64
                ),
                'password' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 128
                ),
				'registered' => array(
					'type'      => 'INT',
					'unsigned'  => TRUE
				),
                'access_level' => array(
                    'type'          => 'INT',
                    'unsigned'      => TRUE,
                )
            )
        );

        $this->dbforge->add_key('id_user', TRUE);
        $this->dbforge->add_field('CONSTRAINT fk_u_ar FOREIGN KEY (access_level) REFERENCES access_rights(access_level)');
        $this->dbforge->create_table('user');

        $data = array(
            array(
                'email'         => 'admin@email.com',
                'name'          => 'Admin',
                'password'      => '$2y$10$yOPFuKeRlpk/E30B0gQsmOzM74yxze1oOlD/kV6x5NDx4seYNBlkW',
                'registered'	=> 1,
                'access_level'  => 1
            ),
            array(
                'email'         => 'jenkins@email.com',
                'name'          => 'Jenkins',
                'password'      => '$2y$10$yOPFuKeRlpk/E30B0gQsmOzM74yxze1oOlD/kV6x5NDx4seYNBlkW',
                'registered'	=> 1,
                'access_level'  => 2
            )
        );
        $this->db->insert_batch('user', $data);
    }
    
    public function down() {
        $this->dbforge->drop_table('user');
    }
}
        