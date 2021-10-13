<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Mail\OTP;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;
use Twilio\Rest\Client;
use App\Http\Controllers\API\v1\BaseController as BaseController;

class LoginSignupContoller extends BaseController
{
    public function otp_send(Request $request){

        $validator = Validator::make($request->all(), [
            'number_or_email' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('400','Validation Error.', $validator->errors());
        }

        $otp = rand(100000, 999999);

        $otp_message = "Your One Time Password(OTP) is $otp \n";
        $otp_message.="Note: Please do not reply to this sms as this is the system generated sms. For further queries, contact us at support@sagaa.com \n";
        $otp_message .="Yours sincerely,\n";
        $otp_message .="Team Sagaa App \n";

        if (filter_var($request->number_or_email, FILTER_VALIDATE_EMAIL)) {

            Mail::to($request->number_or_email)->send(new OTP($otp));
            $type = "email";
        }
        else {
            $this->send_sms($request->number_or_email, $otp_message);
            $type = "phone";
        }

        $user = User::where($type, $request->number_or_email)->first();


        if(is_null($user)){

            $create_user = new User;
            if($type == 'email') {
                $create_user->email = $request->number_or_email;
            }
            else if($type == 'phone') {
                $create_user->phone = $request->number_or_email;
            }
            $create_user->otp = $otp;
            $create_user->otp_type = $type == 'phone' ? 1:2;
            $create_user->otp_validity = Carbon::now()->addMinutes(3);
            $create_user->save();
        }
        else{
            $user->otp = $otp;
            $user->otp_is_used = 0;
            $user->otp_validity = Carbon::now()->addMinutes(3);
            $user->save();
        }
        $message = "Your One Time Password(OTP) has been sent on your email.";
        return $this->sendResponseOtp($message);
    }

    public function send_sms($to,$message){

        $basic  = new \Vonage\Client\Credentials\Basic("2b3fcf48", "kMyNQPCORtg5MveL");
        $client = new \Vonage\Client($basic);

        $response = $client->sms()->send(
            new \Vonage\SMS\Message\SMS("$to", "Sagaa", "$message")
        );
    }

    public function otp_validation(Request $request){
       // dd($this->send_otp(1212122));
        $otp_validity = Carbon::now();
        $otp_exists = User::where('otp', $request->otp)->first();

        if($otp_exists){

            $is_otp_validity = User::where('otp_validity', '>=', $otp_validity)->where('otp_is_used', 0)->first();

            if($is_otp_validity){

                $is_otp_validity->otp_is_used = 1;
                $is_otp_validity->fcm_token = $request->fcm_token;
                $is_otp_validity->save();
                $token = $request->fcm_token;
                $title = "Logged in";
                $body = "You are logged in successfully";
                $notification_data = [];
                $this->firebase_notification($token, $title, $body, $notification_data);
                $success['token'] =  $is_otp_validity-> createToken('SagaaApp')->accessToken;

                $success['data'] = $this->get_user($is_otp_validity->id);

                return $this->sendResponse($success, 'You are login successfully.');
            }
            else{
                return $this->sendError('Expired.', ['error'=>'Your OTP has been expired. Please resend again.']);
            }
        }
        else{
            return $this->sendError('Expired.', ['error'=>'Your OTP is not valid. Please try again.']);
        }
    }
    public static function send_otp($OTP){

          $phone = '+923326768004';
          try {

              $sid = "dfgdfg";
              $token = "9158c539d3f190823fbe92d1eb45c7dfg0d";

                $client = new Client($sid, $token);
                $message = $client->messages->create(
                '+923326768004', // Text this number
                [
                    'from' => '9991231234', // From a valid Twilio number
                    'body' => 'Hello from Twilio!'
                ]
            );
              dd( $client->setLogLevel('debug'));

//
//             $client = new Client($sid, $token);
//
//             $client->messages->create(
//                 $phone,
//                 array(
//                     'from' => '+923326768004',
//                     'body' => $OTP
//                 )
//             );
         }
         catch(\Exception $e)
         {
             \Log::error('Twilio SMS:: ' . $e->getMessage());
         }
        //return $client;
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
}
