<?php
/**
 * Created by PhpStorm.
 * User: tariq
 * Date: ৫/১/২০
 * Time: ২:৩৫ PM
 */


class Call {

    public function sendCall(){
        $call = [
            'from' => '+17866778272',
            'to' => '+12242448419',
//            'to' => '+18505498016', //justin
//            'to' => '+12033500464',
//            'to' => '+12057198836',
//            'to' => '+8801521225627',
//            'to' => '+8801781501769',

//            'to' => '+15593217498',
            'connection_id' => '1277331384146331228',
            'audio_url' => 'https://s3.amazonaws.com/send-profit/audio_libraries/audio_library_5b536f5e85c5e5151c289248_aab041dab876d83df21fc9767e12e2053a3b71ae.mp3',
//            'timeout_secs' => 10
        ];
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($call));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $headers[] = 'Authorization: Bearer KEY016F74DB594A0ACCBE2583981E912328_rVJ7ir7jnpcBJhczNdGRFA';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
//        sleep(1);
        $result_dec = json_decode($result);
        print_r($result_dec);

//        $this->sendCall2();

    }

    public function sendCall2(){
        $call = [
            'from' => '+17866778272',
//            'from' => '+12028457147',
//            'to' => '+12242448419',
//            'to' => '+18505498016',
//            'to' => '+12033500464',
//            'to' => '+12057198836',
            'to' => '+18505498016', //justin

//            'to' => '+15593217498',
            'connection_id' => '1277331384146331228',
            'audio_url' => 'https://s3.amazonaws.com/send-profit/audio_libraries/audio_library_5cfeb5da78487b07f83829d5_dfadcbdbd9c71a2618c6b088575c4fd94afaaf67.wav',
            'answering_machine_detection' => 'detect',
        ];
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($call));

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $headers[] = 'Authorization: Bearer KEY016F74DB594A0ACCBE2583981E912328_rVJ7ir7jnpcBJhczNdGRFA';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $result_dec = json_decode($result);
        print_r($result_dec);


    }


}

$obj = new Call();
$obj->sendCall();





