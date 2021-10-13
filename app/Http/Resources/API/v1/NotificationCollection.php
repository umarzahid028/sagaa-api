<?php

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NotificationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
    public function with($request){
        return [
            'success' => true,
            'status_code' =>200,
            'api_version'=> '1.0.0',
            'message' => 'Notification lists.',
        ];
    }
}
