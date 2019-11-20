<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|   This controller for manage user account
|   Can add account, update, delete and other like
*/

class Account extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('AccountModel');
    }

    /**
     * Add account, connected with model
     */
    public function add() {
        $id_user        = $this->session->userdata('id_user');
        $username       = $this->input->post('username_email');
        $password       = $this->input->post('password');
        $account_media  = $this->input->post('media');

        $data = array(
            'id_user'              => $id_user,
            'username_email'    => $username,
            'password'          => $password,
            'id_media'          => $account_media
        );

        $this->AccountModel->addAccount($data);
        redirect('account');
    }

    /**
     * Update account, connected with model
     *
     * @param $data
     */
    public function update($id_account) {
        $username_email = $this->input->post('username_email');
        $password       = $this->input->post('password');
        $data           = $this->AccountModel->getAccountByID($id_account);

        $data['username_email'] = $username_email;
        $data['password']       = $password;

        $this->AccountModel->editAccount($data);
        redirect('account');
    }

    /**
     * Delete account, connected with model
     *
     * @param $id_account
     */
    public function delete($id_account) {
        $this->AccountModel->deleteAccount($id_account);
        $this->session->set_flashdata('success', '<div style="color: #00c054">Delete account successfully.</div>');

        redirect('account');
    }

    /**
     * Show list of account
     * Can redirect to action button for edit or delete
     */
    public function index() {
        $id_user  = $this->session->userdata('id_user');  // get user id by session
        $accounts = $this->AccountModel->getAccountByUserID($id_user);

        if ($this->session->is_logged == 1) {
            $data = array(
                'title'     => 'Account',
                'accounts'  => $accounts
            );

            $this->load->view('templates/header', $data);
            $this->load->view('templates/navbar');
            $this->load->view('templates/sidebar');
            $this->load->view('account/index', $data);
            $this->load->view('templates/main_footer');
            $this->load->view('templates/footer');
        }
        else {
            redirect('auth');
        }
    }

    /**
     * Show form for add account view
     * Used for adding account
     */
    public function add_account() {
        $account_media = $this->AccountModel->getAccountMedia();

        if ($this->session->is_logged == 1) {
            $data = array(
                'title'         => 'Add Account',
                'account_media' => $account_media
            );

            $this->load->view('templates/header', $data);
            $this->load->view('templates/navbar');
            $this->load->view('templates/sidebar');
            $this->load->view('account/add_account', $data);
            $this->load->view('templates/main_footer');
            $this->load->view('templates/footer');
        }
    }

    /**
     * Show form for detail account view
     * Used for edit or delete account, based on id account.
     *
     * @param $id_account
     */
    public function detail_account($id_account) {
        $account = $this->AccountModel->getAccountByID($id_account, TRUE);

        $data = array(
            'title'         => 'Detail Account',
            'account'       => $account
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/navbar');
        $this->load->view('templates/sidebar');
        $this->load->view('account/detail_account', $data);
        $this->load->view('templates/main_footer');
        $this->load->view('templates/footer');
    }

}
