<?php

class AccountModel extends CI_Model {

    private const TABLE_NAME_ACCOUNT = "account";
    private const TABLE_NAME_ACCOUNT_MEDIA = "account_media";

    public function __construct() {
        parent::__construct();
        $this->load->library('encryption');
        $this->load->model('UserModel');
    }

    /**
     * Add account media
     * Encrypted username or email and password
     * @param $data
     */
    public function addAccount($data) {
        $key_str         = $this->generateKey();
        $data['key_str'] = $key_str;

        $data = $this->encryptAccount($data);
        $this->db->insert($this::TABLE_NAME_ACCOUNT, $data);
    }

    /**
     * Generate key for encryption
     * @return string of key in hexadecimal.
     */
    private function generateKey() {
        $key = bin2hex($this->encryption->create_key(32));

        return $key;
    }

    /**
     * Get all account data
     * @return all account data
     */
    public function getAccounts() {
        $accounts = $this->db->get($this::TABLE_NAME_ACCOUNT);

        return $accounts->result_array();
    }

    /**
     * Get all of account data based on id user.
     * @param $id_user
     * @return all of account data based on id user.
     */
    public function getAccountByUserID($id_user) {
        $this->db->select('*');
        $this->db->from('account a');
        $this->db->join('account_media am', 'am.id_media = a.id_media');
        $this->db->where('a.id_user', $id_user);
        $accounts = $this->db->get()->result_array();
        $accounts = $this->decryptAccount($accounts);

        return $accounts;
    }

    /**
     * Get account based on $id_account
     * if $single = TRUE, it will return account data with decrypted username or email and password,
     * otherwise, it will return data with encrypted username or email and password.
     * @param $id_account
     * @param bool $single
     * @return array of account
     */
    public function getAccountByID($id_account, $single = FALSE) {
        if ($single) {
            $account = $this->db->get_where($this::TABLE_NAME_ACCOUNT, array('id_account' => $id_account))->row_array();
            $account = $this->decryptAccount($account, $single);
        }
        else {
            $account = $this->db->get_where($this::TABLE_NAME_ACCOUNT, array('id_account' => $id_account))->row_array();
        }

        return $account;
    }

    /**
     * get all account media name and icon
     * example: instagram, twitter, etc.
     * @return all of account media.
     */
    public function getAccountMedia() {
        $this->db->order_by('name', 'ASC');
        $account_media = $this->db->get($this::TABLE_NAME_ACCOUNT_MEDIA);

        return $account_media->result_array();
    }

    /**
     * Edit account data
     * @param $data
     */
    public function editAccount($data) {
        $data = $this->encryptAccount($data);

        $this->db->where('id_account', $data['id_account']);
        $this->db->update($this::TABLE_NAME_ACCOUNT, $data);
    }

    /**
     * Delete account data
     * @param $id_account
     * @return true or false of action delete account.
     */
    public function deleteAccount($id_account) {
        $key = $this->getEncryptionKey($id_account);

        $id_account['username_email'] =

        $this->db->where('id_account', $id_account);
        $is_deleted = $this->db->delete($this::TABLE_NAME_ACCOUNT);
    }

    /**
     * Encrypt username and password
     * @param $data
     * @return array of param given, username or email and password are encrypted
     */
    private function encryptAccount($data) {
        $key = hex2bin($data['key_str']);
        $config['encryption_key'] = $key;

        $this->initializeAndConfigEncryption($key);

        $username_email = $this->encryption->encrypt($data['username_email']);
        $password       = $this->encryption->encrypt($data['password']);

        $data['username_email'] = $username_email;
        $data['password'] = $password;

        return $data;
    }

    /**
     * Decrypt username or email and password
     * if $single_account TRUE, it will encrypt single row of account data,
     * otherwise, it will encrypt all row of account data
     *
     * @param $accounts
     * @param $single_account
     * @return array of decrypted username or email and password
     */
    private function decryptAccount($accounts, $single_account = FALSE) {
        $index = 0;  // counting iteration

        if ($single_account) {
            $username_email = $accounts['username_email'];
            $password       = $accounts['password'];
            $key            = hex2bin($this->getEncryptionKey($accounts['id_account']));

            $this->initializeAndConfigEncryption($key);
            $config['encryption_key']   = $key;
            $accounts['username_email'] = $this->encryption->decrypt($username_email);
            $accounts['password']       = $this->encryption->decrypt($password);
        }
        else {
            foreach ($accounts as $account) {
                $username_email = $account['username_email'];
                $password       = $account['password'];
                $key            = hex2bin($this->getEncryptionKey($account['id_account']));

                $this->initializeAndConfigEncryption($key);
                $config['encryption_key']            = $key;
                $accounts[$index]['username_email']  = $this->encryption->decrypt($username_email);
                $accounts[$index]['password']        = $this->encryption->decrypt($password);
                $index++;
            }
        }

        return $accounts;
    }

    /**
     * Get encryption key of account based on id account
     * @param $id_account
     * @return string of key
     */
    private function getEncryptionKey($id_account) {
        $account = $this->getAccountByID($id_account);
        $key     = $account['key_str'];

        return $key;
    }

    /**
     * Initializing encryption cipher, mode, and key
     * Set $config['encryption_key'] with the key that been converted to biner
     * @param $key
     */
    private function initializeAndConfigEncryption($key) {
        $this->encryption->initialize(  // initialize encryption
            array(
                'cipher'    => 'aes-256',
                'mode'      => 'ctr',
                'key'       => $key
            )
        );

        $config['encryption_key'] = $key;
    }
}