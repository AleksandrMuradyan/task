<?php
/**
 * Copyright © BWX, LLC. All rights reserved.
 * Author © Aleksandr Muradyan <alik.muradyan92@gmail.com>
 */
namespace App\Http\Controllers;

use mysql_xdevapi\Exception;

class HomeController extends Controller
{
    public function index()
    {
        $file = file_get_contents(public_path().'/input.txt');
        $fileData =explode("\n", $file);
        $euArray =[ 'AT','BE','BG','CY','CZ','DE','DK','EE','ES','FI','FR','GR','HR','HU','IE','IT','LT','LU','LV','MT','NL','PO','PT','RO','SE','SI','SK'];
        foreach ( $fileData as $row) {
            $row =json_decode($row,true);
            if (isset($row['bin'])&&isset($row['amount'])&&isset($row['currency'])) {
                $binResults = file_get_contents(config('services.lookup') .$row['bin']);
                if (!$binResults)continue;
                $r = json_decode($binResults);
                $isEu = in_array($r->country->alpha2, $euArray);
                if ($row['currency'] !='EUR') {
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => config('services.apilayer'),
                        CURLOPT_HTTPHEADER => array(
                            "Content-Type: text/plain",
                            "apikey: ".config('services.api_key').""
                        ),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CUSTOMREQUEST => "GET"
                    ));
                    $response = curl_exec($curl);
                    curl_close($curl);
                    $response=json_decode($response,true);
                    if (isset($response['rates'][$row['currency']])) {
                        $amntFixed = $row['amount']/$response['rates'][$row['currency']];
                    }else{
                        $amntFixed = $row['amount'];
                    }
                }else{

                    $amntFixed = round($row['amount'],2);
                }
                echo $amntFixed * ($isEu ? 0.01 : 0.02);
                print "\n";
            }
        }
        return true;
    }
}
