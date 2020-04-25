<?php
class Migration_account_media extends CI_Migration {
    public function up() {
        $this->dbforge->add_field(
            array(
                'id_media' => array(
                    'type'              => 'INT',
                    'unsigned'          => TRUE,
                    'auto_increment'    => TRUE
                ),
                'logo' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 256
                ),
                'name' => array(
                    'type'          => 'VARCHAR',
                    'constraint'    => 64
                )
            )
        );

        $this->dbforge->add_key('id_media', TRUE);
        $this->dbforge->create_table('account_media');

        $data = array(
            array(
                'logo'   => 'fab fa-instagram',
                'name'   => 'Instagram'
            ),
            array(
                'logo'   => 'fab fa-twitter',
                'name'   => 'Twitter'
            ),
            array(
                'logo'   => 'fab fa-facebook-f',
                'name'   => 'Facebook'
            ),
            array(
                'logo'   => 'fab fa-google',
                'name'   => 'Google'
            ),
            array(
                'logo'   => 'fab fa-medium',
                'name'   => 'Medium'
            ),
            array(
                'logo'   => 'fab fa-quora',
                'name'   => 'Quora'
            ),
            array(
                'logo'   => 'fab fa-trello',
                'name'   => 'Trello'
            ),
            array(
                'logo'   => 'fab fa-line',
                'name'   => 'Line'
            ),
            array(
                'logo'   => 'fab fa-cc-visa',
                'name'   => 'Visa'
            ),
            array(
                'logo'   => 'fab fa-cc-mastercard',
                'name'   => 'Mastercard'
            ),
            array(
                'logo'   => 'fab fa-cc-amex',
                'name'   => 'American Express'
            ),
            array(
                'logo'   => 'fab fa-amazon',
                'name'   => 'Amazon'
            ),
            array(
                'logo'   => 'fab fa-apple',
                'name'   => 'Apple'
            ),
            array(
                'logo'   => 'fab fa-steam',
                'name'   => 'Steam'
            )
        );

        $this->db->insert_batch('account_media', $data);
    }
    
    public function down() {
        $this->dbforge->drop_table('account_media');
    }
}
        