<?php
use Phalcon\Validation;
use Phalcon\Validation\Validator\Between;

class Cities extends \Phalcon\Mvc\Model
{

    public $id;
    public $session_id;
    public $city;

    public function valid()
    {
        $validator = new Validation();
        $validator->add(
            [
                "lat",
                "lon",
            ],
            new Between(
                [
                    "minimum" => [
                        "lat" => -90,
                        "lon" => -180,
                    ],
                    "maximum" => [
                        "lat" => 90,
                        "lon" => 180,
                    ],
                    "message" => [
                        "lat" => "The latitude must be between -90 and 90",
                        "lon" => "The longitude must be between -180 and 180",
                    ],
                ]
            )
        );
        $msg = $validator->validate($_POST);
        if (count($msg)) {
            return ResponseHelper::errorHandler($msg[0]);
        }
        return true;
    }
}