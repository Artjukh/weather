<?php 
use Phalcon\Http\Response;

class ResponseHelper {

    public function createResponse($content)
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');
        $response->setContent($content);
        return $response;
    }

    public function errorHandler($e)
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');
        $response->setContent($e->getMessage());
        return $response;
    }
}