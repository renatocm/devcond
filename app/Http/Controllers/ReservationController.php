<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\AreaDisabledDay;
use App\Models\Reservation;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReservationController extends Controller
{
    public function getReservation()
    {
        $array = ['error' => '', 'list' => []];
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
            foreach($dayList as $day) {
                if(intval($day) != $lastDay+1){
                    $dayGroups[] = $daysHelper[$lastDay];
                    $dayGroups[] = $daysHelper[$day];
                }

                $lastDay = intval($day);
            }

            //adicionando o último dia
            $dayGroups[] = $daysHelper[end($dayList)];

            //juntando as datas
            $dates = '';
            $close = 0;
            foreach ($dayGroups as $group) {
                if($close === 0){
                    $dates .= $group;
                }else{
                    $dates .= '-' . $group . ',';
                }
                $close = 1 - $close;
            }

            $dates = explode(',', $dates);
            array_pop($dates);

            //adicionando o time
            $start = date('H:i', strtotime($area['start_time']));
            $end = date('H:i', strtotime($area['end_time']));

            foreach ($dates as $dKey => $dValue) {
                $dates[$dKey] .= ' ' . $start . ' às ' . $end;
            }

            $array['list'][] = [
                'id' => $area['id'],
                'cover' => asset('storage/'.$area['cover']),
                'title' => $area['title'],
                'dates' => $dates
            ];

        }

        return $array;
    }

    public function setReservation($id, Request $request)
    {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i:s',
            'property' => 'required'
        ]);

        if(!$validator->fails()){
            $date = $request->input('date');
            $time = $request->input('time');
            $property = $request->input('property');

            $unit = Unit::find($property);
            $area = Area::find($id);

            if($unit && $area){
                $can = true;

                $weekday = date('w', strtotime($date));
                //verificar se está dentro da disponibilidade padrão (dia hora)
                $allowedDays = explode(',', $area['days']);

                if(!in_array($weekday, $allowedDays)){
                    $can = false;
                }else{
                    $start = strtotime($area['start_time']);
                    $end = strtotime('-1 hour', strtotime($area['end_time']));
                    $reservationtime = strtotime($time);
                    if($reservationtime < $start || $reservationtime > $end){
                        $can = false;
                    }
                }

                //verificar se está fora dos diasbledays
                $existingDisabledDay = AreaDisabledDay::where('id_area', $id)
                                        ->where('day', $date)
                                        ->count();
                if($existingDisabledDay > 0){
                    $can = false;
                }

                //verificar se não existe outra reserva no mesmo dia hora
                $existingReservation = Reservation::where('id_area', $id)
                                        ->where('reservation_date', $date . ' ' . $time)
                                        ->count();
                if($existingReservation > 0){
                    $can = false;
                }

                if($can){
                    $newReservation = new Reservation();
                    $newReservation->id_unit = $property;
                    $newReservation->id_area = $id;
                    $newReservation->reservation_date = $date . ' ' . $time;
                    $newReservation->save();
                }else{
                    $array['error'] = 'Reserva não permitida neste dia/horário';
                }
            }else{
                $array['error'] = "Dados incorretos";
                return $array;
            }
        }else{
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function getDisabledDates($id)
    {
        $array = ['error' => '', 'list' => []];
        $area = Area::find($id);

        if($area){
            // Dias disabled padrão
            $disabledDays = AreaDisabledDay::where('id_area', $id)->get();

            foreach($disabledDays as $disableDay){
                $array['list'][] = $disableDay['day'];
            }

            // Dias disabled através do allowed
            $allowedDays = explode(',', $area['days']);
            $offDays = [];
            for($q=0; $q<7; $q++){
                if(!in_array($q, $allowedDays)){
                    $offDays[] = $q;
                }
            }

            //listar os dias proibidos +3 meses para frente
            $start = time();
            $end = strtotime('+3 months');

            for(
                $current = $start;
                $current < $end;
                $current = strtotime('+1 day', $current)
            ){
                $weekDay = date('w', $current);
                if(in_array($weekDay, $offDays)){
                    $array['list'][] = date('Y-m-d', $current);
                }
            }
        }else{
            $array['error'] = "Área inexistente";
            return $array;
        }

        return $array;
    }


    public function getTimes($id, Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $validator = Validator::make($request->all(),[
            'date' => 'required|date_format:Y-m-d'
        ]);

        if(!$validator->fails()){
            $date = $request->input('date');
            $area = Area::find($id);

            if($area){
                $can = true;

                //Verificar se é dia disabled
                $existingDisabledDay = AreaDisabledDay::where('id_area', $id)
                                        ->where('day', $date)
                                        ->count();
                if($existingDisabledDay > 0){
                    $can = false;
                }
                //Verificar se é dia permitido

                $allowedDays = explode(',', $area['days']);
                $weekDay = date('w', strtotime($date));

                if(!in_array($weekDay, $allowedDays)){
                    $can = false;
                }

                if($can){
                    $start = strtotime($area['start_time']);
                    $end = strtotime($area['end_time']);
                    $times = [];

                    for(
                        $lastTime = $start;
                        $lastTime < $end;
                        $lastTime = strtotime("+1 hour", $lastTime)
                    ){
                        $times[] = $lastTime;
                    }

                    $timeList = [];
                    foreach($times as $time){
                        $timeList[] = [
                            'id' => date('H:i:s', $time),
                            'title' => date('H:i', $time) . ' - ' . date('H:i', strtotime('+1 hour', $time))
                        ];
                    }

                    // removendo as reservas

                    $reservations = Reservation::where('id_area', $id)
                                    ->whereBetween('reservation_date', [
                                        $date. ' 00:00:00',
                                        $date. ' 23:59:59'
                                    ])->get();

                    $toRemove = [];

                    foreach($reservations as $reservation){
                        $time = date('H:i:s', strtotime($reservation['reservation_date']));
                        $toRemove[] = $time;
                    }

                    foreach($timeList as $timeItem){
                        if(!in_array($timeItem['id'], $toRemove)){
                            $array['list'][] = $timeItem;
                        }

                    }


                }
            }else{
                $array['error'] = "Área inexistente";
                return $array;
            }
        }else{
            $array['error'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function getMyReservation(Request $request)
    {
        $array = ['error' => '', 'list' => []];

        $property = $request->input('property');

        if($property){
            $unit = Unit::find($property);
            if($unit){
                $reservations = Reservation::where('id_unit', $property)
                ->orderBy('reservation_date', 'DESC')
                ->get();

                foreach($reservations as $reservation){
                    $area = Area::find($reservation['id_area']);

                    $daterev = date('d/m/Y H:i', strtotime($reservation['reservation_date']));
                    $aftertime = date('H:i', strtotime('+1 hour', strtotime($reservation['reservation_date'])));
                    $daterev .= ' à ' . $aftertime;

                    $array['list'][] = [
                        'id' => $reservation['id'],
                        'id_area'  => $reservation['id_area'],
                        'title' => $area['title'],
                        'cover' => asset('storage/' .$area['cover']),
                        'datereserved' => $daterev
                    ];
                }
            }else{
                $array['error'] = "Unidade necessária";
                return $array;
            }
        }else{
            $array['error'] = "Propriedade necessária";
            return $array;
        }

        return $array;
    }

    public function delMyReservation($id)
    {
        $array = ['error' => ''];

        $user = auth()->user();
        $reservation = Reservation::find($id);
        if($reservation){
            $unit = Unit::where('id', $reservation['id_unit'])
            ->where('id_owner', $user['id'])
            ->count();

            if($unit >0 ){
                Reservation::find($id)->delete();
            }else{
                $array['error'] = 'Esta reserva não é sua';
                return $array;
            }
        }else{
            $array['error'] = "Reserva inexistente";
            return $array;
        }

        return $array;
    }


}
