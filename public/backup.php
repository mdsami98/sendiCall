<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /*===================LOGIC BLOCK 1 For testing start==============*/

    $raw_payload = file_get_contents('php://input',true);

    $payload = json_encode($raw_payload);

    $payload = json_decode($raw_payload, true);
    $fh = fopen("log.txt", "a+");

    if ($fh) {
        fwrite($fh, json_encode($payload));
        fclose($fh);
    }

    /*===================LOGIC BLOCK For testing end==============*/

    if($payload['data']['event_type'] == 'call.answered'){

        $dtmfArray= [
            'digits' => '1'
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls/'.$payload['data']['payload']['call_control_id'].'/actions/send_dtmf');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dtmfArray));

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

        $result_dec= json_encode($result);

        $fh = fopen("dtmf.txt", "a+");

        if ($fh) {
            fwrite($fh, $result_dec);
            fclose($fh);
        }

    }

    //dtmf event received



    if($payload['data']['event_type'] == 'call.dtmf.received') {


        if ($payload['data']['payload']['digit'] == '1') {

            $fh = fopen("dtmf1.txt", "a+");

            if ($fh) {
                fwrite($fh, "pressed 1 ");
                fclose($fh);

//                // transfer call function

                $call = [
                    'from' => '+17866778272',
                    'to' => '+8801781501769',

                ];

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls/' . $payload['data']['payload']['call_control_id'] . '/actions/transfer');
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

                $fh = fopen("transfer.txt", "a+");

                if ($fh) {
                    fwrite($fh, $result);
                    fclose($fh);
                }

            }
        }
        else {

            if ($payload['data']['payload']['digit'] == '') {
                $fh = fopen("nothing.txt", "a+");

                if ($fh) {
                    fwrite($fh, "nothing pressed");
                    fclose($fh);
                }
            }
            else {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls/' . $payload['data']['payload']['call_control_id'] . '/actions/hangup');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);

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
            }

        }



    }


    //call.playback.ended
    if ($payload['data']['event_type'] == 'call.playback.ended'){

        // Gather
        $gatherArray = [
            'payload'=> '',
            'language'=> 'en-US',
            'timeout_millis'=> '5000',
            'voice'=> 'male',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls/' . $payload['data']['payload']['call_control_id'] . '/actions/gather_using_speak');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gatherArray));

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



//        Hang Up
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, 'https://api.telnyx.com/v2/calls/' . $payload['data']['payload']['call_control_id'] . '/actions/hangup');
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POST, 1);
//
//        $headers = array();
//        $headers[] = 'Content-Type: application/json';
//        $headers[] = 'Accept: application/json';
//        $headers[] = 'Authorization: Bearer KEY016F74DB594A0ACCBE2583981E912328_rVJ7ir7jnpcBJhczNdGRFA';
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//
//        $result = curl_exec($ch);
//        if (curl_errno($ch)) {
//            echo 'Error:' . curl_error($ch);
//        }
//        curl_close($ch);
    }

}

else {
    $fh = fopen("log2.txt", "w+");

    if ($fh) {
        fwrite($fh, "failed");
        fclose($fh);
    } else {
        trigger_error("Unable to open file!");
    }
}



?>
