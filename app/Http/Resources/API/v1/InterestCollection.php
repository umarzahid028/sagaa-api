<?php

namespace App\Http\Resources\API\v1;

use Illuminate\Http\Resources\Json\ResourceCollection;

class InterestCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->transform(function($data){
            return [
                'id' => $data->id,
                'name' => $data->name,
                'image' => asset('images/interest/'.$data->image),
            ];
        });
    }
    public function with($request){
        return [
            'success' => true,
            'status_code' =>200,
            'api_version'=> '1.0.0',
            'message' => 'Interest List.',
        ];
    }
}
