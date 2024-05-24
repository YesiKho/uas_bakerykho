<?php

require 'app/helpers/Flasher.php';
require 'app/helpers/FormAlert.php';

// Untuk Menampilkan Tampilan
function view($page, $data = [])
{
    extract($data);
    $page = str_replace('.', '/', $page);
    include 'views/' . $page . '.php';
}

// untuk mengatur dan memanggil rute atau function yang sesuai url
class Route
{
    public static $urls = [];

    function __construct()
    {
        extract(parseQueryURL());
        var_dump(self::$urls);
        if (isset($_SESSION['auth'])) {
            if (!array_key_exists($uri, self::$urls[$_SERVER['REQUEST_METHOD']][$_SESSION['auth']['role']])) {
                header("Location: " . BASEURL . $_SESSION['auth']['baseurl']);
                exit;
            }
            $call = self::$urls[$_SERVER['REQUEST_METHOD']][$_SESSION['auth']['role']][$uri];
            $call($params);
        } else {
            if (!array_key_exists($uri, self::$urls[$_SERVER['REQUEST_METHOD']])) {
                header("Location: " . BASEURL);
                exit;
            }
            $call = self::$urls[$_SERVER['REQUEST_METHOD']][$uri];
            $call($params);
        }

        Flasher::destroyFlash();
        FormAlert::destroyFormAlert();
    }

    // membuat rute dengan method GET 
    public static function get($url, $controller)
    {
        if ($url == '/') {
            $url = '';
        }
        self::$urls[strtoupper('GET')][$url] = $controller;
        return ["urls" => [$url], "controllers" => array($controller)];
    }

    // membuat rute dengan method POST 
    public static function post($url, $controller)
    {
        if ($url == '/') {
            $url = '';
        }
        self::$urls[strtoupper('POST')][$url] = $controller;
    }

    // membuat rute dengan kebutuhan lengkap CRUD
    public static function resource($url, $controller)
    {
        if ($url == '/') {
            $url = '';
        }

        $index = ["url" => $url, "controller" => $controller . '::index'];
        $create = ["url" => $url . '/create', "controller" => $controller . '::create'];
        $store = ["url" => $url . '/store', "controller" => $controller . '::store'];
        $edit = ["url" => $url . '/edit', "controller" => $controller . '::edit'];
        $update = ["url" => $url . '/update', "controller" => $controller . '::update'];
        $destroy = ["url" => $url . '/destroy', "controller" => $controller . '::destroy'];

        // index
        self::$urls[strtoupper('GET')][$index['url']] = $index['controller'];
        // create
        self::$urls[strtoupper('GET')][$create['url']] = $create['controller'];
        // store
        self::$urls[strtoupper('POST')][$store['url']] = $store['controller'];
        // edit
        self::$urls[strtoupper('GET')][$edit['url']] = $edit['controller'];
        // update
        self::$urls[strtoupper('POST')][$update['url']] = $update['controller'];
        // destroy
        self::$urls[strtoupper('POST')][$destroy['url']] = $destroy['controller'];

        return [
            "urls" => array($index['url'], $create['url'], $store['url'], $edit['url'], $update['url'], $destroy['url']),
            "controllers" => array($index['controller'], $create['controller'], $store['controller'], $edit['controller'], $update['controller'], $destroy['controller'])
        ];
    }

    // untuk mengatur hak akses pengguna atau role
    public static function middleware($middleware, $routes = [])
    {
        $urls_method = array_keys(self::$urls);
        $url_routes = [];
        $controller_routes = [];

        foreach ($routes as $route) {
            $url_routes = array_merge($url_routes, $route["urls"]);
            $controller_routes = array_merge($controller_routes, $route["controllers"]);
        }

        foreach ($url_routes as $url_route) {
            foreach ($urls_method as $url_method) {
                if (array_key_exists($url_route, self::$urls[$url_method])) {
                    if (!array_key_exists($middleware, self::$urls[$url_method])) {
                        self::$urls[$url_method][strtoupper($middleware)][$url_route] = $controller_routes[array_search($url_route, $url_routes)];
                    } else {
                        array_push(self::$urls[$url_method][strtoupper($middleware)][$url_route], $controller_routes[array_search($url_route, $url_routes)]);
                    }
                    unset(self::$urls[$url_method][$url_route]);
                }
            }
        }
        self::$urls[strtoupper('POST')][strtoupper($middleware)]['login/destroy'] = 'AuthenticatedSessionController::destroy';
    }
}

// untuk mengatur url agak dapat digunakan sesuai controllernya
function parseQueryURL()
{
    $uri = '';
    $params = '';
    $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    if (isset($url)) {
        $url = strtolower(implode(
            "/",
            array_filter(
                explode(
                    "/",
                    str_replace(
                        BASEDIR,
                        "",
                        parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY)
                    )
                ),
                'strlen'
            )
        ));

        $url = str_replace(BASEDIR, "", $url);
        $url = strtolower($url);
        $url = explode("?", $url);

        if (count($url) > 1) {
            $uri = $url[0];
            $params = parseParams($url[1]);
        } else {
            $uri = $url[0];
        }
    }

    return array('uri' => $uri, 'params' => $params);
}

// untuk mengkonversi paramater ex: product_id=1
function parseParams($param)
{
    $resParam = explode('=', $param); // $resParam = ['product_id','1']
    $resParam = array($resParam[0] => $resParam[1]); // $resParam = ['product_id' => '1']
    return $resParam;
}

// untuk mengantur rute seperti pada tag a attribute href
function route($path)
{
    require_once 'config/env.php';
    $path = str_replace('.', '/', $path);
    return BASEURL . $path;
}
