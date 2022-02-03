<?php

namespace App\Http\Controllers;

use App\Models\Warning;
use App\Models\Unit;
use Illuminate\Http\Request;

class WarningController extends Controller
{
    public function getMyWarnings(Request $request)
    {
        $array = ['error' => ''];

        $property = $request->input('property');

        if($property){

            $user = auth()->user();
            $unit = Unit::where('id', $property)->where('id', $user['id'])->count();

            if($unit > 0){
                $warnings = Warning::where('id_unit', $property)
                    ->orderBy('datecreated', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->get();

                foreach($warnings as $warnKey => $warnValue){
                    $warnings[$warnKey]['datecreated'] = date('d/m/Y', strtotime($warnValue['datecreated']));
                    $photoList = [];

                    $photos = explode(',', $warnValue['photos']);

                    foreach($photos as $photo){
                        if(!empty($photo)){
                            $photoList[] = asset('storage/' . $photo);
                        }
                    }
                    $warnings[$warnKey]['photos'] = $photoList;
                }

                $array['list'] = $warnings;
            }else{
                $array['error'] = 'Esta unidade não é sua';
            }


        }else{
            $array['error'] = 'A propriedade é necessária';
        }

        return $array;
    }
}
