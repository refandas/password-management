<?php

class UserModel extends CI_Model {

    private const TABLE_NAME = "user";

	/**
	 * method for registration new user
	 * by default access level for most user that registered first time is "user",
	 * it means this limited to feature can accessed
	 * @param $data
	 * @return mixed
	 */
	public function registration($data) {
        $data['password']       = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['access_level']   = 2;
        $data['registered']		= 1;

        $user = $this->db->insert($this::TABLE_NAME, $data);
        return $user;
    }

	/**
	 * method for set password (reset password
	 * @param $data
	 * @return mixed
	 */
	public function setPassword($data) {
        $data['password']  = password_hash($data['password'], PASSWORD_DEFAULT);
        $this->db->where('id_user', $data['id_user']);
        $update_password = $this->db->update($this::TABLE_NAME, $data);

        return $update_password;
    }

	/**
	 * method for update user
	 * @param $data
	 * @return bool
	 */
	public function updateUser($data) {
        $this->db->where('id_user', $data['id_user']);

        if ($this->db->update($this::TABLE_NAME, $data)) {
        	return true;
		}
        return false;
    }

	/**
	 * method for delete user
	 * @param $id_user
	 */
	public function deleteUser($id_user) {
        $this->db->where('id_user', $id_user);
        $this->db->delete($this::TABLE_NAME);
    }

	/**
	 * method for activate user, just admin can access this
	 * @param $id_user
	 * @return bool
	 */
	public function activateUser($id_user) {
        $make_active = array('registered' => 1);

        $this->db->where('id_user', $id_user);
        if ($this->db->update($this::TABLE_NAME, $make_active)) {
        	return true;
		}
        return false;
    }

	/**
	 * method for disable user, just admin can access this
	 * @param $id_user
	 * @return bool
	 */
	public function disableUser($id_user) {
        $make_disable = array('registered' => 0);

        $this->db->where('id_user', $id_user);
        if ($this->db->update($this::TABLE_NAME, $make_disable)) {
        	return true;
		}
        return false;
    }

	/**
	 * method for authenticate
	 * @param $email
	 * @param $password
	 * @return bool
	 */
	public function authenticate($email, $password) {
        $user = $this->findUserByEmail($email);
        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

	/**
	 * method for get many user
	 * @return mixed
	 */
	public function getUsers() {
        $users = $this->db->get($this::TABLE_NAME);

        return $users->result_array();
    }

	/**
	 * method for get user data from email
	 * @param $email
	 * @return mixed
	 */
	public function findUserByEmail($email) {
        $user = $this->db->get_where($this::TABLE_NAME, array('email' => $email));

        return $user->row_array();
    }

	/**
	 * method for get user data from id_user
	 * @param $id_user
	 * @return mixed
	 */
	public function findUserByID($id_user) {
        $user = $this->db->get_where($this::TABLE_NAME, array('id_user' => $id_user));

        return $user->row_array();
    }

	/**
	 * method for checking user is exists or not
	 * @param $email
	 * @return bool
	 */
	public function userIsExists($email) {
        if (!empty($this->UserModel->findUserByEmail($email)))
            return true;
        return false;
    }

	/**
	 * method for get table name from this model
	 * @return string
	 */
	public function getTableName() {
        return $this::TABLE_NAME;
    }
}