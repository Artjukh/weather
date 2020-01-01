<?php

declare (strict_types = 1);

use Phalcon\Http\Request;
use ResponseHelper;

class WeatherController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $request = new Request();
        if (true === $request->isPost()) {
            $name = $request->get('name', null, false); // Minsk
            $latitude = $request->get('lat', null, false); //'40.714224 coordinaties with google maps
            $longitude = $request->get('lon', null, false); //'-73.961452 coordinaties with google maps
            if ($latitude && $longitude && Cities::valid()) {
                $reFormatLatitude = explode('.', $latitude);
                $reFormatLongitude = explode('.', $longitude);
            }
            return ResponseHelper::createResponse($this->getWeatherByData($name, $reFormatLatitude[0], $reFormatLongitude[0]));
        }
    }

    // Get list cities from db , error handing
    public function getWeatherAction()
    {
        try {
            $query = $this->modelsManager->createQuery('SELECT cities.city FROM cities WHERE cities.session_id = :session_id:');
            $list = $query->execute([ 'session_id' => $this->session->getId() ]);
            return ResponseHelper::createResponse(json_encode($list));
        } catch(Exception $e) {
            return ResponseHelper::errorHandler($e);
        }
    }

    // Validation data
    public function valid()
    {
        return Cities::valid();
    }

    // Check if the weather exist in redis, req API if not
    protected function getWeatherByData($name = false, $latitude = false, $longitude = false)
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
        return $this->reqWeatherApi(false, $latitude, $longitude);
    }

    //Request openweathermap API and save db\redis
    protected function reqWeatherApi($name = false, $latitude = false, $longitude = false)
    {
        $curl = curl_init();
        // Create url for API openweathermap
        if ($name) {
            $curl_url = "api.openweathermap.org/data/2.5/weather?q=$name&APPID=" . APPID;
        } elseif ($latitude && $longitude) {
            $curl_url = "api.openweathermap.org/data/2.5/weather?lat=$latitude&lon=$longitude&APPID=" . APPID;
        }

        curl_setopt($curl, CURLOPT_URL, $curl_url);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: application/json',
                'Content-type: application/json',
            )
        );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $res = curl_exec($curl);
        curl_close($curl);
        if ($name) {
            $this->saveCity($name, json_encode($res));
            return $res;
        }
        $this->saveCity("$latitude,$longitude", json_encode($res));
        return $res;
    }

    protected function saveCity($name, $res)
    {
        try {//Save in db
            $newCity = new Cities;
            $newCity->session_id = $this->session->getId();
            $newCity->city = $name;
            $newCity->save();
            //Save in redis
            $this->redis->set($name, $res);
        } catch (Exception $e) {
            return ResponseHelper::errorHandler($e);
        }

    }

}
