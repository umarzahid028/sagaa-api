<?php

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class UserRequestedHeartCollection extends ResourceCollection
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
                'user_id' => $data->requested_heart->id,
                'name' => $data->requested_heart->name,
                'location' => $data->requested_heart->location,
                'latitude' => $data->requested_heart->latitude,
                'longitude' => $data->requested_heart->longitude,
                'gender' => $data->requested_heart->gender,
                'profession' => $data->requested_heart->profession,
                'date_of_birth' => $data->requested_heart->date_of_birth,
                'email' => $data->requested_heart->email,
                'phone' => $data->requested_heart->phone,
                'instagram' => $data->requested_heart->instagram,
                'vaccinated' => $data->requested_heart->vaccinated,
                'profile' => asset('storage/profile/')."/". $data->requested_heart->profile,
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
