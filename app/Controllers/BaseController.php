<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * Helpers loaded for every controller. `url` backs the ported legacy
     * views, which call base_url() throughout.
     */
    protected $helpers = ['url'];

    /**
     * @param CLIRequest|IncomingRequest $request
     *
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Ported CI3 redirect() status rule (system/helpers/url_helper.php): on
     * HTTP/1.1 a non-GET request answers 303 (Post/Redirect/Get) and a GET
     * answers 307; anything else answers 302. The oracle's status codes are
     * observable behavior — the port keeps them identical.
     */
    protected function legacyRedirect(string $uri): RedirectResponse
    {
        $code = 302;
        if (service('superglobals')->server('SERVER_PROTOCOL') === 'HTTP/1.1') {
            $code = $this->request->getMethod() !== 'GET' ? 303 : 307;
        }

        return redirect()->to($uri, $code);
    }

    /**
     * The legacy page sandwich: header template (with the page title), body
     * view, footer template — what every CI3 method rendered via
     * $this->load->view() triplets.
     */
    protected function page(string $view, string $title, array $data = []): string
    {
        return view('templates/header', ['title' => $title])
            . view($view, $data)
            . view('templates/footer');
    }

    /**
     * The legacy full-layout sandwich: header, navbar, sidebar, body view,
     * main_footer, footer — the six $this->load->view() calls the gated CI3
     * application pages made (dashboard, account, setting) — #35 moved
     * the dashboard landing stub over from page().
     */
    protected function layoutPage(string $view, string $title, array $data = []): string
    {
        return view('templates/header', ['title' => $title])
            . view('templates/navbar')
            . view('templates/sidebar')
            . view($view, $data)
            . view('templates/main_footer')
            . view('templates/footer');
    }
}
