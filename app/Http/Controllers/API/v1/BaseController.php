<?php
namespace App\Http\Controllers\API\v1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;


class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */

    public function sendResponse($result, $message)
    {
        $response = [
            'success' => true,
            'status_code'=> 200,
            'api_version'=> '1.0.0',
            'data'    => $result,
            'message' => $message,
        ];
        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($status_code,$error, $errorMessages = [], $code = 200)
    {
        $response = [
            'success' => false,
            'status_code' =>$code,
            'api_version'=> '1.0.0',
            'message' => $error,
        ];


        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }


        return response()->json($response, $code);
    }

    public function sendResponseOtp($message)
    {
        $response = [
            'success' => true,
            'status_code' => 200,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
    }

    public function SuccessResponse($message)
    {
        $response = [
            'success' => true,
            'status_code'=> 200,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
    }

    public function errorResponse($message)
    {
        $response = [
            'success' => true,
            'status_code'=> 400,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
    }
    public function DataExistsError($message)
    {
        $response = [
            'success' => false,
            'status_code' => 401,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
    }
    public function DataNotFound($message)
    {
        $response = [
            'success' => false,
            'status_code' => 200,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
    }

    public function error($message)
    {
        $response = [
            'success' => false,
            'status_code' => 200,
            'api_version'=> '1.0.0',
            'message' => $message,
        ];
        return response()->json($response, 200);
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
