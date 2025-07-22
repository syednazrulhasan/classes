<?php

namespace Client;

class Response
{
  public static function data($data){
    echo $data;
    exit();
  }
  public static function json($data){
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
  }
}