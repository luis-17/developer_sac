<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

$settings = require __DIR__ . '/settings.php';
$app = new \Slim\App($settings);
$container = $app->getContainer();

$container['view'] = new \Slim\Views\PhpRenderer("templates/");

$app->get('/empleados', function (Request $request, Response $response) { 
	$listaEmpleados = json_decode(file_get_contents('employees.json'), true);
    $response = $this->view->render($response, "lista-empleados.phtml", ["empleados" => $listaEmpleados, "router" => $this->router, 'search'=> false]);
    return $response;
})->setName("empleados");

$app->get('/empleados/{id}', function (Request $request, Response $response, $args) { 
    $listaEmpleados = json_decode(file_get_contents('employees.json'), true);
	$filtroBusqueda = $args['id'];
    $fEmpleado = null;
    foreach ($listaEmpleados as $key => $row) { 
        if($row['id'] == $filtroBusqueda){
        	$fEmpleado = $row;
        }
    }
    return $this->view->render($response, 'detalle-empleado.phtml', ['fEmpleado' => $fEmpleado, "router" => $this->router]);
})->setName("empleado-detail");

$app->post('/empleados/busqueda', function (Request $request, Response $response) {
    $listaEmpleados = json_decode(file_get_contents('employees.json'), true);
    $mail = $request->getParam('mail');
    if( empty($mail) ){
    	return $this->view->render($response, 'lista-empleados.phtml', ['empleados' => $listaEmpleados, "router" => $this->router]);
    	exit();
    }
    foreach ($listaEmpleados as $key => $row) { 
    	$valido = strpos($row['email'], $mail); 
        if($valido === FALSE){
        	unset($listaEmpleados[$key]);
        }
    }
    return $this->view->render($response, 'lista-empleados.phtml', ['empleados' => $listaEmpleados, "router" => $this->router, 'search'=> true]);
})->setName("empleados-busqueda");

$app->post('/empleados/salario', function (Request $request, Response $response, $args) { 
	$listaEmpleados = json_decode(file_get_contents('employees.json'), true);
    $start = convertirAFlotante($request->getParam('desde'));
    $end = convertirAFlotante($request->getParam('hasta'));
    // if( empty($start) || empty($end) ){
    // 	$response = $this->view->render($response, "lista-empleados.phtml", ["empleados" => $listaEmpleados, "router" => $this->router, 'search'=> false]);
    // 	return $response; exit(); 
    // }
    foreach ($listaEmpleados as $key => $row) { 
        if ( !($start <= convertirAFlotante($row['salary']) && $end >= convertirAFlotante($row['salary'])) ) { 
            //$employee[] = $value;
            unset($listaEmpleados[$key]);
        }
    } 
    $newResponse = $response->withHeader('Content-type', 'application/xml');
    return $this->view->render($newResponse, 'salario.phtml', array('empleados' => $listaEmpleados));
})->setName('empleado_salario');

function convertirAFlotante($num)
{
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
    );
}

$app->run();