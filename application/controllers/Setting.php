<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('UserModel');
    }

    /**
     * Update email and name
     *
     * @param $id_user
     */
    public function update($id_user) {
        $email  = $this->input->post('email');
        $name   = $this->input->post('name');

        $data = array(
            'id_user'   => $id_user,
            'email'     => $email,
            'name'      => $name
        );

        $this->UserModel->updateUser($data);
        redirect('setting');
    }

    /**
     * Reset password
     *
     * @param $id_user
     */
    public function setPassword($id_user) {
        $password   = $this->input->post('password');

        $data = array(
            'password'  => $password,
            'id_user'   => $id_user
        );

        $this->UserModel->setPassword($data);
        $this->session->set_flashdata('success', '<div style="color: #00c054">Reset password success.</div>');
        redirect('setting');
    }

    /**
     * Deactivate function, make user deactivate
     * It will change is_active to 0.
     * If action success, it will redirect to auth/login
     *
     * @param $id_user
     */
    public function deactivate($id_user) {
        $this->UserModel->disableUser($id_user);
        $this->session->set_flashdata('success', '<div style="color: #00c054">Account has been deactivated.</div>');
        redirect('auth');
    }

    /**
     * Deleting account
     *
     * @param $id_user
     */
    public function delete($id_user) {
        $this->UserModel->deleteUser($id_user);
        $this->session->set_flashdata('success', '<div style="color: #a71d2a">Account has been deleted</div>');
        redirect('auth');
    }

    public function index() {
        $id_user    = $this->session->userdata('id_user');
        $user       = $this->UserModel->findUserByID($id_user);

        $data = array(
            'title'     => 'Setting',
            'user'      => $user
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/navbar');
        $this->load->view('templates/sidebar');
        $this->load->view('setting/index', $data);
        $this->load->view('templates/main_footer');
        $this->load->view('templates/footer');
    }

    /**
     * Show form for retype new password for user
     */
    public function password($id_user) {
        $user = $this->UserModel->findUserByID($id_user);
        $data = array(
            'title'     => 'Change Password',
            'user'      => $user
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/navbar');
        $this->load->view('templates/sidebar');
        $this->load->view('setting/change_password', $data);
        $this->load->view('templates/main_footer');
        $this->load->view('templates/footer');
    }
}