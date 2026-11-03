<?php require_once __DIR__.'/../services/MercadoPago.php'; $u=require_auth(); json_response(['plans'=>plans_live()]);
