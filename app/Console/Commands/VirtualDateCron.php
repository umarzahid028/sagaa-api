<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\VirtualDate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class VirtualDateCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtual:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $virtual_date = VirtualDate::where('status', 'aWaiting')->get();
        $notification_data = [];
        foreach ($virtual_date AS $row){
            $user_from = User::with('virtual_date_from')->find($row->from_id);
            $user_to = User::with('virtual_date_to')->find($row->to_id);
            $remaining_time = Carbon::now('Asia/Karachi')
                ->diffInMinutes($row->date_time, false);

            if ($remaining_time <= 20 && $row->is_notification == 0){
                if($user_from->is_notification ){
                    $token = $user_from->fcm_token;
                    $title = 'Be ready for virtual date';
                    $body = "We are notifying you that ".$remaining_time." is remaining for your virtual date.";
                    $this->firebase_notification($token, $title, $body, $notification_data);
                }
                if($user_to->is_notification){
                    $token = $user_to->fcm_token;
                    $title = 'Be ready for virtual date';
                    $body = $user_from->name ." notifying you that ".$remaining_time." is remaining for your virtual date.";
                    $this->firebase_notification($token, $title, $body, $notification_data);
                }

                $virtual_date_update = VirtualDate::where('from_id', $row->from_id)->first();
                $virtual_date_update->is_notification = 1;
                $virtual_date_update->save();
            }

            \Log::alert([$row->date_time,$remaining_time,($remaining_time <= 20 && $row->is_notification == 0)]);
        }

    }

    public function firebase_notification($token, $title, $body, $notification_data = []){

        $url = 'https://fcm.googleapis.com/fcm/send';
        $params['device_token'] = $token;
        $params['data'] = json_encode($notification_data);

        $fields = array(
            'registration_ids' => array(
                $params['device_token'],
            ),
            'notification' => array(
                "title" => $title,
                "body" => $body,
                "content_available"=>true,
                'notification_data' => [
                    "data" => $params['data'],
                ],
            ),
            "content_available" => true,
            "priority" => "high",
        );

        $fields = json_encode($fields);
        $headers = array(
            'Authorization: key= '.env('FIREBASE_NOTIFICATION_KEY'),
            'Content-Type:application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        // print_r($result);
        curl_close($ch);
    }
}
