<?php


// TODO: 1. Validate session
// TODO: 2. Validate value

require_once __DIR__ . '/../vendor/autoload.php';

use Helper\SmartBoxController as sb;


use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

$request = Request::createFromGlobals();

$route = new Route('/submit/{parameters}', array('controller' => 'Helper\SmartBoxController', 'action' => 'index'));
$routes = new RouteCollection();
$routes->add('route_name', $route);

$context = new RequestContext('/');

$matcher = new UrlMatcher($routes, $context);

//$parameters = $matcher->match('/submit/asasa');
// array('controller' => 'MyController', '_route' => 'route_name')

$context->fromRequest(Request::createFromGlobals());
$matcher = new UrlMatcher($routes, $context);
try {
    $attributes = $matcher->match($request->getPathInfo());
    $controller = $attributes['controller'];
    unset($attributes['controller']);
    $response = call_user_func_array(array($controller, 'index'), array($request, $attributes));
} catch (ResourceNotFoundException $e) {
    $response = new JsonResponse('Not found!', JsonResponse::HTTP_NOT_FOUND);
} catch (\Exception $e) {
    // TODO: Add some logging in here
    $response = new JsonResponse('An error occurred', 500);
}

$response->headers->set('access-control-allow-origin', 'http://sb.imediat.eu');
$response->send();