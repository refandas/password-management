<?php

class UserModel extends CI_Model {

    private const TABLE_NAME = "user";

    /*
    |   method for registration new user
    |   by default access level for most user that registered first time is "user", it means this limited to feature
    */
    public function registration($data) {
        $data['password']       = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['access_level']   = 2;

        $user = $this->db->insert($this::TABLE_NAME, $data);
        return $user;
    }

    //   method for set password (reset password)
    public function setPassword($data) {
        $data['password']  = password_hash($data['password'], PASSWORD_DEFAULT);
        $this->db->where('id_user', $data['id_user']);
        $update_password = $this->db->update($this::TABLE_NAME, $data);

        return $update_password;
    }

    //  method for update user
    public function updateUser($data) {
        $this->db->where('email', $data);
        $this->db->update($this::TABLE_NAME, $data);
    }

    // method for delete user
    public function deleteUser($id_user) {
        $this->db->where('id_user', $id_user);
        $this->db->delete($this::TABLE_NAME);
    }

    // method for activate user, just admit can access this
    public function activateUser($id_user) {
        $make_active = array('registered' => 1);

        $this->db->where('id_user', $id_user);
        $this->db->update($this::TABLE_NAME, $make_active);
    }

    // method for disable user, just admin can access this
    public function disableUser($id_user) {
        $make_disable = array('registered' => 0);

        $this->db->where('id_user', $id_user);
        $this->db->update($this::TABLE_NAME, $make_disable);
    }


    //  method for authenticate
    public function authenticate($email, $password) {
        $user = $this->findUserByEmail($email);
        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    // method for get many users
    public function getUsers() {
        $users = $this->db->get($this::TABLE_NAME);

        return $users->result_array();
    }

    //  method for get user data from email
    public function findUserByEmail($email) {
        $user = $this->db->get_where($this::TABLE_NAME, array('email' => $email));

        return $user->row_array();
    }

    // method for checking user is exists or not
    public function userIsExists($email) {
        if (!empty($this->UserModel->findUserByEmail($email)))
            return true;
        return false;
    }

    //  method for get table name from this model
    public function getTableName() {
        return $this::TABLE_NAME;
    }
}