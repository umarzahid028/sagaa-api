<?php

namespace App\Http\Controllers\API\v1;

use App\Classes\AgoraDynamicKey\RtmTokenBuilder;
use App\Events\MakeAgoraCall;
use App\Http\Controllers\Controller;
use App\Http\Resources\API\v1\NotificationCollection;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserVideoCall;
use App\Models\VirtualDate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Classes\AgoraDynamicKey\RtcTokenBuilder;
use App\Http\Controllers\API\v1\BaseController as BaseController;
use Illuminate\Support\Str;
use Validator;


class AgoraVideoController extends BaseController
{

    public function token(Request $request){

        /**
         * Speedy limit 5
         * Virtual limit 2
         */

        $limit = UserVideoCall::where('from_id', $request->from)
            ->where('status', 1)
            ->where('date_type', $request->date_type)
            ->count();
        if($limit <= 2000 AND $request->date_type == "virtual"){
            $video_call_limit_check = true;
        }
        else if($limit <= 5000 AND $request->date_type == "speedy"){
            $video_call_limit_check = true;
        }
        else {
            $video_call_limit_check = false;
        }

        if($video_call_limit_check){
            $appID = env('AGORA_APP_ID');
            $appCertificate = env('AGORA_APP_CERTIFICATE');
            $channelName = uniqid();
            $uid = 0;
            $role = RtcTokenBuilder::RoleAttendee;
            $expireTimeInSeconds = 3600;
            $currentTimestamp = (new \DateTime("now", new \DateTimeZone('UTC')))->getTimestamp();
            $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
            $token = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, $uid, $role, $privilegeExpiredTs);
            $data['token'] = $token;
            $data['channel_name'] =$channelName;
            $data['date_type'] = $request->date_type;
            $message = 'Your Calling token has been generated.';

            $user_video_call = new UserVideoCall;
            $user_video_call->from_id = $request->from;
            $user_video_call->to_id = $request->to;
            $user_video_call->date_type = $request->date_type;
            $user_video_call->call_token = $token;
            $user_video_call->channel_name = $channelName;
            $user_video_call->status = 0;
            $user_video_call->expiry = $privilegeExpiredTs;
            $user_video_call->save();

            $user_to = User::find($request->to);

            $user_to->is_calling = 1;
            $user_to->agora_channel = $channelName;
            $user_to->date_type = $request->date_type;
            $user_to->agora_token = $token;
            $user_to->save();

            $user_from = User::find($request->from);
            $user_from->is_calling = 1;
            $user_from->agora_channel = $channelName;
            $user_from->date_type = $request->date_type;
            $user_from->agora_token = $token;
            $user_from->save();

            $from_user_name = $user_from->name;
            $fcm_token = $user_to->fcm_token;
            $title = $from_user_name ."Calling";
            $body = $from_user_name.' is calling you';
            $notification_data = ['from_id'=>$request->from, 'name'=>$from_user_name, 'date_type'=>$request->date_type, 'token'=> $token, 'channel_name'=>$channelName];

            if($user_to->is_notification){
                $this->firebase_notification($fcm_token, $title, $body, $notification_data);
            }


           $notification_array = [
               'notification_from'=> $request->from,
               'notification_to'=> $request->to,
               'title'=> $title,
               'body'=> $body,
               'payload'=> $notification_data,
           ];
            $this->notification($notification_array);
        }
        else{
           $data['used'] = $limit;
           $data['date_type'] = $request->date_type;
           $message = "Your Call limit reached";
        }

        return $this->sendResponse($data, $message);
    }

    public function agora_join_or_leave(Request $request){

        $from = $request->from;
        $to = $request->to;
        $channel_name = $request->channel_name;
        $token = $request->token;
        $status = $request->status;

        $user_video_call = UserVideoCall::where('from_id', $to)
            ->where('to_id', $from)
            ->where('channel_name', $channel_name)
            ->where('call_token', $token)
            ->first();

        if($request->status == 0){

            User::where('agora_channel', $channel_name)
                ->where('agora_token', $token)
                ->update([
                    'is_calling'=> 0,
                    'agora_channel'=> NULL,
                    'date_type'=> NULL,
                    'agora_token'=> NULL,
                ]);
        }

        if($user_video_call){

            if($user_video_call->status == 0){
                //joined
                $user_video_call->status = $status;
                $user_video_call->save();
                $data['channel_name'] =$channel_name;
                $data['token'] = $token;
                $message = "You can join the channel";
            }
            else{
                $data['channel_name'] = $channel_name;
                $data['token'] = $token;
                $message = "You have already use that token. You can't join the channel";
            }
        }
        else{
            $data['channel_name'] = $channel_name;
            $data['token'] = $token;
            $message = "Invalid data";
        }
        return $this->sendResponse($data, $message);
    }


    public function notification($notification_array){
        $notification = new Notification;
        $notification->notification_from = $notification_array['notification_from'];
        $notification->notification_to = $notification_array['notification_to'];
        $notification->title = $notification_array['title'];
        $notification->body = $notification_array['body'];
        $notification->payload = json_encode($notification_array['payload']);
        $notification->save();
    }
    public function notification_list(){
        $user_id = Auth::user()->id;
        $notification = Notification::where('notification_to',$user_id)->get();
        return new NotificationCollection($notification);
    }
    public function virtual_date(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
            'date_time' => 'required',
            'budget' => 'required',
            'restaurant_name' => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('400','Validation Error.', $validator->errors());
        }

        // aWaiting
        // calling
        // callEnded
        $date = Carbon::parse($request->date_time)->format('Y-m-d h:i:s');
        $virtual = new VirtualDate;
        $virtual->from_id = $request->from;
        $virtual->to_id = $request->to;
        $virtual->date_time =$date;
        $virtual->budget = $request->budget;
        $virtual->restaurant_name = $request->restaurant_name;
        $virtual->status = "aWaiting";
        $virtual->save();


        $user_to = User::find($request->to);
        $user_from = User::find($request->from);
        $from_user_name = $user_from->name;
        $fcm_token = $user_to->fcm_token;
        $title = "Virtual Call Invitation";
        $body ="Call is schedule on ".$date." by ".$from_user_name;
        $notification_data = ['from_id'=>$request->from, 'name'=>$from_user_name];
        if($user_to->is_notification){
            $this->firebase_notification($fcm_token, $title, $body, $notification_data);
        }
        $notification_array = [
            'notification_from'=> $request->from,
            'notification_to'=> $request->to,
            'title'=> $title,
            'body'=> $body,
            'payload'=> $notification_data,
        ];
        $this->notification($notification_array);
        $data['settled'] = $body;
        return $this->sendResponse($data, 'Your virtual date has been settled ');
    }
}
