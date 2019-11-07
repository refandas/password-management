<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('UserModel');
    }

    public function index() {
        if ($this->session->is_logged == 1) {
            $data = array(
                'title'     => 'Dashboard'
            );

            $this->load->view('templates/header', $data);
            $this->load->view('templates/navbar');
            $this->load->view('templates/sidebar');
            $this->load->view('dashboard/index');
            $this->load->view('templates/main_footer');
            $this->load->view('templates/footer');
        }
        else {
            redirect('auth');
        }
    }

    public function users() {
        if ($this->session->access_level == 1) {
            $data = array(
                'title'     => 'Users'
            );

            $this->load->view('templates/header', $data);
            $this->load->view('templates/navbar');
            $this->load->view('templates/sidebar');
            $this->load->view('dashboard/users');
            $this->load->view('templates/main_footer');
            $this->load->view('templates/footer');
        }
        else {
            redirect('dashboard');
        }
    }

    public function user($id_user) {
        if ($this->session->access_level  == 1) {
            $data = array(
                'title'     => 'User Detail',
                'id_user'   => $id_user
            );

            $this->load->view('templates/header', $data);
            $this->load->view('templates/navbar');
            $this->load->view('templates/sidebar');
            $this->load->view('dashboard/user', $data);
            $this->load->view('templates/main_footer');
            $this->load->view('templates/footer');
        }
        else {
            redirect('dashboard');
        }
    }

    public function logout() {
        $userdata = array('email', 'access_level', 'name', 'is_logged');
        $this->session->unset_userdata($userdata);
        redirect('auth');
    }
}