<?php

class AccountModel extends CI_Model {

    private const TABLE_NAME = "account";

    //  method for add account media
    public function addAccount($data) {
        /*
        |   encryption algorithm for username or email
        |   $data['username_email'] = encryptUsernameEmail($data['username_email']);
        |
        |   encryption algorithm for password
        |   $data['password'] = encryptPassword($data['password']);
        */

        $this->db->insert($this::TABLE_NAME, $data);
    }

    public function getAccounts() {
        $accounts = $this->db->get($this::TABLE_NAME);

        return $accounts->result_array();
    }

    public function getAccountByUserID($id_user) {
        $accounts = $this->db->get($this::TABLE_NAME);

        return $accounts->result_array();
    }

    public function editAccount($data) {
        $this->db->where('id_account', $data['id_account']);
        $this->db->update($this::TABLE_NAME, $data);
    }

    public function deleteAccount($id_account) {
        $this->db->where('id_account', $id_account);
        $is_deleted = $this->db->delete($this::TABLE_NAME);

        return $is_deleted;
    }

    public function encryptUsernameEmail($username_email) {

    }

    public function decryptUsernameEmail($username_email) {

    }

    public function encryptPassword($password) {

    }

    public function decryptPassword($cipher) {

    }
}