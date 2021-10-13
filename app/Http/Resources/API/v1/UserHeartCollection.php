<?php

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserHeartCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        //	0 Pending,1 Confirmed, 2 declined
        return $this->collection->transform(function($data){

            return [
                'heart_request_status'=>$data->status,
                'heart_request_code'=>$data->confirmed,
                'user_id' => $data->user->id,
                'name' => $data->user->name,
                'location' => $data->user->location,
                'latitude' => $data->user->latitude,
                'longitude' => $data->user->longitude,
                'gender' => $data->user->gender,
                'profession' => $data->user->profession,
                'date_of_birth' => $data->user->date_of_birth,
                'email' => $data->user->email,
                'phone' => $data->user->phone,
                'instagram' => $data->user->instagram,
                'vaccinated' => $data->user->vaccinated,
                'profile' => asset('storage/profile/')."/". $data->user->profile,
            ];
        });
    }
    public function with($request){
        return [
            'success' => true,
            'status_code' =>200,
            'api_version'=> '1.0.0',
            'message' => 'Heart List.',
        ];
    }
}
