<?php

namespace API\Support;

use Exception;

class Request
{
    protected $client;

    public function __construct(Authentication $authentication){
        $this->client = $authentication->returnClient();
    }

    public function get($end_point, array $parameters = [])
    {
        try {
            return $this->client->request('GET', $end_point, $parameters);
        } catch (Exception $e) {
            return $e->getResponse();
        }
    }

    public function post($end_point, array $parameters)
    {
        try {
            return $this->client->request('POST', $end_point, $parameters);
        } catch (Exception $e) {
            return $e->getResponse();
        }
    }

    public function put($end_point, array $parameters)
    {
        try {
            return $this->client->request('PUT', $end_point, $parameters);
        } catch (Exception $e) {
            return $e->getResponse();
        }
    }

    public function patch($end_point, $fields, array $parameters)
    {
        try {
            return $this->client->request('PATCH', $end_point, $parameters);
        } catch (Exception $e) {
            return $e->getResponse();
        }
    }

    public function delete($end_point, array $parameters = [])
    {
        try {
            return $this->client->request('DELETE', $end_point, $parameters);
        } catch (Exception $e) {
            return $e->getResponse();
        }
    }

    private function prepareFields($fields)
    {
        $return = [];
        foreach ($fields as $key => $value) {
            if ($value != [] && $value != '') {
                if (is_array($value)) {
                    foreach ($value as $sub_key => $object) {
                        if (is_object($object)) {
                            if (is_array($fields[$key][$sub_key])) {
                                $return[$key][$sub_key][] = $object->getAttributes();
                            } else {
                                $return[$key][$sub_key] = $object->getAttributes();
                            }
                        } else {
                            if (is_array($fields[$key][$sub_key])) {
                                $return[$key][$sub_key][] = $object;
                            } else {
                                $return[$key][$sub_key] = $object;
                            }
                        }
                    }
                } else {
                    if (is_object($value)) {
                        if (is_array($fields[$key])) {
                            $return[$key][] = $value->getAttributes();
                        } else {
                            $return[$key] = $value->getAttributes();
                        }
                    } else {
                        if (is_array($fields[$key])) {
                            $return[$key][] = $value;
                        } else {
                            $return[$key] = $value;
                        }
                    }
                }
            }
        }

        return json_encode($return);
    }
}
