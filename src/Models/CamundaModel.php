<?php

namespace Wertmenschen\CamundaApi\Models;

use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

abstract class CamundaModel
{
    protected $client;
    public $id;
    public $key;

    public function __construct($id = null, $attributes = [])
    {
        $this->client = new Client([
            'base_uri' => Config::get('camunda.api.url')
        ]);

        $this->id = $id;

        foreach($attributes as $key => $value) {
            $this->{$key} = $value;
        }
    }

    protected function post($url, $data = [], $json = false)
    {
        return $this->request($url, 'post', $this->getData($data, $json));
    }

    protected function put($url, $data = [], $json = false)
    {
        return $this->request($url, 'put', $this->getData($data, $json));
    }

    protected function delete($url, $data = [], $json = false)
    {
        return $this->request($url, 'delete', $this->getData($data, $json));
    }

    protected function getData($data, $json)
    {
        if(Arr::has($data, 'multipart')) {
            return $data;
        }
        elseif($json) {
            return ['json' => $data];
        }
        else {
            return array_merge(['json' => ['a' => 'b']], $data);
        }
    }

    protected function get($url)
    {
        return $this->request($url, 'get');
    }

    private function request($url, $method, $data = [])
    {
        $data['auth'] = [Config::get('camunda.api.auth.user'), Config::get('camunda.api.auth.password')];

        $response = $this->client->{$method}($this->buildUrl($url), $data);
        return json_decode($response->getBody());
    }

    private function buildUrl($url)
    {
        $modelUri = (empty($this->id) && empty($this->key)) || str_contains($url, '?') ? '' : $this->modelUri() . '/';
        return 'engine-rest/' . $modelUri . $url;
    }

    private function modelUri()
    {
        if($this->key) {
            return Str::kebab(class_basename($this)) . '/key/' . $this->key . $this->tenant();
        }
        else {
            return Str::kebab(class_basename($this)) . '/' . $this->id;
        }
    }

    protected function tenant()
    {
        return strlen(Config::get('camunda.api.tenant-id')) ? '/tenant-id/' . Config::get('camunda.api.tenant-id') : '';
    }
}