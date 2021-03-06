<?php
        /*
        The MIT License (MIT)

        Copyright (c) 2013 Marco Antonio Orellana Olivares - <morello.cl@outlook.com>

        Permission is hereby granted, free of charge, to any person obtaining a copy of
        this software and associated documentation files (the "Software"), to deal in
        the Software without restriction, including without limitation the rights to
        use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
        the Software, and to permit persons to whom the Software is furnished to do so,
        subject to the following conditions:

        The above copyright notice and this permission notice shall be included in all
        copies or substantial portions of the Software.

        THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
        IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
        FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
        COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
        IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
        CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
        */

        //echo "Iniciando Integración Puntopagos<br />";

        $config = parse_ini_file('puntopagos.ini', 1);

        $PUNTOPAGOS_URL = $config['puntopagos']['url'];
        $PUNTOPAGOS_KEY = $config['puntopagos']['key'];
        $PUNTOPAGOS_SECRET = $config['puntopagos']['secret'];        

        //echo "Key: ".$PUNTOPAGOS_KEY.'<br />';
        //echo "Secret: ".$PUNTOPAGOS_SECRET.'<br />';

        $funcion = "transaccion/crear";
        
        $trx_id = $_POST['nro_compra'];
        $monto = $_POST['monto'];
        $monto_str = number_format($monto, 2, '.', '');
        $medioPago = $_POST['medio_pago'];
        $detalle = $_POST['detalle'];
        
        $nombre_cli = $_POST['nombre'];
        $email_cli = $_POST['email'];

        if ($medioPago != '')
        {
                $data_paso1 = '{"trx_id":"'.$trx_id.'","medio_pago":"'.$medioPago.'","monto":'.$monto_str.',"detalle":"'.$detalle.'"}';
        }
        else
        {
                $data_paso1 = '{"trx_id":"'.$trx_id.'","monto":'.$monto_str.',"detalle":"'.$detalle.'"}'; 
        }

        $http_request = $data_paso1;
        $http_request .= "<br/><br/>";

        //echo "Data: ".$data_paso1."<br />";

        $fecha = gmdate("D, d M Y H:i:s", time())." GMT";
        $mensaje = $funcion."\n".$trx_id."\n".$monto_str."\n".$fecha;
        $signature = base64_encode(hash_hmac('sha1', $mensaje, $PUNTOPAGOS_SECRET, true));
        $firma = "PP ".$PUNTOPAGOS_KEY.":".$signature;

        //echo "Firma: ".$firma."<br />";

        $header = array();
        $http_request .= $header[] = "Accept: application/json;";
        $http_request .= $header[] = "Accept-Charset: utf-8;";
        $http_request .= $header[] = "Accept-Language: en-us,en;q=0.5";
        $http_request .= $header[] = "Content-type: application/json";
        $http_request .= $header[] = "Fecha: ".$fecha;
        $http_request .= $header[] = "Autorizacion: ".$firma;


        $url_pp = $PUNTOPAGOS_URL."/transaccion/crear";

        //echo "URL: ".$url_pp."<br />";

        $curl = curl_init($url_pp);
        curl_setopt($curl, CURL_VERSION_SSL,"SSL_VERSION_SSLv3"); //optativo
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURL_HTTP_VERSION_1_1, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_paso1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        //echo 'Iniciar Puntopagos /transaccion/crear<br />';
        $http_response = curl_exec ($curl);

        $err = curl_errno($curl);
        $errmsg = curl_error($curl);

        //echo 'Error: '.$err.' '.$errmsg.'<br />';
        
        //echo 'Resp: <p>'.$http_response.'</p><br />';

        $response = json_decode($http_response, true);
        curl_close($curl);

        //echo 'Fin Puntopagos /transaccion/crear<br />';

        //echo 'jsonResp: '.$response.'<br />';

        //http_redirect("https://sandbox.puntopagos.com/transaccion/procesar/".$response['token']);

        if($response['token'] != ""){
                //http_redirect("https://sandbox.puntopagos.com/transaccion/procesar/".$response['token']);
                //echo "El token es: ".$response->token;
                $token = $response['token'];
                setcookie('1'.$token.'1', $monto_str, time() + 3600);
                setcookie('1'.$token.'2', $trx_id, time() + 3600);
                setcookie('1'.$token.'3', $nombre_cli, time() + 3600);
                setcookie('1'.$token.'4', $email_cli, time() + 3600);
                //setcookie($token.'3', $PUNTOPAGOS_SECRET, time() + 3600);
                header("Location: ".$PUNTOPAGOS_URL."/transaccion/procesar/".$token);
        }else{
                echo "<h2>Ha ocurrido un error.</h2> <h3>Error (".$err."): ".$errmsg."</h3><br/>Error PP:".$response['error'];
        }
?>