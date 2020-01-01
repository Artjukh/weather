<?php

declare(strict_types=1);

use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\Between;
use Phalcon\Mvc\Dispatcher\Exception as PhDispatchException;

class WeatherController extends \Phalcon\Mvc\Controller
{

    public function valid()
    {
        $validator = new Validation();
        $validator->add(
            [
            "lat",
            "lon"
            ],
            new Between(
                [
                    "minimum" => [
                        "lat"  => -90,
                        "lon" => -180,
                    ],
                    "maximum" => [
                        "lat"  => 90,
                        "lon" => 180,
                    ],
                    "message" => [
                        "lat"  => "The latitude must be between -90 and 90",
                        "lon" => "The longitude must be between -180 and 180",
                    ],
                ]
            )
        ); 
        $msg = $validator->validate($_POST);
        if (count($msg)) {
            return $msg[0]->getMessage();
        }
        return null;
    }

    public function indexAction()
    {
        
        $request = new Request();
        if (true === $request->isPost()) {
            $name = $request->get('name', null);                // Minsk
            $latitude = $request->get('lat', null);        //'40.714224 coordinaties with google maps 
            $longitude = $request->get('lon', null);      //'-73.961452 coordinaties with google maps
            if ($latitude != null && $longitude != null) {
                $valid = $this->valid();
                if ($valid != null) {
                    return $valid;
                }
                $reFormatLatitude = explode('.', $latitude);
                $reFormatLongitude = explode('.', $longitude);
            }
        $weather = $this->getWeather($name, $reFormatLatitude[0], $reFormatLongitude[0]);
       
        return $this->createResponse($weather);;
        }
    }

    protected function createResponse($content)
    {
        $response = new Response();
        $response->setContentType('application/json', 'UTF-8');
        $response->setContent($content);
        return $response;
    }
    protected function getWeather($name = null, $latitude = null, $longitude = null)
    {
        if ($name) {
            strtolower($name);
            if ($this->redis->get($name)) {
                return json_decode($this->redis->get($name));
            }
            return $this->reqWeatherApi($name);
        }
        if ($this->redis->get("$latitude,$longitude")) {
            return json_decode($this->redis->get("$latitude,$longitude"));
        }
        return $this->reqWeatherApi(null, $latitude, $longitude);
    }

    protected function reqWeatherApi($name = null, $latitude = null, $longitude = null)
    {

        $curl = curl_init();
        // Create url for API openweathermap 
        $curl_url = $name ? "api.openweathermap.org/data/2.5/weather?q=$name&APPID=" . APPID : "api.openweathermap.org/data/2.5/weather?lat=$latitude&lon=$longitude&APPID=" . APPID;

        curl_setopt($curl, CURLOPT_URL, $curl_url);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-type: application/json'
            )
        );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $res = curl_exec($curl);
        if ($name != null) {
            $this->saveCity($name);
            $this->redis->set($name, json_encode($res)); // redis cache 
            return $res;
        }
        $this->saveCity("$latitude,$longitude");
        $this->redis->set("$latitude,$longitude", json_encode($res)); // redis cache
        return $res;
    }

    protected function saveCity($name)
    {
        $newCity = new Cities;
        $newCity->session_id =  $this->session->getId();
        $newCity->city = $name;
        $newCity->save();
    }

    public function getWeatherAction()
    {
        $query = $this->modelsManager->createQuery(
                'SELECT cities.city FROM cities WHERE cities.session_id = :session_id:'
            )
        ;
        $list = $query->execute(
            [
                'session_id' => $this->session->getId(),
            ]
        );
        return $this->createResponse(json_encode($list));
    }
}
