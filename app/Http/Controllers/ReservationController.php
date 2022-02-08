<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function getReservation()
    {
        $array = ['error' => ''];
        $daysHelper = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $areas = Area::where('allowed', 1)->get();

        foreach($areas as $area){
            $dayList = explode(',', $area['days']);
            $dayGroups = [];

            //adicionando o primeiro dia
            $lastDay = intval(current($dayList));
            $dayGroups[] = $daysHelper[$lastDay];
            array_shift($dayList); //remove item da lista

            //adicionando dias relevantes

            //adicionando o último dia
            $dayGroups[] = $daysHelper[end($dayList)];

            echo "AREA {$area['title']} \n";
            print_r($dayGroups);
            echo "\n -------";
        }

        $array['list'] = $areas;

        return $array;
    }
}
