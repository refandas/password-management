<?php
class Migration_account extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'id_account' => array(
                    'type'              => 'INT',
                    'unsigned'          => TRUE,
                    'auto_increment'    => TRUE
                ),
                'id_user' => array(
                    'type'      => 'INT',
                    'unsigned'  => TRUE
                ),
                'username_email' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 256,
                    'unique'        => TRUE
                ),
                'password' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 256
                ),
                'id_media' => array(
                    'type'      => 'INT',
                    'unsigned'  => TRUE
                ),
                'key_str' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 64
                )
            )
        );

        $this->dbforge->add_key('id_account', TRUE);
        $this->dbforge->add_field('CONSTRAINT fk_a_u FOREIGN KEY (id_user) REFERENCES user(id_user)');
        $this->dbforge->add_field('CONSTRAINT fk_a_am FOREIGN KEY (id_media) REFERENCES account_media(id_media)');
        $this->dbforge->create_table('account');
    }
    
    public function down() {
        $this->dbforge->drop_table('account');
    }
}
        