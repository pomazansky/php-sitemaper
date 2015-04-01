<?php

namespace PhpSitemaper;

use PhpSitemaper\Views\View;

/**
 * Class Router
 * @package Sitemap
 */
class Router
{
    /**
     * Static instance
     *
     * @var Router
     */
    private static $instance;

    /**
     * Array of routes
     *
     * @var array
     */
    private $routes = [];

    /**
     * Defines constructor as protected
     */
    protected function __construct()
    {
    }

    /**
     * Makes object coping impossble
     */
    protected function __clone()
    {
    }

    /**
     * Return self static instance
     *
     * @return Router
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Returns array of routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Adds a route
     *
     * @param string $method
     * @param string $uri
     * @param array $ca
     * @return bool
     */
    public function add($method = 'get', $uri = '/', array $ca = [])
    {

        $method = strtolower($method);

        if (!empty($this->routes[$method][$uri])) {
            return false;
        }

        if (!array_key_exists(1, $ca)) {
            $ca[1] = 'index';
        }

        $this->routes[$method][$uri] = $ca;

        return true;
    }

    /**
     * Executes router
     */
    public function execute()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $uri = $this->getUri();

        if (array_key_exists($uri, $this->routes[$method])) {
            $ca = $this->routes[$method][$uri];
            $controller = ucfirst($ca[0]) . 'Controller';
            $action = !empty($ca[1]) ? $ca[1] . 'Action' : 'indexAction';

            $rc = new \ReflectionClass(__NAMESPACE__ . "\\Controllers\\$controller");
            if (!($rc->hasMethod($action))) {
                $action = 'indexAction';
            }
            $c = $rc->newInstance();
            $a = $rc->getMethod($action);
            $a->invoke($c);
        } else {
            $view = new View();
            header('HTTP/1.0 404 Not Found');
            $view->render404();
        }
    }

    /**
     * Return current request URI
     *
     * @return string
     */
    private function getUri()
    {
        $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $request = explode('/', str_replace(dirname($_SERVER['PHP_SELF']) . '/', '', $request_path));
        $request = array_diff($request, ['']);

        $uri = '/' . implode('/', $request);

        return $uri;
    }
}
