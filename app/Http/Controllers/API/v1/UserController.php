<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Resources\API\v1\CuisineCollection;
use App\Http\Resources\API\v1\InterestCollection;
use App\Http\Resources\API\v1\UserHeartCollection;
use App\Models\Cuisine;
use App\Models\Interest;
use App\Models\User;
use App\Models\UserCuisine;
use App\Models\UserHeart;
use App\Models\UserInterest;
use App\Models\VirtualDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;
use DB;
use App\Http\Controllers\API\v1\BaseController as BaseController;


class UserController extends BaseController
{
    public function user_profile_update(Request $request){

        $validator = Validator::make($request->all(), [
            'email' => 'email|unique:users,email',
        ]);


        if($validator->fails()){
            return $this->sendError('400','Validation Error.', $validator->errors());
        }


        $update = User::find($request->id);

        if($update){
            if($request->name){
                $update->name = $request->name;
            }
            if($request->location){
                $update->location = $request->location;
            }
            if($request->latitude){
                $update->latitude = $request->latitude;
            }
            if($request->longitude){
                $update->longitude =  bcrypt($request->longitude);
            }
            if($request->latitude){
                $update->latitude =  $request->latitude;
            }
            if($request->longitude){
                $update->longitude =  $request->longitude;
            }
            if($request->gender){
                $update->gender =  $request->gender;
            }
            if($request->profession){
                $update->profession =  $request->profession;
            }
            if($request->date_of_birth){
                $update->date_of_birth =  $request->date_of_birth;
                $age = Carbon::parse($request->date_of_birth)->format('Y-m-d');
                $update->age = Carbon::parse($age)->age;
            }
            if($request->email){
                $update->email =  $request->email;
            }
            if($request->phone){
                $update->phone =  $request->phone;
            }
            if($request->instagram){
                $update->instagram =  $request->instagram;
            }
            if($request->vaccinated){
                $update->vaccinated =  $request->vaccinated;
            }
            if($request->cuisines){
                $cuisines = explode(',',$request->cuisines);

                UserCuisine::where('user_id',$request->id)->delete();

                foreach ($cuisines AS $cuisine){
                    $cuisine_exists = UserCuisine::where('cuisine_id', $cuisine)->where('user_id',$request->id)->first();
                    $user_cuisine =  new UserCuisine;
                    $user_cuisine->user_id = $request->id;
                    $user_cuisine->cuisine_id = $cuisine;
                    $user_cuisine->save();
                }
            }
            if($request->interests){

                UserInterest::where('user_id',$request->id)->delete();

                $interests = explode(',',$request->interests);
                foreach ($interests AS $interest){
                    $user_interest =  new UserInterest;
                    $user_interest->user_id = $request->id;
                    $user_interest->interest_id = $interest;
                    $user_interest->save();
                }
            }
            if($request->profile){
                $image = $request->profile;  // your base64 encoded

                $image = str_replace('data:image/png;base64,', '', $image);
                $image = str_replace(' ', '+', $image);
                $imageName ='profile'.'_'.time().'.'.'png';

                 \File::put(storage_path(). '/app/public/profile/' . $imageName, base64_decode($image));

                $update->profile =  $imageName;
            }
            $update->save();

            $success['data'] = $this->get_user($update->id);

            return $this->sendResponse($success,'profile has been updated.');
        }
        else{
            return $this->errorResponse('User not found. Request id is '.$request->id);
        }
    }
    public function cuisine(){

        $cuisine = Cuisine::get();
        return new CuisineCollection($cuisine);
    }
    public function interest(){

        $Interest = Interest::get();
        return new InterestCollection($Interest);
    }
    public function cuisine_interest(){

        $cuisine = Cuisine::get();
        $Interest = Interest::get();

        $success['cuisine_image_base_path'] = asset('images/cuisine/');
        $success['Interest_image_base_path'] = asset('images/interest/');

        $success['cuisine'] = $cuisine;
        $success['Interest'] = $Interest;


        return $this->sendResponse($success,'Cuisine and interest list.');

    }
    public function user_list(Request $request){

        $validator = Validator::make($request->all(), [
            'max_distance' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('400','Validation Error.', $validator->errors());
        }
//        $circle_radius = 3959;
//        $max_distance = 20;
//        $lat = "31.454147";
//        $lng = "73.084642";

        $circle_radius = 3959;
        $max_distance = $request->max_distance;
        $lat = $request->latitude;
        $lng = $request->longitude;
        $interests_array = UserInterest::where('user_id', $request->id)->pluck('interest_id')->toArray();
        $cuisines_array = UserCuisine::where('user_id', $request->id)->pluck('cuisine_id')->toArray();
        $user_id = $request->id;

        $candidates = User::with('cuisines','interests')
            ->selectRaw("id, name, location, latitude, longitude, gender, profession, date_of_birth, age, email, phone, instagram, vaccinated, profile,
               ( 3956 * acos( cos( radians(?) )
                * cos( radians( latitude ) )
                * cos( radians( longitude ) - radians(?)) + sin( radians(?) )
                * sin( radians( latitude ) ) )) AS distance ", [$lat, $lng, $lat])
             ->where('id', '!=', $user_id)
            ->whereHas('interests', function ($q) use($cuisines_array){
                $q->OrwhereIn('interest_id', $cuisines_array);
            })
            ->whereHas('cuisines', function ($q) use($cuisines_array){
                $q->OrwhereIn('cuisine_id', $cuisines_array);
            })
            ->having("distance", "<", $max_distance)
            ->orderBy("distance",'asc')
            ->paginate(10);


        if (count($candidates) > 0 ){
             $candidates->map(function($item_update) use($user_id) {
                $item_update->profile_base_url = asset('storage/profile/');
                $item_update->cuisine_base_url = asset('images/cuisine/');
                $item_update->interest_base_url =  asset('images/interest/');
                $to =  (int)$item_update->id;
                $from =  (int)$user_id;
                $user_heart_from  = UserHeart::where('requesting_person_from',$from)->where('requesting_person_to', $to)->first();
                $user_heart_to  = UserHeart::where('requesting_person_from',$to)->where('requesting_person_to', $from)->first();
                if($user_heart_from OR $user_heart_to){
                    $is_hearted = 1;
                }
                else{
                    $is_hearted = 0;
                }
                $item_update->is_hearted = $is_hearted;
                return $item_update;
            });
            return $candidates;
        }
        else{
            return $this->DataNotFound('Data not found');
        }
    }
    public function get_user($id){

        $user = User::with('cuisines','interests')->find($id);

        $user_data = [
            'id'=> $user->id,
            'name'=> $user->name,
            'location'=> $user->location,
            'latitude'=> $user->latitude,
            'longitude'=> $user->longitude,
            'gender'=> $user->gender,
            'profession'=> $user->profession,
            'date_of_birth'=> $user->date_of_birth,
            'age'=> $user->age,
            'email'=> $user->email,
            'phone'=> $user->phone,
            'instagram'=> $user->instagram,
            'vaccinated'=> $user->vaccinated,
            'profile_base_url'=> asset('storage/profile/'),
            'profile'=> $user->profile,
            'cuisines' =>[
                'cuisine_base_url'=> asset('images/cuisine/'),
                'data'=> $user->cuisines,
            ],
            'interests' =>[
                'interest_base_url'=>asset('images/interest/'),
                'data'=> $user->interests,
            ]
        ];
        return $user_data;
    }

    public function user_heart_request(Request $request){

        $status = $request->status;
        $key = true;
        $requesting_person_from = $request->requesting_person_from;
        $requesting_person_to = $request->requesting_person_to;
        $user_from = User::find($request->requesting_person_from);
        $user_to = User::find($request->requesting_person_to);


        if(is_null($user_from)){
            return $this->DataNotFound('Requesting Person from id is '. $requesting_person_from. ' not found');
        }
        if(is_null($user_to)){
            return $this->DataNotFound('Requesting Person to id is '. $requesting_person_to. ' not found');
        }

        $data_exists = UserHeart::where('requesting_person_from', $request->requesting_person_from)
            ->where('requesting_person_to', $requesting_person_to)
            ->first();

        if(is_null($data_exists) && $request->status == 1){
            $add = new UserHeart ;
            $add->requesting_person_from = $request->requesting_person_from;
            $add->requesting_person_to = $requesting_person_to;
            $add->save();
            $message = "Heart request has been sent";
            $firebase_message = $user_from->name." requesting heart to you.";
        }
        else if(!is_null($data_exists) && $request->status == 0){
            $heart = UserHeart::where('requesting_person_from', $request->requesting_person_from)
                ->where('requesting_person_to', $requesting_person_to)
                ->first();
            $heart->delete();

            $message = "Your heart request has been removed";
            $firebase_message = $user_from->name." requested heart has been removed.";
        }
        else{
            $message = "You have already sent the heart request";
            $key = false;
            $firebase_message = "";
        }
        $token = $user_to->fcm_token;
        $title = 'Heart Notification';
        $body = $firebase_message;
        $notification_data = [];
        if($user_to->is_notification && $key == true){
            $this->firebase_notification($token, $title, $body, $notification_data);

        }
        return $this->SuccessResponse($message);
    }
    public function heart_requesting_list(Request $request){
        $user = User::find($request->user_id);
        if(is_null($user)){
            return $this->DataNotFound('Requested user not found');
        }
        $heart_requesting_list = UserHeart::with('user')->where('requesting_person_from', $request->user_id)->get();
        $heart_requested_list = UserHeart::with('requested_heart')->where('requesting_person_to', $request->user_id)->get();

        $success['heart_requesting_list'] = $heart_requesting_list;
        $success['heart_requested_list'] = $heart_requested_list;
        return $this->sendResponse($success,'Heart lists.');
        return new UserHeartCollection($heart_requesting_list,$heart_requested_list);
    }
    public function heart_accept_or_rejects(Request $request){

        $from_id = User::find($request->from_id);
        $to_id= User::find($request->to_id);
        if(is_null($from_id)){
            return $this->DataNotFound('Requested from user id not found');
        }
        if(is_null($to_id)){
            return $this->DataNotFound('Requested to user id not found');
        }
        if($request->status == 0){
            $status = "Pending";
        }
        else if($request->status == 1){
            $status = "Confirmed";
        }
        else if($request->status == 2){
            $status = "Declined";
        }
        $update = UserHeart::where('requesting_person_from', $request->from_id)->where('requesting_person_to', $request->to_id)->first();
        $update->status = $status;
        $update->confirmed = $request->status;
        $update->save();

        return $this->SuccessResponse('Heart status has been updated');
    }
    public function fcm_token(Request $request){
        $user = User::find($request->id);
        $user->fcm_token = $request->fcm_token;
        $user->save();
        $data['fcm_token'] = $request->fcm_token;
        $data['status'] = true;
        return $this->sendResponse($data, 'Your FCM Token has been updated');
    }

    public function is_notification(Request $request){

        $user = User::find($request->user_id);
        if($request->is_notification == 1){
            $user->is_notification = 1;
            $message = 'Your notification has been on';
        }
        else if($request->is_notification == 0){
            $user->is_notification = 0;
            $message = 'Your notification has been offed';
        }
        $user->save();
        $data['user_id'] = $request->user_id;

        $title = 'Notification';
        $body = $message;
        $notification_data = [];

        $this->firebase_notification($user->fcm_token, $title, $body, $notification_data);

        return $this->sendResponse($data, $message);
    }
}
