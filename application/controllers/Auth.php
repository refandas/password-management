<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('UserModel');
    }

	/**
	 * method for create new user
	 */
	public function create() {
        $name       = $this->input->post('name');
        $email      = $this->input->post('email');
        $password   = $this->input->post('password');

        $data = array(
            'name'      => $name,
            'email'     => $email,
            'password'  => $password
        );

        if ( $this->UserModel->registration($data) ) {
            $this->session->set_flashdata('success', '<div style="color: #00c054">Registration success! Please activate account before login.</div>');
            redirect('auth');
        } else {
            redirect('auth/register');
        }
    }

	/**
	 * mehod for authenticate that login is success or not
	 * param for authenticate is email and password => authenticate(email, passoword)
	 * if login is valid, make session that contains: id_user, access_level, and is_logged
	 * if login is valid redirect to dashboard
	 */
	public function login() {
        $email      = $this->input->post('email');
        $password   = $this->input->post('password');

        $user           = $this->UserModel->findUserByEmail($email);
        $login_valid    = $this->UserModel->authenticate($email, $password);

        if ($login_valid && $user['registered'] == 1) {
            $userdata = array(
                'id_user'       => $user['id_user'],
                'name'          => $user['name'],
                'access_level'  => $user['access_level'],
                'is_logged'     => TRUE
            );
            $this->session->set_userdata($userdata);
            redirect('dashboard');
        } else {
            if ($user == 0) {
                $this->session->set_flashdata('failed', '<div style="color: #a71d2a">This account is not registered!</div>');
            } else if (!$login_valid) {
                $this->session->set_flashdata('failed', '<div style="color: #a71d2a">Incorrect password!</div>');
            }
        }
        redirect('auth');
    }

	/**
	 * method for set new password
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
        redirect('auth');
    }

	/**
	 * method for show login form
	 */
	public function index() {
        $data = array(
            'title'     => 'Login - Password Management'
        );

        $this->load->view('templates/header', $data);
        $this->load->view('auth/login');
        $this->load->view('templates/footer');
    }

	/**
	 * method for show forgot password form
	 */
	public function forgotPassword() {
        $data = array(
            'title'     => 'Forgot Password'
        );

        $this->load->view('templates/header', $data);
        $this->load->view('auth/forgot');
        $this->load->view('templates/footer');
    }

	/**
	 * method for show register form
	 */
	public function register() {
        $data = array(
            'title'     => 'Register Now!'
        );

        $this->load->view('templates/header', $data);
        $this->load->view('auth/register');
        $this->load->view('templates/footer');
    }

	/**
	 * method for show reset password form, check is user email exists or not
	 */
	public function resetPassword() {
        $email  = $this->input->post('email');
        $data = array(
            'title'     => 'Reset Password',
            'user'      => $user   = $this->UserModel->findUserByEmail($email)
        );

        if ($this->UserModel->userIsExists($email)) {
            $this->load->view('templates/header', $data);
            $this->load->view('auth/set_password', $data);
            $this->load->view('templates/footer');
        } else {
            $this->session->set_flashdata('failed', '<div style="color: #a71d2a">Email not found</div>');
            redirect('auth/forgotpassword');
        }
    }

	/**
	 * method for activate user
	 * @param $id_user
	 */
	public function activateUser($id_user) {
    	if ($this->UserModel->activateUser($id_user)) {
	    	$this->session->set_flashdata('success', '<div style="color: #00c054">Aktivasi akun berhasil</div>');
		} else {
			$this->session->set_flashdata('failed', '<div style="color: #a71d2a">Aktivasi akun gagal</div>');
		}
    	redirect('dashboard/users');
	}

	/**
	 * method for deactivate user
	 * @param $id_user
	 */
	public function deactivateUser($id_user) {
    	if ($this->UserModel->disableUser($id_user)) {
			$this->session->set_flashdata('success', '<div style="color: #00c054">Deaktivasi akun berhasil</div>');
    	} else {
			$this->session->set_flashdata('failed', '<div style="color: #a71d2a">Deaktivasi akun gagal</div>');
		}
		redirect('dashboard/users');
	}
}
