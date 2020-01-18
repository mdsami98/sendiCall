<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once('ProcessBaseController.php');
class Public_controller extends ProcessBaseController
{
    function __construct()
    {
        parent::__construct();

        $this->session_user_id = $this->session->twilio_ai_user_id;
        $this->session_user_logged_in = $this->session->twilio_ai_user_logged_in;
        $this->_config = $this->Mdl_config->_config();
        $this->server_ip = $this->_config->server_ip;
        $this->bindInfo = new stdClass;
        $this->bindInfo->id = false;
        $this->sender_id = [
            13 => false,
            14 => false,
            15 => false,
            18 => false,
        ];
        $this->forceReplyGateway = false;
        $this->forceSystemNumber = false;
        ob_flush();
    }

    public function index()
    {
        die();
        $result = $this->repo->campaigns()->find();
        $now_s = strtotime(date('Y-m-d H:i:s'));
        $now = $this->mongo->offset_timestamp();

        echo $this->mongo->date_format_1($now_s, true, false);
        echo '<br>';
        echo $this->mongo->date_format_1($now_s);

    }

    public function g($msg, $func)
    {
        echo $msg;

        $msg_2 = call_user_func($func, $msg);
        echo $msg_2;
    }

    public function test_callback($test)
    {
        echo $test;
    }

    public function test_callback_2()
    {
        $this->test_callback($a = 4, function ($msg) {
            return $msg;
        });
    }

    public function number_verify()
    {
        die();
        echo $this->db->count_all_results('NUMBER_VERIFIER_BLOCK');
        echo '<br>';
        $insert_array = [];
        $result = $this->db->query("SELECT * FROM NUMBER_VERIFIER_BLOCK order by ID asc limit 1200000,600000")->result_array();
        foreach ($result as $key => $value) {
            $insert_array[] = [
                'npa' => intval($value['NPA']),
                'nxx' => intval($value['NXX']),
                'block' => intval($value['BLOCK']),
                'blocktype' => $value['BLOCKTYPE'],
                'ocn' => $value['OCN'],
                'company' => $value['COMPANY']
            ];
        }
        $output = $this->mongo->insert('number_verifier_block', $insert_array, true);
        var_dump($output[0]);
    }

    public function sync_numbers()
    {
        $insert_array = [];
        $result = $this->db->query("SELECT * FROM TBL_SYNC_FROM_NUMBERS order by ID asc")->result_array();
        foreach ($result as $key => $value) {
            $insert_array[] = [
                'user_id' => $this->mongo->_id($this->session_user_id),
                'number' => $value['NUMBER'],
                'auth_type' => intval($value['AUTH_TYPE']),
                'add_date' => $this->mongo->date()
            ];
        }
        $output = $this->mongo->insert('sync_from_numbers', $insert_array, true);
        var_dump($output[0]);
    }

    public function check_oid($value = '', $timpstamp = true)
    {
        if (is_object($value)) {
            $class = get_class($value);
            if ($class == "MongoDB\BSON\ObjectID")
                return (string)$value;
            else if ($class == "MongoDB\BSON\UTCDateTime" && $timpstamp == true)
                return $this->mongo->date_format_1((string)$value);
            else if ($class == "MongoDB\BSON\UTCDateTime" && $timpstamp == false)
                return $this->mongo->date_format_1((string)$value, true, false);
        } else {
            return $value;
        }
    }

    public function print_table($array, $multiDimension = true)
    {
        if (count($array) > 0) {
            if (!$multiDimension) {
                $array[0] = $array;
            }
            $html = '
          <table border="1" cellspacing="0" cellpadding="8">
          ';
            $html .= '<tr>';
            foreach ($array[0] as $key => $value) {
                $html .= '<th>' . $key . '</th>';
            }
            $html .= '</tr>';
            foreach ($array as $key => $value) {
                $html .= '<tr>';
                foreach ($value as $key_2 => $value_2) {
                    $html .= '<td>' . $this->check_oid($value_2) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '
          </table>
        ';
            return $html;
        } else
            return 'No Data in Table';
    }

    public function domain_import()
    {
        $output = $this->repo->domainAuths()->find();
        echo $this->print_table($output);
    }

    public function single_field_sum($value_1, $value)
    {
        die(); //unused;
        $result = $this->mongo->report_group('to_number', intval($value_1), intval($value));
        print_r($result);
    }

    public function test_str_arr()
    {
        $pat = '{{DG}}-Domain group 1-{{DG}} -bvjksa vjkas v- {{DG}}-Domain group 2-{{DG}} ncvsjk jksda askd{{DG}}-Domain group 3-{{DG}} csdjcjksd jks {{DG}}-Domain group 4-{{DG}} ';
        preg_match_all('/{{DG}}-(.*)-{{DG}}/s', $pat, $title);
        print_r($title);
    }

    public function group_update()
    {
        die();
        $result_1 = $this->mongo->fetch('groups');
        foreach ($result_1 as $key => $value) {
            $id = $value->_id;
            if (!isset($result_1->total_data_in_group)) {
                $count = $this->repo->contacts()->countByGroupId($id);
                if ($count == 0) {
                    $this->mongo->delete(
                        'groups',
                        [
                            '_id' => $this->mongo->_id($id)
                        ]
                    );
                    echo 'delete<br>';
                } else {
                    $this->mongo->update(
                        'groups',
                        [
                            '_id' => $this->mongo->_id($id)
                        ],
                        [
                            'total_data_in_group' => intval($count)
                        ]
                    );
                    echo 'update count ' . $count . '<br>';
                }
            }
        }

    }

    public function plivo_reply_check()
    {

    }

    public function twilio_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        require_once(getcwd() . '/application/controllers/Services/Twilio.php');
        $auth = $this->Mdl_global->auth_id(1, $this->mongo->_id($user_id), true);
        if ($auth) {
            $twilioClass = new Services_Twilio(trim($auth->auth_key), trim($auth->auth_token));
            $coversation_field['reply_text'] = $reply;
            if(strlen($system_number) != 5){
                $system_number = '+' . $system_number;
            }
            if (trim($reply) != '') {
                $message = $twilioClass->account->messages->sendMessage(
                    $system_number,
                    '+' . $user_number,
                    $reply
                );
            }
            $insert_array = [
                'date' => $this->mongo->date(),
                'expireDate' => $this->mongo->date('', false, "+6 months"),
                'gateway_type' => $coversation_field['gateway_type'],
                'user_id' => $coversation_field['user_id'],
                'system_number_id' => $coversation_field['system_number_id'],
                'system_number' => $coversation_field['system_number'],
                'user_number' => $coversation_field['user_number'],
                'replyAble' => $coversation_field['replyAble'],
                'positive' => $coversation_field['positive'],
                'keyword_id' => $coversation_field['keyword_id'],
                'text' => $coversation_field['text'],
                'reply_text' => $coversation_field['reply_text'],

            ];
            $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

            $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
            $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
            $this->Mdl_global->insert_row('reports', [
                    "user_id" => $coversation_field['user_id'],
                    "campaign" => null,
                    "from_number" => $coversation_field['system_number'],
                    "to_number" => $coversation_field['user_number'],
                    "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                    "status" => "REPLIED",
                    "date" => $this->mongo->date(),
                    "message" => $coversation_field['reply_text'],
                    "gateway_type" => $coversation_field['gateway_type'],
                    'ar' => true,
                    'positive' => $coversation_field['positive'],
                    'keyword_id' => $coversation_field['keyword_id'],
                    'keyword' => $coversation_field['keyword'],
                ]
            );
        }
    }

    public function plivo_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        require_once '/var/www/html/api/plivo/plivo.php';

        $auth = $this->Mdl_global->auth_id(2, $this->mongo->_id($user_id), true);
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $p = new RestAPI($auth->auth_key, $auth->auth_token);
                $params = array(
                    'src' => $system_number,
                    'dst' => $user_number,
                    'text' => $reply,
                    'url' => 'http://' . $this->server_ip . '/Public_controller/plivo_reply_check',
                    'method' => 'POST'
                );

                $response = $p->send_message($params);

                if (isset($response['response']['message_uuid'][0])) {
                    $uuid = $response['response']['message_uuid'][0];
                    $coversation_field['uuid'] = $uuid;
                } else {
                    $coversation_field['uuid'] = null;
                }
            }
            $insert_array = [
                'date' => $this->mongo->date(),
                'expireDate' => $this->mongo->date('', false, "+6 months"),
                'gateway_type' => $coversation_field['gateway_type'],
                'user_id' => $coversation_field['user_id'],
                'system_number_id' => $coversation_field['system_number_id'],
                'system_number' => $coversation_field['system_number'],
                'user_number' => $coversation_field['user_number'],
                'replyAble' => $coversation_field['replyAble'],
                'positive' => $coversation_field['positive'],
                'keyword_id' => $coversation_field['keyword_id'],
                'text' => $coversation_field['text'],
                'reply_text' => $coversation_field['reply_text'],

            ];
            $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

            $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
            $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
            $this->Mdl_global->insert_row('reports', [
                    "user_id" => $coversation_field['user_id'],
                    "campaign" => null,
                    "from_number" => $coversation_field['system_number'],
                    "to_number" => $coversation_field['user_number'],
                    "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                    "status" => "REPLIED",
                    "date" => $this->mongo->date(),
                    "message" => $coversation_field['reply_text'],
                    "gateway_type" => $coversation_field['gateway_type'],
                    'ar' => true,
                    'positive' => $coversation_field['positive'],
                    'keyword_id' => $coversation_field['keyword_id'],
                    'keyword' => $coversation_field['keyword'],
                ]
            );
        }
    }

    public function bandwidth_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(3, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'from' => '+' . $system_number,
                    'to' => '+' . $user_number,
                    'text' => $reply,
                    'receiptRequested' => 'all',
                    'callbackUrl' => "http://" . $this->server_ip . "/Public_controller/bandwidth_reply_callback",
                ];
                $path = 'messages/';
                $result = $this->Mdl_bandwidth->bandwidthApiBasic([
                    'auth' => $auth,
                    'path' => $path,
                    'method' => 'post',
                    'fields' => $params,
                ]);
                if ($result['status'] && !empty($result['response']['header'])) {
                    $send = $this->Mdl_bandwidth->getDataFromBandwidthHeader([
                        'header' => $result['response']['header'],
                        'func' => 'sendMessage',
                    ]);
                    if ($send['status'] && !empty($send['id'])) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => $send['id'],
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }
                }

            }

        }
    }

    public function clx_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(6, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => [$user_number],
                        'body' => $reply,
                        'delivery_report' => 'full',
                        'callback_url' => "http://" . $this->server_ip . "/Public_controller/clx_delivery_callback",
                    ]
                ];
                $send = $this->Mdl_clx->clxSend($params);

                if ($send['status'] && !empty($send['id'])) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function clx2_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(20, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => [$user_number],
                        'body' => $reply,
                        'delivery_report' => 'full',
                        'callback_url' => "http://" . $this->server_ip . "/Public_controller/clx_delivery_callback",
                    ]
                ];
                $send = $this->Mdl_clx->clxSend($params);

                if ($send['status'] && !empty($send['id'])) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function signalWireReply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {

        $auth = $this->Mdl_global->auth_id(22, $this->mongo->_id($user_id), true);
        // $auth = $this->mongo->fetch_1('api_auth',['user_id'=>$this->mongo->_id($user_id),'type'=> 7]);
        $coversation_field['response'] = [];
        $user_number = '+' . $user_number;
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'From' => '+' . $system_number,
                        'To' => $user_number,
                        'Body' => $reply,
                    ]
                ];
                $send = $this->Mdl_signalwire->signalWireSend($params);

                // $this->Mdl_global->insert_row('request_store',['clxSend' => $send,'params'=>$params]);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $coversation_field['message_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function deltaReply($reply, $user_id, $user_number, $system_number, $coversation_field, $campaign_id)
    {

        $auth = $this->Mdl_global->auth_id(25, $this->mongo->_id($user_id), true);
        // $auth = $this->mongo->fetch_1('api_auth',['user_id'=>$this->mongo->_id($user_id),'type'=> 7]);
        $coversation_field['response'] = [];
        $user_number = '+' . $user_number;
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'From' => '+' . $system_number,
                        'To' => $user_number,
                        'Body' => $reply,
                        'StatusCallback' => $this->Mdl_config->_config()->smsDlr->delta,
                    ]
                ];
                $send = $this->Mdl_signalwire->signalWireSend($params, $user_id, $campaign_id);

                // $this->Mdl_global->insert_row('request_store',['clxSend' => $send,'params'=>$params]);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $coversation_field['message_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function alpha1Reply($reply, $user_id, $user_number, $system_number, $coversation_field, $campaign_id)
    {

        $auth = $this->Mdl_global->auth_id(29, $this->mongo->_id($user_id), true);
        // $auth = $this->mongo->fetch_1('api_auth',['user_id'=>$this->mongo->_id($user_id),'type'=> 7]);
        $coversation_field['response'] = [];
        $user_number = '+' . $user_number;
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'src' => '+' . $system_number,
                        'dst' => $user_number,
                        'text' => $reply,
                        'url' => $this->Mdl_config->_config()->smsDlr->alpha_1,
                    ]
                ];
                $send = $this->Mdl_alpha_1->alpha1Send($params, $user_id, $campaign_id);

                // $this->Mdl_global->insert_row('request_store',['clxSend' => $send,'params'=>$params]);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $coversation_field['message_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function inteliquent_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(7, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => [$user_number],
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_inteliquent->inteliquentSend($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $coversation_field['message_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function sendiiRouteAlphaReply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(19, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => [$user_number],
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_inteliquent2->inteliquent2Send($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $coversation_field['message_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }


    public function telnyx_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(8, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => '+' . $system_number,
                        'to' => '+' . $user_number,
                        'body' => $reply,
                    ]
                ];
                $send = $this->Mdl_telnyx->telnyxSend($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function alpha_2_reply($reply, $user_id, $user_number, $system_number, $coversation_field, $campaign_id)
    {
        $auth = $this->Mdl_global->auth_id(28, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => '+' . $system_number,
                        'to' => '+' . $user_number,
                        'text' => $reply,
                        "webhook_url" => $this->Mdl_config->_config()->smsDlr->alpha_2, //can be used as notify url, not assigned then default message profile url will be used default
                        "webhook_failover_url" => $this->Mdl_config->_config()->smsDlr->alpha_2, //notify url
                    ]
                ];
                $send = $this->Mdl_alpha_2->alpha2Send($params, $user_id, $campaign_id);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function infobip_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(10, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => $user_number,
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_infoBip->infobipSend($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function tyntec_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(11, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => '+' . $system_number,
                        'to' => '+' . $user_number,
                        'message' => $reply,
                    ]
                ];
                $send = $this->Mdl_tyntec->tyntecSend($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function oneS2u_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(12, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => 'X773848',
                        'to' => $user_number,
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_oneS2u->oneS2uSend($params);

                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }
    }

    public function primerworks_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 9);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'from' => $system_number,
                            'to' => $user_number,
                            'text' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_primerWorks->primerworksSend($params);

                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => '',
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function smswarriors_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id && $this->sender_id[13]) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 13);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'senderid' => $this->sender_id[13],
                            'contacts' => $user_number,
                            'msg' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_smsWarriors->smswarriorsSend($params);

                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => '',
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function imagineGlobalSend($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id && $this->sender_id[14]) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 14);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'senderid' => $this->sender_id[14],
                            'contacts' => $user_number,
                            'msg' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_imagineGlobal->imagineGlobalSend($params);

                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => '',
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function mmdSend($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id && $this->sender_id[15]) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 15);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'senderid' => $this->sender_id[15],
                            'contacts' => $user_number,
                            'msg' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_mmd->mmdSend($params);

                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => '',
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function semySmsSend($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id && $this->sender_id[18]) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 18);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'senderid' => $this->sender_id[18],
                            'contacts' => $user_number,
                            'msg' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_semySms->semySmsSend($params);

                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => $send['sms_id'],
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function mobinitySend($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        if ($this->bindInfo->id) {
            $auth = $this->repo->apiAuth()->findOneByIdUserIdAndType($this->bindInfo->id, $user_id, 17);
            $coversation_field['response'] = [];
            if ($auth) {
                $coversation_field['reply_text'] = $reply;
                if (trim($reply) != '') {
                    $params = [
                        'auth' => $auth,
                        'data' => [
                            'to' => $user_number,
                            'text' => $reply,
                        ]
                    ];
                    $send = $this->Mdl_mobinity->mobinitySend($params);
                    if ($send['status']) {
                        $insert_array = [
                            'date' => $this->mongo->date(),
                            'expireDate' => $this->mongo->date('', false, "+6 months"),
                            'gateway_type' => $coversation_field['gateway_type'],
                            'user_id' => $coversation_field['user_id'],
                            'system_number_id' => $coversation_field['system_number_id'],
                            'system_number' => $coversation_field['system_number'],
                            'user_number' => $coversation_field['user_number'],
                            'replyAble' => $coversation_field['replyAble'],
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'text' => $coversation_field['text'],
                            'reply_text' => $coversation_field['reply_text'],

                        ];
                        $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                        $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                        $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                        $this->Mdl_global->insert_row('reports', [
                                "user_id" => $coversation_field['user_id'],
                                "campaign" => null,
                                "from_number" => $coversation_field['system_number'],
                                "to_number" => $coversation_field['user_number'],
                                "gateway" => $this->bindInfo->id,
                                "status" => "REPLIED",
                                "date" => $this->mongo->date(),
                                "message" => $coversation_field['reply_text'],
                                "gateway_type" => $coversation_field['gateway_type'],
                                'ar' => true,
                                'positive' => $coversation_field['positive'],
                                'keyword_id' => $coversation_field['keyword_id'],
                                'keyword' => $coversation_field['keyword'],
                                'message_id' => $send['sms_id'],
                                'delivery_code' => '-1',
                                'response' => $coversation_field['response'],
                            ]
                        );
                    }


                }

            }
        }
    }

    public function message360Send($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(16, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => $user_number,
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_message360->message360Send($params);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }

    }
    public function charlieSend($reply, $user_id, $user_number, $system_number, $coversation_field, $campaign_id)
    {
        $auth = $this->Mdl_global->auth_id(24, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => $user_number,
                        'text' => $reply,
                        'MessageStatusCallback' => $this->Mdl_config->_config()->smsDlr->charlie,
                        'DeliveryStatus' => true
                    ]
                ];
                $send = $this->Mdl_charlie->message360Send($params, $user_id, $campaign_id);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }

    }

    public function ghostSend($reply, $user_id, $user_number, $system_number, $coversation_field, $campaign_id)
    {
        $auth = $this->Mdl_global->auth_id(31, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => $user_number,
                        'text' => $reply,
                        'MessageStatusCallback' => $this->Mdl_config->_config()->smsDlr->ghost,
                        'DeliveryStatus' => true
                    ]
                ];
                $send = $this->Mdl_ghost->ghostSend($params, $user_id, $campaign_id);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }

    }

    public function firstPointSend($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(21, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        if ($auth) {
            $coversation_field['reply_text'] = $reply;
            if (trim($reply) != '') {
                $params = [
                    'auth' => $auth,
                    'data' => [
                        'from' => $system_number,
                        'to' => $user_number,
                        'text' => $reply,
                    ]
                ];
                $send = $this->Mdl_firstPoint->firstPointSend($params);
                if ($send['status']) {
                    $insert_array = [
                        'date' => $this->mongo->date(),
                        'expireDate' => $this->mongo->date('', false, "+6 months"),
                        'gateway_type' => $coversation_field['gateway_type'],
                        'user_id' => $coversation_field['user_id'],
                        'system_number_id' => $coversation_field['system_number_id'],
                        'system_number' => $coversation_field['system_number'],
                        'user_number' => $coversation_field['user_number'],
                        'replyAble' => $coversation_field['replyAble'],
                        'positive' => $coversation_field['positive'],
                        'keyword_id' => $coversation_field['keyword_id'],
                        'text' => $coversation_field['text'],
                        'reply_text' => $coversation_field['reply_text'],

                    ];
                    $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

                    $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
                    $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
                    $this->Mdl_global->insert_row('reports', [
                            "user_id" => $coversation_field['user_id'],
                            "campaign" => null,
                            "from_number" => $coversation_field['system_number'],
                            "to_number" => $coversation_field['user_number'],
                            "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                            "status" => "REPLIED",
                            "date" => $this->mongo->date(),
                            "message" => $coversation_field['reply_text'],
                            "gateway_type" => $coversation_field['gateway_type'],
                            'ar' => true,
                            'positive' => $coversation_field['positive'],
                            'keyword_id' => $coversation_field['keyword_id'],
                            'keyword' => $coversation_field['keyword'],
                            'message_id' => $send['sms_id'],
                            'delivery_code' => '-1',
                            'response' => $coversation_field['response'],
                        ]
                    );
                }


            }

        }

    }


    public function aerialink_reply($reply, $user_id, $user_number, $system_number, $coversation_field)
    {
        $auth = $this->Mdl_global->auth_id(4, $this->mongo->_id($user_id), true);
        $coversation_field['response'] = [];
        $coversation_field['reply_text'] = $reply;
        $response_array = false;
        if (trim($reply) != '') {
            $params = [
                'source' => $system_number,
                'destination' => $user_number,
                'messageText' => $reply,
            ];
            $response_array = $this->Mdl_aerialink->aerialink_request([
                'path' => 'messages',
                'data' => $params,
                'method' => 'POST',
                'user_id' => $coversation_field['user_id'],
            ]);


            if ($response_array)
                if (isset($response_array['aerialink']['errorCode']))
                    $response_array = false;

            $coversation_field['response'] = $response_array;
        }
        if ($response_array) {

            $insert_array = [
                'date' => $this->mongo->date(),
                'expireDate' => $this->mongo->date('', false, "+6 months"),
                'gateway_type' => $coversation_field['gateway_type'],
                'user_id' => $coversation_field['user_id'],
                'system_number_id' => $coversation_field['system_number_id'],
                'system_number' => $coversation_field['system_number'],
                'user_number' => $coversation_field['user_number'],
                'replyAble' => $coversation_field['replyAble'],
                'positive' => $coversation_field['positive'],
                'keyword_id' => $coversation_field['keyword_id'],
                'text' => $coversation_field['text'],
                'reply_text' => $coversation_field['reply_text'],

            ];
            $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

            $coversation_field['gateway_type'] = $this->forceReplyGateway ? $this->forceReplyGateway : $coversation_field['gateway_type'];
            $coversation_field['system_number'] = $this->forceSystemNumber ? $this->forceSystemNumber : $coversation_field['system_number'];
            $this->Mdl_global->insert_row('reports', [
                    "user_id" => $coversation_field['user_id'],
                    "campaign" => null,
                    "from_number" => $coversation_field['system_number'],
                    "to_number" => $coversation_field['user_number'],
                    "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                    "status" => "REPLIED",
                    "date" => $this->mongo->date(),
                    "message" => $coversation_field['reply_text'],
                    "gateway_type" => $coversation_field['gateway_type'],
                    'ar' => true,
                    'positive' => $coversation_field['positive'],
                    'keyword_id' => $coversation_field['keyword_id'],
                    'keyword' => $coversation_field['keyword'],
                    'response' => $coversation_field['response'],
                ]
            );
        }
    }

    public function spinner($user_id = null)
    {
        $request = new HttpRequest();
        $request->setUrl('http://54.85.120.91:8181/TwilioAI/api/engine/spin');
        $request->setMethod(HTTP_METH_GET);

        $SystemAuthToken = $this->Mdl_global->getSystemAuthToken($user_id);

        $request->setQueryData(array(
            'text' => '{He|she} is {glad|happy}'
        ));

        $request->setHeaders(array(
            'postman-token' => '95e004cf-c206-5363-2f01-1f1c2ef2e473',
            'cache-control' => 'no-cache',
            'authToken' => $SystemAuthToken,
        ));

        $request->setContentType('application/x-www-form-urlencoded');
        $request->setPostFields(array());

        try {
            $response = $request->send();

            echo $response->getBody();
        } catch (HttpException $ex) {
            echo $ex;
        }
    }

    public function getBetweenContents()
    {
        $str = 'bjk fba k[SDG]Domain1[EDG] jsd jks[SDG]domain2[EDG]';
        $data = $this->Mdl_global->getBetweenContents($str, '[SDG]', '[EDG]');
        print_r($data);
    }

    function bandwidth_reply_callback()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        if (isset($post['messageId']) && isset($post['deliveryCode']))
            $this->repo->reports()->updateDeliveryCodeByMessageId($post['messageId'], $post['deliveryCode']);
    }

    function clx_delivery_callback()
    {
        $post = json_decode(file_get_contents('php://input'), true);
        /*
          {
            "type":"delivery_report_sms",
            "batch_id":"2ij85c51qJqhaojJ",
            "total_message_count":1,
            "statuses":[{"code":0,"status":"Delivered","count":1,"recipients":["18505498016"]}]
          }
        */
    }

    public function process_reply($obj, $coversation_field)
    {
        $output = [];
        $user_number = $coversation_field['user_number'];
        $reply_from_number_group_id = null;
        $reply_from_number_group_data = false;
        $reply_from_number_data = false;
        $reply_from_number_group_count = 0;
        $reply_from_number_group_skip = 0;
        $reply_from_number = $coversation_field['system_number'];
        $reply_from_number_org = $coversation_field['system_number_org'];
        $reply_auth = 0;
        $switchGateway = false;
        $output['system_number_id'] = null;
        $output['campaign_id'] = null;
        $lastUsedSystemNumber = $this->repo->reports()->getByFromNumberByUserSingle($obj->user_id, $reply_from_number_org);

        if ($lastUsedSystemNumber && isset($lastUsedSystemNumber->campaign)) {
            $campaignId = $lastUsedSystemNumber->campaign;
            $output['campaign_id'] = $campaignId;
            $campaignData = $this->repo->campaigns()->findByIdAndUserId($campaignId, $obj->user_id);

            if ($campaignData && isset($campaignData->reply_from_number_group) && $campaignData->reply_from_number_group != null) {

                $reply_from_number_group_id = $campaignData->reply_from_number_group;
                $reply_from_number_group_data = $this->repo->twilioFromNumberGroup()->findById($reply_from_number_group_id);
                if ($reply_from_number_group_data) {
                    $reply_auth = $reply_from_number_group_data->auth_type;
                    $reply_from_number_group_count = $this->repo->twilioFromNumbersGroupLists()->countByGroupId($reply_from_number_group_id);
                    if ($reply_from_number_group_count > 0) {
                        $switchGateway = true;
                        if ($reply_auth == 9 || $reply_auth == 13 || $reply_auth == 14 || $reply_auth == 15 || $reply_auth == 17 || $reply_auth == 18) {
                            if (isset($campaignData->reply_bind_auth_id) && $campaignData->reply_bind_auth_id) {
                                $this->bindInfo->id = $this->mongo->_id($campaignData->reply_bind_auth_id);
                            }
                            if (isset($campaignData->reply_gateway_auths) && isset($campaignData->reply_gateway_auths[0])) {
                                $this->bindInfo->id = $this->mongo->_id($campaignData->reply_gateway_auths[0]);
                            }
                            if ($reply_auth == 13 || $reply_auth == 14 || $reply_auth == 15) {
                                if (isset($campaignData->reply_sender_id) && $campaignData->reply_sender_id != '') {
                                    $this->sender_id[$reply_auth] = $campaignData->reply_sender_id;
                                }
                            }
                            if ($reply_auth == 18) {
                                $auth_info = $this->repo->apiAuth()->findById($this->bindInfo->id);
                                if ($auth_info) {
                                    $this->sender_id[$reply_auth] = $auth_info->device_id;
                                }
                            }
                        }
                        $reply_from_number_group_skip = isset($reply_from_number_group_data->reply_skip) ? $reply_from_number_group_data->reply_skip : 0;
                        if ($reply_from_number_group_skip >= $reply_from_number_group_count)
                            $reply_from_number_group_skip = 0;
                    }

                }
            }
        }
        $reply_setting = $obj->reply_setting;
        $reply_setting_array = json_decode(json_encode($reply_setting), true);
        $reply_message_auth = $reply_auth;

        if (!isset($reply_setting_array[$reply_auth])) {
            $reply_setting_array[$reply_auth] = $reply_setting_array[0];
            $reply_message_auth = 0;
        }

        if ($switchGateway) {
            $reply_from_number_data = $this->repo->twilioFromNumbersGroupLists()->findOneByGroupId(
                $reply_from_number_group_id,
                [
                    'sort' => ['_id' => 1],
                    'skip' => $reply_from_number_group_skip,
                    'limit' => 1,
                ]
            );

            if ($reply_from_number_data) {
                $reply_from_number_group_skip++;
                $output['system_number'] = $reply_from_number_data->from_number;
                $output['system_number_id'] = $reply_from_number_data->_id;
                $reply_auth = $reply_from_number_data->auth_type;
            } else {
                $reply_from_number_group_skip = 0;
                $switchGateway = false;
                $reply_auth = 0;
            }
            $this->repo->twilioFromNumberGroup()->updateById(
                $reply_from_number_group_id,
                ['reply_skip' => $reply_from_number_group_skip]
            );
        }

        $output['reply_auth'] = $reply_auth;
        $output['message'] = '';
        $reply_setting = $reply_setting_array[$reply_auth];
        $total_seq = $reply_setting['total_seq'];
        $current_seq = $reply_setting['skip'] + 1;
        $message_obj = $this->mongo->fetch_1(
            'keyword_spinner',
            [
                'keyword_id'=> $this->mongo->_id($obj->_id),
                'seq' => $current_seq,
                'auth_type' => $reply_message_auth,
                'user_id'=> $this->mongo->_id($obj->user_id)
            ]
        );
        //$message_obj = $this->repo->keywordSpinner($this->mongo->user_id(), $obj->_id, $current_seq, $reply_message_auth);

        if ($message_obj) {
            if ($total_seq == $current_seq)
                $current_seq = 0;
            $this->repo->keywordReplySetting()->updateById(
                $obj->_id,
                ['reply_setting.' . $reply_message_auth . '.skip' => $current_seq]
            );
            $message = $message_obj->reply_sms;
            $user_number_obj = $this->repo->contacts()->findOneByUserIdAndContact($obj->user_id, $user_number);

            $search = ['[[FIRST_NAME]]', '[[LAST_NAME]]', '[[ZIP]]', '[[CITY]]', '[[STATE]]', '[[EMAIL]]', '[[NUMBER_OPERATOR]]', '[[URL]]'];

            if ($user_number_obj)
                $replace = [
                    $user_number_obj->name,
                    $user_number_obj->last_name,
                    $user_number_obj->zip,
                    $user_number_obj->city,
                    $user_number_obj->state,
                    $user_number_obj->email,
                    $user_number_obj->number_operator,
                    $user_number_obj->url
                ];
            else
                $replace = ['', '', '', '', '', '', '', ''];
            $message = str_replace($search, $replace, $message);
            $domain_search = [];
            $domain_replace = [];
            $domain_group_update = [];
            if (count($reply_setting['domain_group']) > 0) {
                foreach ($reply_setting['domain_group'] as $key => $value) {
                    $domain_search_temp = '[SDG]' . $value['group_id'] . '[EDG]';
                    $total_domain_temp = $this->repo->domains()->countByUserIdAndGroupId($obj->user_id, $value['group_id']);
                    $current_domain = $value['skip'];
                    $domain_obj = $this->repo->domains()->findOneByGroupIdAndUserId($value['group_id'], $obj->user_id, $current_domain);
                    if ($domain_obj) {
                        $domain_replace_temp = $domain_obj->domain;

                    } else {
                        $domain_replace_temp = '';
                    }
                    $domain_group_update[] = [
                        'group_id' => $this->mongo->_id($value['group_id']),
                        'skip' => (($total_domain_temp == 0 || $total_domain_temp == $current_domain + 1) ? 0 : $current_domain + 1)
                    ];
                    $domain_search[] = $domain_search_temp;
                    $domain_replace[] = $domain_replace_temp;
                }
                $message = str_replace($domain_search, $domain_replace, $message);
                $this->repo->keywordReplySetting()->updateById(
                    $obj->_id,
                    ['reply_setting.' . $reply_message_auth . 'domain_group' => $domain_group_update]
                );
            }
            $output['message'] = $message;
        }

        return $output;
    }

    public function keyword_reply($gateWay, $argument1 = '', $argument2 = '')
    {
//        echo  $gateWay;
        $unknown_gateway = 'unknown';
        if (trim(strtolower($gateWay)) == $unknown_gateway) {
            $json_2 = json_decode(file_get_contents('php://input'), true);
            $post_2 = $_REQUEST;
            $this->Mdl_global->insert_row('request_store', [
                'gateway' => $unknown_gateway,
                'json' => $json_2,
                'post' => $post_2,
            ]);
            die();
        }
        if (trim(strtolower($gateWay)) == 'bandwidth' ||
            trim(strtolower($gateWay)) == 'inteliquent' ||
            trim(strtolower($gateWay)) == 'telnyx' ||
            trim(strtolower($gateWay)) == 'alpha_2' ||
            trim(strtolower($gateWay)) == 'infobip' ||
            trim(strtolower($gateWay)) == 'tyntec' ||
            trim(strtolower($gateWay)) == 'mobinity' ||
            trim(strtolower($gateWay)) == 'sendii_route_alpha' ||
            trim(strtolower($gateWay)) == 'sendii_route_bravo' ||
//            trim(strtolower($gateWay)) == 'charlie' ||
            trim(strtolower($gateWay)) == 'clx'
        ) {
            $post = json_decode(file_get_contents('php://input'), true);
        } else {
            $post = $_REQUEST;
        }

        // $this->Mdl_global->insert_row('request_store',$post);
        $gateWay = trim(strtolower($gateWay));
        $argument1 = trim($argument1);
        $argument2 = trim($argument2);
        $message_id = '';
        $delivery_code = '-1';
        $bind_name = '';
        if ($gateWay == '') die();
        if ($gateWay != 'plivo' &&
            $gateWay != 'alpha_1' &&
            $gateWay != 'twilio' &&
            $gateWay != 'bandwidth' &&
            $gateWay != 'aerialink' &&
            $gateWay != 'inteliquent' &&
            $gateWay != 'telnyx' &&
            $gateWay != 'alpha_2' &&
            $gateWay != 'primerworks' &&
            $gateWay != 'infobip' &&
            $gateWay != 'tyntec' &&
            $gateWay != 'message360' &&
            $gateWay != 'charlie' &&
            $gateWay != 'mobinity' &&
            $gateWay != 'semysms' &&
            $gateWay != 'sendii_route_alpha' &&
            $gateWay != 'sendii_route_bravo' &&
            $gateWay != 'first_point' &&
            $gateWay != 'signalwire' &&
            $gateWay != 'delta' &&
            $gateWay != 'ghost' &&
            $gateWay != 'clx'
        ) die();
        if ($gateWay == 'bandwidth') {
            if (!isset($post['direction']) || $post['direction'] != 'in')
                die();
            if (isset($post['messageId']))
                $message_id = $post['messageId'];
            else
                die();

        }
        if ($gateWay == 'sendii_route_bravo') {
            if (!isset($post['type']) || $post['type'] != 'mo_text')
                die();
            if (isset($post['id']))
                $message_id = $post['id'];
            else
                die();

        }
        if ($gateWay == 'clx') {
            if (!isset($post['type']) || $post['type'] != 'mo_text')
                die();
            if (isset($post['id']))
                $message_id = $post['id'];
            else
                die();

        }
        if ($gateWay == 'aerialink') {
            if (isset($post['source']))
                $post['From'] = $post['source'];

            if (isset($post['destination']))
                $post['To'] = $post['destination'];
        }
        if ($gateWay == 'inteliquent') {
            if (isset($post['resultResponses'])) die();
            // $this->Mdl_global->insert_row('request_store',['inteliquent'=>$post]);
            if (is_array($post['to']) && !empty($post['to'])) {
                $post['to'] = $post['to'][0];
            }
        }
        if ($gateWay == 'sendii_route_alpha') {
            if (isset($post['resultResponses'])) die();
            $this->Mdl_global->insert_row('request_store', ['inteliquent' => $post]);
            if (is_array($post['to']) && !empty($post['to'])) {
                $post['to'] = $post['to'][0];
            }
        }
        if ($gateWay == 'telnyx') {
            $this->Mdl_global->insert_row('request_store', ['telnyx' => $post]);
        }
        if ($gateWay == 'alpha_2') {
            $this->Mdl_global->insert_row('request_store', ['alpha_2' => $post]);
            $post['To'] = $post['data']['payload']['to'];
            $post['From'] = $post['data']['payload']['from']['phone_number'];
        }
        if ($gateWay == 'primerworks') {
            // $this->Mdl_global->insert_row('request_store',['primerworks'=>$post]);
        }
        if ($gateWay == 'infobip') {
            // $this->Mdl_global->insert_row('request_store',['infobip'=>$post]);

            if (!isset($post['results'][0]['receivedAt'])) die();
            $temp = [];
            $temp['from'] = $post['results'][0]['from'];
            $temp['to'] = $post['results'][0]['to'];
            $temp['text'] = $post['results'][0]['text'];
            $post = $temp;
        }
        if ($gateWay == 'mobinity') {
            // $this->Mdl_global->insert_row('request_store',['infobip'=>$post]);

            if (!isset($post['phone_number'])) die();
            $temp = [];
            $temp['from'] = $post['phone_number'];
            $temp['to'] = '12345678912';
            $temp['text'] = $post['message'];
            $post = $temp;
        }
        if ($gateWay == 'semysms') {
            // $this->Mdl_global->insert_row('request_store',['infobip'=>$post]);

            if (!isset($post['phone'])) die();
            $temp = [];
            $temp['from'] = $post['phone'];
            $temp['to'] = '12345678912';
            $temp['text'] = $post['msg'];
            $post = $temp;
        }
        if ($gateWay == 'first_point') {
            // $this->Mdl_global->insert_row('request_store',['infobip'=>$post]);

            if (!isset($post['msisdn'])) die();
            $temp = [];
            $temp['from'] = $post['msisdn'];
            $temp['to'] = $post['to'];
            $temp['text'] = $post['message'];
            $post = $temp;
        }
        if ($gateWay == 'signalwire') {
            $this->Mdl_global->insert_row('request_store', ['signalwire' => $post]);
            // print_r($post);
            $post['To'] = str_replace('+', '', $post['To']);
            if (!isset($post['From'])) die();


        }
        if ($gateWay == 'delta') {
            $this->Mdl_global->insert_row('request_store', ['delta' => $post]);
            // print_r($post);
            $post['To'] = str_replace('+', '', $post['To']);
            if (!isset($post['From'])) die();
        }
        if ($gateWay == 'ghost') {
            if (!isset($post['msisdn'])) die();
            $temp = [];
            $temp['from'] = $post['msisdn'];
            $temp['to'] = $post['to'];
            $temp['text'] = $post['text'];
            $post = $temp;

        }
        if (!isset($post['From']) && !isset($post['from'])) die();
        if (!isset($post['To']) && !isset($post['to'])) die();

        $from_number = isset($post['From']) ? trim($post["From"]) : trim($post["from"]);
        $to_number = isset($post['To']) ? trim($post["To"]) : trim($post["to"]);

        $coversation_field = [];
        $coversation_field['message_id'] = $message_id;
        $coversation_field['delivery_code'] = $delivery_code;

        $user_number = $this->Mdl_global->number_len($this->Mdl_global->clean_number($from_number));
        if($gateWay == 'twilio' && strlen($to_number) == 5){
            $system_number =  $to_number;
        }else{
            $system_number = $this->Mdl_global->number_len($this->Mdl_global->clean_number($to_number));

        }
//        echo '$user_number:'.$user_number;
//         echo '$system_number:'.$system_number;


        if ($gateWay == 'twilio') {
            $post['gateWay'] = 'twilio';
            $this->Mdl_global->insert_row('request_store',$post);
            if (!isset($post['Body'])) die();
            $text = $post["Body"];
            $coversation_field['gateway_type'] = 1;
        } else if ($gateWay == 'plivo') {
            $this->Mdl_global->insert_row('request_store',['plivo'=>$post]);

            if (!isset($post['Text'])) die();
            $text = $post["Text"];
            $coversation_field['gateway_type'] = 2;
        }else if ($gateWay == 'alpha_1') {
            $this->Mdl_global->insert_row('request_store',['alpha_1'=>$post]);

            if (!isset($post['Text'])) die();
            $text = $post["Text"];
            $coversation_field['gateway_type'] = 29;
        } else if ($gateWay == 'bandwidth') {
            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 3;
        } else if ($gateWay == 'aerialink') {
            if (!isset($post['messageText'])) die();
            $text = $post["messageText"];
            $coversation_field['gateway_type'] = 4;
            // $this->Mdl_global->insert_row('request_store',['place_1'=>$post]);
        } else if ($gateWay == 'sendii_route_bravo') {
            if (!isset($post['body'])) die();
            $text = $post["body"];
            $coversation_field['gateway_type'] = 20;
        } else if ($gateWay == 'clx') {
            if (!isset($post['body'])) die();
            $text = $post["body"];
            $coversation_field['gateway_type'] = 6;
        } else if ($gateWay == 'inteliquent') {
            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 7;
        } else if ($gateWay == 'telnyx') {
            if (!isset($post['body'])) die();
            $text = $post["body"];
            $coversation_field['gateway_type'] = 8;
        }else if ($gateWay == 'alpha_2') {
            if (!isset($post['data']['payload']['text'])) die();
            $text = $post['data']['payload']['text'];
            $coversation_field['gateway_type'] = 28;
        } else if ($gateWay == 'primerworks') {
            if (!isset($post['bind'])) die();
            $text = $post["text"];
            $bind_name = trim($post['bind']);
            if ($bind_name == '') die();
            $coversation_field['gateway_type'] = 9;
        } else if ($gateWay == 'infobip') {
            $text = $post["text"];
            $coversation_field['gateway_type'] = 10;
        } else if ($gateWay == 'tyntec') {
            if (!isset($post['message'])) die();
            $text = $post["message"];
            $coversation_field['gateway_type'] = 11;
        } else if ($gateWay == 'message360') {
            //$this->Mdl_global->insert_row('request_store',['message360_test'=>$post]);
            if (!isset($post['Text'])) die();
            $text = $post["Text"];
            $coversation_field['gateway_type'] = 16;
        } else if ($gateWay == 'charlie') {
            // $this->Mdl_global->insert_row('request_store',['charlie_test'=>$post]);
            if (!isset($post['Text'])) die();
            $text = $post["Text"];
            $coversation_field['gateway_type'] = 24;
        } else if ($gateWay == 'mobinity') {
            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 17;
        } else if ($gateWay == 'semysms') {
            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 18;

        } else if ($gateWay == 'sendii_route_alpha') {
            $this->Mdl_global->insert_row('request_store',['sendii_route_alpha_test'=>$post]);
            if (!isset($post['text']) || !isset($post['messageType']) || (isset($post['messageType']) && $post['messageType'] !='SMS')) die();
            $text = $post["text"];

            $coversation_field['gateway_type'] = 19;
        } else if ($gateWay == 'first_point') {
            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 21;

        } else if ($gateWay == 'signalwire') {

            if (!isset($post['Body'])) die();
            $text = $post["Body"];
            $coversation_field['gateway_type'] = 22;
        }
        else if ($gateWay == 'delta') {

            if (!isset($post['Body'])) die();
            $text = $post["Body"];
            $coversation_field['gateway_type'] = 25;
        }
        else if ($gateWay == 'ghost') {
            // $this->Mdl_global->insert_row('request_store',['ghost_test'=>$post]);

            if (!isset($post['text'])) die();
            $text = $post["text"];
            $coversation_field['gateway_type'] = 31;
        }


        $text = trim($text);
        $coversation_field['keyword_id'] = null;
        $coversation_field['text'] = $text;
        $coversation_field['reply_text'] = '';
        $coversation_field['replyAble'] = false;
        $coversation_field['positive'] = true;
        $coversation_field['date'] = $this->mongo->date();
        $coversation_field['user_number'] = $user_number;

        $system_number_obj = $this->mongo->fetch_1('twilio_from_numbers_group_list', ['from_number' => $system_number, 'auth_type' => $coversation_field['gateway_type']]);
//        echo $coversation_field['gateway_type'];
//        echo 'system_number:'.$system_number;
//        echo 'system_number_obj:';
//        print_r($system_number_obj);
        if (!$system_number_obj) {
            //echo'number not found';
            $coversation_field['system_number'] = $system_number;
            $coversation_field['user_id'] = null;
            $insert_array = [
                'date' => $this->mongo->date(),
                'expireDate' => $this->mongo->date('', false, "+6 months"),
                'gateway_type' => $coversation_field['gateway_type'],
                'user_id' => $coversation_field['user_id'],
                'system_number' => $coversation_field['system_number'],
                'user_number' => $coversation_field['user_number'],
                'replyAble' => $coversation_field['replyAble'],
                'text' => $coversation_field['text'],
                'reply_text' => $coversation_field['reply_text'],

            ];
            $this->Mdl_global->insert_row('keyword_conversation', $insert_array);
            // echo 'system number not found';

            die();

        }


        $coversation_field['system_number_id'] = $this->mongo->_id($system_number_obj->_id);
        $coversation_field['system_number'] = $system_number_obj->from_number;
        $coversation_field['system_number_org'] = $coversation_field['system_number'];

        if ($coversation_field['gateway_type'] == 9 && $bind_name != '') {
            $bind_info = $this->mongo->fetch_1('api_auth', ['bind_name' => $bind_name, 'approved' => true]);
            if ($bind_info) {
                $this->bindInfo->id = $bind_info->_id;
                if ($system_number_obj->from_number == "1234") {
                    $system_number_obj->user_id = $this->mongo->_id($bind_info->user_id);
                    $system_number_obj->auth_id = $this->mongo->_id($bind_info->_id);
                }
            } else {
                die();
            }
        }
        if ($coversation_field['gateway_type'] == 17 && $argument1 != '') {
            $auth_info = $this->mongo->fetch_1('api_auth', ['_id' => $this->mongo->_id($argument1)]);
            if ($auth_info) {
                $this->bindInfo->id = $auth_info->_id;
                if ($system_number_obj->from_number == $system_number) {
                    $system_number_obj->user_id = $this->mongo->_id($auth_info->user_id);
                    $system_number_obj->auth_id = $this->mongo->_id($auth_info->_id);
                }
            } else {
                die();
            }
        }
        if ($coversation_field['gateway_type'] == 18 && $argument1 != '') {
            $auth_info = $this->mongo->fetch_1('api_auth', ['_id' => $this->mongo->_id($argument1)]);
            if ($auth_info) {
                $this->bindInfo->id = $auth_info->_id;
                $this->sender_id[$coversation_field['gateway_type']] = $auth_info->device_id;
                $coversation_field['system_number_org'] = $auth_info->device_id;
                $this->forceSystemNumber = $coversation_field['system_number_org'];
                $this->forceReplyGateway = 18;
                if ($system_number_obj->from_number == $system_number) {
                    $system_number_obj->user_id = $this->mongo->_id($auth_info->user_id);
                    $system_number_obj->auth_id = $this->mongo->_id($auth_info->_id);
                }
            } else {
                die();
            }
        }

        $system_number_user_id = $this->mongo->_id($system_number_obj->user_id);
        $userObj = $this->mongo->fetch_1('user', ['_id' => $system_number_user_id]);
        if (!$userObj) die();
        $comunity_id = $userObj->comunity_id;


        /****Trigger START ****/
        if(isset($userObj->trigger_access) && $userObj->trigger_access == true)
        {
            $this->Mdl_trigger->incomingMessageAndSaveTriggerInQueue($user_number, $coversation_field['system_number'], $coversation_field['text'], $coversation_field['gateway_type'], $system_number_user_id);

        }
        /***Trigger END**/



        // echo 'system number found'.$coversation_field['system_number_org'].'---'.$coversation_field['user_number'].'--'.$coversation_field['gateway_type'];
        $this->Mdl_campaign->keepLog($coversation_field['system_number_org'],$coversation_field['user_number'], $coversation_field['text'], $coversation_field['gateway_type'],'SMS');

        $date_from = date('Y-m-d H:i:s', strtotime('- 24 hours'));
        $date_to = date('Y-m-d H:i:s');
        $permitted_limit = 10;

        $user_seed_number_exist = $this->Mdl_global->check_exist('seed_test', ['user_id' => $this->mongo->_id($system_number_obj->user_id), 'seed_number' => $user_number]);

        if (!$user_seed_number_exist) {
            $limit_check = $this->mongo->count(
                'keyword_conversation',
                [
                    'user_number' => $user_number,
                    'user_id' => $this->mongo->_id($system_number_obj->user_id),
                    'date' => [
                        '$gte' => $this->mongo->date($date_from),
                        '$lte' => $this->mongo->date($date_to)
                    ]
                ]
            );
            //if ($limit_check >= $permitted_limit) die();
        }

        $coversation_field['user_id'] = $this->mongo->_id($system_number_obj->user_id);


        $check_gateway = $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']);
        if (!$check_gateway) die();

        $text_array = explode(' ', $text);
        $replyAble = false;
        $replyArray = [];
        $coversation_field['keyword_id'] = null;
        $coversation_field['keyword'] = null;
        foreach ($text_array as $key => $value) {
            $value = $this->Mdl_global->clean_number($value);
            if ($value != '') {

                $result = $this->mongo->fetch_1(
                    'negative_keyword',
                    [
                        'keyword' => strtoupper($value),
                        'comunity_id' => $comunity_id,
                    ]
                );
                if ($result) {
                    $replyAble = false;
                    $coversation_field['replyAble'] = false;
                    $coversation_field['positive'] = false;
                    $coversation_field['keyword'] = strtoupper($result->keyword);
                    $coversation_field['keyword_id'] = null;
                    $replyArray = $result;
                    $this->mongo->insert('optout_list', ['number' => $coversation_field['user_number'], "user_id" => $coversation_field['user_id'], "add_date" => $this->mongo->date()]);
                    $threadId = $this->repo->inboxThreads()->findOneUserThreadIdByParams(
                        $coversation_field['user_id'],
                        $coversation_field['system_number_org'],
                        $coversation_field['user_number'],
                        $coversation_field['gateway_type']);
                    if($threadId){
                        $this->Mdl_inbox->archiveThreadsByIdsWithBlacklist([$threadId->_id]);
//                        print_r($threadId);
                    }
                    break;
                }
            }
        }
        if ($coversation_field['positive']) {
            foreach ($text_array as $key => $value) {
                $result = $this->mongo->fetch_1(
                    'keyword_reply_setting',
                    [
                        'keyword_r' => strtoupper($value),
                        'user_id' => $this->mongo->_id($system_number_obj->user_id)
                    ]
                );
                if ($result) {
                    $replyAble = true;
                    $coversation_field['replyAble'] = true;
                    $coversation_field['keyword'] = strtoupper($result->keyword_r);
                    $coversation_field['keyword_id'] = $this->mongo->_id($result->_id);
                    $replyArray = $result;
                    break;
                }
            }
        }
        // print_r($coversation_field);
        $this->Mdl_global->insert_row('reports', [
                "user_id" => $coversation_field['user_id'],
                "campaign" => null,
                "from_number" => $coversation_field['system_number_org'],
                "to_number" => $coversation_field['user_number'],
                "gateway" => $this->Mdl_global->auth_id($coversation_field['gateway_type'], $coversation_field['user_id']),
                "status" => "RECEIVED",
                "date" => $this->mongo->date(),
                "message" => $coversation_field['text'],
                "gateway_type" => $coversation_field['gateway_type'],
                'ar' => true,
                'positive' => $coversation_field['positive'],
                'keyword_id' => $coversation_field['keyword_id'],
                'keyword' => $coversation_field['keyword'],
                'message_id' => $coversation_field['message_id'],
                'delivery_code' => $coversation_field['delivery_code'],
            ]
        );
        $this->Mdl_inbox->insertInboxMessageAndThread(
            $coversation_field['user_id'],
            $coversation_field['system_number_org'],
            $coversation_field['user_number'],
            $coversation_field['gateway_type'],
            $coversation_field['text'],
            'IN');
        //Post in webhook

        $post['reply_body'] = $text;
        $this->Mdl_webhook->postInWebhook( $coversation_field['user_id'], $coversation_field['system_number_org'],$coversation_field['user_number'],$post);
        if ($replyAble) {

            $processed_reply = $this->process_reply($replyArray, $coversation_field);
            // print_r($processed_reply);

            // $this->Mdl_global->print_array($processed_reply);
            // $this->Mdl_global->insert_row('request_store',['process'=>$processed_reply,'replyable'=>true]);
            $reply_auth = $processed_reply['reply_auth'];

            if ($reply_auth != 0) {
                $gateWay = $this->Mdl_global->auth_type_code_name($reply_auth);
                $coversation_field['gateway_type'] = $reply_auth;
                $system_number = $processed_reply['system_number'];
                $coversation_field['system_number'] = $processed_reply['system_number'];
                $coversation_field['system_number_id'] = $processed_reply['system_number_id'];
            }
            //inbox insert
            $this->Mdl_inbox->insertInboxMessageAndThread(
                $replyArray->user_id,
                $system_number,
                $user_number,
                $coversation_field['gateway_type'],
                $processed_reply['message'],
                'OUT');


            if ($gateWay == 'twilio') $this->twilio_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'plivo') $this->plivo_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'alpha_1') $this->alpha1Reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field,$processed_reply['campaign_id']);
            else if ($gateWay == 'bandwidth') $this->bandwidth_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'aerialink') $this->aerialink_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'clx') $this->clx_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'inteliquent') $this->inteliquent_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'telnyx') $this->telnyx_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'alpha_2') $this->alpha_2_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'primerworks') $this->primerworks_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field, $processed_reply['campaign_id']);
            else if ($gateWay == 'infobip') $this->infobip_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'tyntec') $this->tyntec_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == '1s2u') $this->oneS2u_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'smswarriors') $this->smswarriors_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'imagine_global') $this->imagineGlobalSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'mmd') $this->mmdSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'message360') $this->message360Send($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'charlie') $this->charlieSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field, $processed_reply['campaign_id']);
            else if ($gateWay == 'mobinity') $this->mobinitySend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'semysms') $this->semySmsSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'sendii_route_bravo') $this->clx2_reply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'first_point') $this->firstPointSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'sendii_route_alpha') $this->sendiiRouteAlphaReply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'delta') $this->deltaReply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field, $processed_reply['campaign_id']);
            else if ($gateWay == 'signal_wire' || $gateWay == 'signalwire') $this->signalWireReply($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field);
            else if ($gateWay == 'ghost') $this->ghostSend($processed_reply['message'], $replyArray->user_id, $user_number, $system_number, $coversation_field, $processed_reply['campaign_id']);

            $this->Mdl_global->updateCreditHistory($replyArray->user_id, $coversation_field['gateway_type'], 'sms', 1);
        } else {

            $insert_array = [
                'date' => $this->mongo->date(),
                'expireDate' => $this->mongo->date('', false, "+6 months"),
                'gateway_type' => $coversation_field['gateway_type'],
                'user_id' => $coversation_field['user_id'],
                'system_number_id' => $coversation_field['system_number_id'],
                'system_number' => $coversation_field['system_number_org'],
                'user_number' => $coversation_field['user_number'],
                'replyAble' => $coversation_field['replyAble'],
                'positive' => $coversation_field['positive'],
                'keyword_id' => $coversation_field['keyword_id'],
                'text' => $coversation_field['text'],
                'reply_text' => $coversation_field['reply_text'],


            ];
            $this->Mdl_global->insert_row('keyword_conversation', $insert_array);

        }
        $this->Mdl_credit->chargeIncomingSmsAndUpdateHistory($coversation_field['system_number_org'], $coversation_field['gateway_type']);
        if ($gateWay == 'signal_wire' || $gateWay == 'signalwire' || $gateWay == 'delta' ) {
            ob_clean();
            echo "<?xml version='1.0' encoding='UTF-8'?><Response></Response>";
        }
    }


    public function from_num_edit()
    {
        $result = $this->repo->twilioFromNumbersGroupLists()->find();
        foreach ($result as $key => $value) {
            echo $value->from_number;
            $number = str_replace('+', '', $value->from_number);
            echo '&nbsp;&nbsp;&nbsp;&nbsp;' . $number . '<br>';
            $this->repo->twilioFromNumbersGroupLists()->updateById(
                $value->_id,
                ['from_number' => $number]
            );
        }
    }


    public function test_campaign_insert()
    {
        die();
        $to_number_groups = $this->mongo->fetch('groups', ['user_id' => $this->mongo->_id('58735d18b2683b13f440236c'), 'total_data_in_group' => 10000], ['sort' => ['_id' => 1], 'limit' => 15, 'skip' => 0]);
        $to_number_groups_array_all = [];
        if ($to_number_groups) {
            $i = 1;
            foreach ($to_number_groups as $key => $value) {
                $to_number_groups_array_all[] = $this->mongo->_id($value->_id);
                $i++;
            }
        }

        for ($i = 0; $i < 15; $i++) {
            $to_number_groups_array = [];
            shuffle($to_number_groups_array_all);
            $to_number_groups_array[] = $to_number_groups_array_all[0];
            print_r($to_number_groups_array);
            echo '<br><br><br><br><br>';
            $data = [
                "user_id" => $this->mongo->_id("58735d18b2683b13f440236c"),
                "type" => 1,
                "from_number_group" => $this->mongo->_id("58ab3496b219f7001509bec4"),
                "from_number_group_name" => "Twilio Group 1",
                "to_number_groups" => $to_number_groups_array,
                "copilot_id" => null,
                "template" => "[[FIRST_NAME]] [[LAST_NAME]] {get|grab|see|receive} {an xtra|another|a second} {ck|chck|chk} {for|4} {451.46|539.34|319.28|672.67|763.74} {rply|txt|respnd} {yes|ok} {to inquire|to have detaiIs|to get detail} {End|Stop|Cancel} {2|to} {Stop|End} [[NUMBER_OPERATOR]]",
                "total_contact" => 10000,
                "total_sent" => 0,
                "status" => 1,
                "tracking_url" => "",
                "add_date" => $this->mongo->date(),
                "expireDate" => $this->mongo->date('', false, "+6 months"),
                "seed_send" => false,
                "schedule_at" => $this->mongo->date(),
                "backend_status" => "ACTIVE",
                "summary" => [
                    "SENT" => 0
                ],
                "progress" => 0
            ];

            $this->Mdl_global->insert_row('campaigns', $data);
        }
    }

    public function test_campaign_insert_2()
    {
        die();
        $to_number_groups = $this->mongo->fetch('groups', ['user_id' => $this->mongo->_id('58c4387333b03718e467c7bb'), 'total_data_in_group' => 10000], ['sort' => ['_id' => 1], 'limit' => 15, 'skip' => 0]);
        $to_number_groups_array_all = [];
        if ($to_number_groups) {
            $i = 0;
            foreach ($to_number_groups as $key => $value) {
                $group_id = $this->mongo->_id($value->_id);
                $to_number_groups_array = [];
                $to_number_groups_array[] = $group_id;
                echo $i . ' ';
                print_r($to_number_groups_array);
                echo '<br><br>';
                $data = [
                    "user_id" => $this->mongo->_id("58c4387333b03718e467c7bb"),
                    "type" => 1,
                    "from_number_group" => $this->mongo->_id("58c443c833b0371ab76e2854"),
                    "from_number_group_name" => "Twilio Group 1",
                    "to_number_groups" => $to_number_groups_array,
                    "copilot_id" => null,
                    "template" => "[[FIRST_NAME]] [[LAST_NAME]] {get|grab|see|receive} {an xtra|another|a second} {ck|chck|chk} {for|4} {451.46|539.34|319.28|672.67|763.74} {rply|txt|respnd} {yes|ok} {to inquire|to have detaiIs|to get detail} {End|Stop|Cancel} {2|to} {Stop|End} [[NUMBER_OPERATOR]]",
                    "total_contact" => 10000,
                    "total_sent" => 0,
                    "status" => 1,
                    "tracking_url" => "",
                    "add_date" => $this->mongo->date(),
                    "expireDate" => $this->mongo->date('', false, "+6 months"),
                    "seed_send" => false,
                    "schedule_at" => $this->mongo->date(),
                    "backend_status" => "ACTIVE",
                    "summary" => [
                        "SENT" => 0
                    ],
                    "progress" => 0
                ];

                $this->Mdl_global->insert_row('campaigns', $data);
                $i++;
            }
        }


    }

    public function contact_files()
    {
        $result = $this->repo->contactFileLoadQue()->find(['sort' => ['_id' => -1]]);
        if ($result) {
            $i = 0;
            foreach ($result as $key => $value) {
                $i++;
                $value->add_date = $this->mongo->date_format_1($value->add_date);
                $value->upload_flag = (($value->upload_flag) ? "true" : "false");
                echo $i . ' ';
                $this->Mdl_global->print_array($value);
            }
        }
    }

    public function campaign_api($user_id = null)
    {
        $client = new Client();

        $SystemAuthToken = $this->Mdl_global->getSystemAuthToken($user_id);

        $response = $client->request('POST', 'http://54.85.120.91:8181/TwilioAI/api/engine/campaign/pause', [
            'form_params' => [
                'campaign_id' => '587d034cdddd203dad748246'
            ],
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
                'authToken' => $SystemAuthToken,
            ],

        ]);
        $response_str = $response->getBody()->getContents();
        echo $response_str;
    }

    public function count_contact($id)
    {
        echo $this->repo->contacts()->countByGroupId($id);

    }

    public function count_total_contact()
    {
        $result = $this->mongo->sum_field(
            'groups',
            [
                '_id' => null,
                'total_data_in_group' => ['$sum' => '$total_data_in_group']
            ],
            $parms = [
                'base_file_id' => $this->mongo->_id('5890c48f14ae7530dc2eb155')
            ]
        );
        $this->Mdl_global->print_array($result);
    }

    public function print_all_group()
    {
        $result = $this->mongo->fetch(
            'groups',
            [
                'user_id' => $this->mongo->_id($this->session_user_id),
                'total_data_in_group' => ['$gt' => 0]
            ]
        );
        $this->Mdl_global->print_array($result);

    }

    public function campaigns()
    {
        $result = $this->repo->campaigns()->find();
        if ($result) {
            foreach ($result as $key => $value) {
                $value->add_date = $value->add_date;
                $value->add_date_2 = $this->mongo->date_format_1($value->add_date);
                $value->schedule_at = $value->schedule_at;
                $value->schedule_at_2 = $this->mongo->date_format_1($value->schedule_at);
                $value->broadcast_started_at = $this->mongo->date_format_1($value->broadcast_started_at);
                $this->Mdl_global->print_array($value);
                echo '<br>-<br>-<br>-<br>-<br>-';
            }
        }
        echo '<br>-<br>-<br>-<br>-<br>-';
        $date = $this->mongo->date();
        echo $date_2 = $this->mongo->date_format_1($date);
    }

    public function group_by_hour($start, $end, $limit = 1000, $skip = 0)
    {
        $limit = intval($limit);
        $skip = intval($skip);
        $start = $this->mongo->date($start . ' 00:00:00', false);
        $end = $this->mongo->date($end . ' 23:59:59', false);
        $result = $this->repo->reports()->getBetweenDates($start, $end, ['limit' => $limit, 'skip' => $skip]);
        foreach ($result as $key => $value) {
            echo '<br>';
            echo $value->from_number . '--------' . $this->mongo->date_format_1($value->date);
        }
    }

    public function operatior_count()
    {
        $result = $this->mongo->load_contact_splitter_file_list_number_operator_by_group('589c991f14ae751748232303', 1000, 0);
        $this->Mdl_global->print_array($result);
    }

    public function twilio_get_numbers()
    {
        require_once(getcwd() . '/application/controllers/Services/Twilio.php');
        $data['auth_type'] = 1;

        $result_1 = $this->Mdl_global->auth_id(1, $this->mongo->_id($this->session_user_id), true);
        if ($result_1) {
            $sid = $result_1->auth_key;
            $token = $result_1->auth_token;
            $client = new Services_Twilio($sid, $token);
            $data['number'] = $client->account->incoming_phone_numbers;

            if (count($data['number']) > 0) {
                foreach ($data['number'] as $key => $value) {
                    echo $value->phone_number . ', sid: ' . $value->sid . '<br>';

                }

            }
        }

    }

    public function remove_special_char()
    {
        $value = '[[FIRST_NAME]] {sp\CDn|sp\ECn|spin|sp\EFn} {at|here|and seee} smartbegining.com';
        echo htmlentities($value);
    }

    public function running_contacts()
    {

        $result = $this->repo->campaigns()->findCampaignsByUserIdAndStatus(
            $this->mongo->user_id(),
            [
                'ACTIVE',
                'QUEUED',
                'PAUSING',
                'PAUSED',
                'RESUMED',
                'PROCESSING',
                'STOPPING',
            ]
        );

        $busy_groups = [];
        $busy_groups_2 = [];
        if ($result) {
            foreach ($result as $key => $value) {
                foreach ($value->to_number_groups as $key_2 => $value_2) {
                    if (!isset($busy_groups_2[(string)$value_2]))
                        $busy_groups[] = $value_2;

                    $busy_groups_2[(string)$value_2] = $value_2;
                }

            }
            $this->Mdl_global->print_array($busy_groups);
        }
    }

    public function campaign()
    {
        $this->view_data['view']['page_tab_title'] = 'Campaign';
        $this->view_data['view']['page_title'] = 'Campaign';
        $this->view_data['view']['page_sub_title'] = '';
        $this->view_data['view']['section'] = 'campaign';
        $this->view_data['view']['menu'] = 'campaign';
        $this->view_data['view']['submenu'] = '';

        $this->load->view('user/dashboard', $this->view_data);
    }

    public function config_c()
    {
        print_r($this->mongo->config_c());
    }

    public function test_sum()
    {
        $sum_1 = $this->mongo->sum_field('groups', ['_id' => null, 'total_data_in_group' => ['$sum' => '$total_data_in_group']], ['_id' => ['$ne' => null]]);
        print_r($sum_1);
    }

    public function make_user_for_share()
    {
        $result_1 = $this->repo->user()->find();
        if ($result_1) {
            $i = 0;
            $j = 0;
            foreach ($result_1 as $key => $value) {
                if (!$this->repo->optOutShareUser()->checkExistByUserId($value->_id)) {
                    $this->mongo->insert(
                        'optout_share_user',
                        ['user_id' => $this->mongo->_id($value->_id), 'import_user_id' => [$this->mongo->_id($value->_id)]]
                    );
                    $i++;
                }

                if (!$this->repo->blacklistShareUser()->checkExistByUserId($value->_id)) {
                    $this->mongo->insert(
                        'blacklist_share_user',
                        ['user_id' => $this->mongo->_id($value->_id), 'import_user_id' => [$this->mongo->_id($value->_id)]]
                    );
                    $j++;
                }
            }
            echo 'optout_share_user created = ' . $i . '<br>';
            echo 'blacklist_share_user created = ' . $j . '<br>';
        }
    }

    function startup()
    {


        die();

        $result_2 = $this->repo->user()->find();
        if ($result_2) {
            foreach ($result_2 as $key => $value) {
                $gateway_access = [1, 2];
                $this->repo->user()->updateGateWayAccess($value->_id, $gateway_access);
            }
        }

        die();

        $result_1 = $this->repo->apiAuth()->find();
        if ($result_1) {
            foreach ($result_1 as $key => $value) {
                $details = [];
                if ($value->type == 1) {
                    $details = [];
                    $details['Account_SID'] = $value->auth_key;
                    $details['Auth_Token'] = $value->auth_token;
                }
                if ($value->type == 2) {
                    $details = [];
                    $details['Auth_ID'] = $value->auth_key;
                    $details['Auth_Token'] = $value->auth_token;
                }
                if ($value->type == 3) {
                    $details = [];
                    $details['User_ID'] = $value->bandwidth_user_id;
                    $details['Api_Secret'] = $value->auth_key;
                    $details['Api_Token'] = $value->auth_token;
                }
                $this->repo->apiAuth()->updateById($value->_id, ['auth' => $details]);
            }
        }
    }

    function cross_domain_check()
    {


        header("Content-Type: application/json; charset=UTF-8");
        $data = $_GET;

        $myJSON = json_encode($data);
        echo "myFunc(" . $myJSON . ");";

    }

    function bandwidth_api_test()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.catapult.inetwork.com/v1/users/u-7hdp3vrlnt5xpwhv3edvfnq/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

        curl_setopt($ch, CURLOPT_USERPWD, "t-y6dfhluolpkfozvjqrksv7q" . ":" . "35qv5ljbdxx5jgnjsrc34rzkfzwln74ko7ks7ry");

        $headers = array();
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $response_array = json_decode($result);

        $this->Mdl_global->print_array($response_array);

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
    }

    function bandwidth_send_test()
    {
        $auth = $this->Mdl_global->auth_id(3, $this->mongo->user_id(), true);
        if ($auth) {
            $secret = $auth->auth_key;
            $token = $auth->auth_token;
            $bandwidth_user_id = $auth->bandwidth_user_id;
            $params = [
                'from' => '+' . $this->Mdl_global->number_len($_GET['to']),
                'to' => '+' . $this->Mdl_global->number_len($_GET['from']),
                'text' => $_GET['text'],
                'receiptRequested' => 'all',
                'callbackUrl' => "http://" . $this->server_ip . "/Public_controller/bandwidth_reply_callback",
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://api.catapult.inetwork.com/v1/users/$bandwidth_user_id/messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERPWD, "$token" . ":" . "$secret");

            $headers = array();
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response_array = [];
            $result = curl_exec($ch);
            echo $result;
            $response_array = json_decode($result);
            if (curl_errno($ch)) {
                echo 'Error:' . curl_error($ch);
            }
            print_r($params);
            $this->Mdl_global->print_array($response_array);

            curl_close($ch);
        }
    }

    // voice mail play

    public function playVoiceMail(){
        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_HUMAN'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_flush();
                echo
                    "<?xml version='1.0' encoding='UTF-8'?>
                <Response>
                    <play>".$audioLibrary->file_url."</play>
                    <Hangup></Hangup>
                </Response>";
            }
        }
    }

    public function playDeltaVoiceMail(){
        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_HUMAN'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_clean();
                echo trim('<?xml version="1.0" encoding="UTF-8"?>
                        <Response>
                            <Play>'.$audioLibrary->file_url.'</Play>
                        </Response>');
            }
        }
    }

    public function playPlivoVoiceMail(){
        $this->mongo->insert('test_logs',['playPlivoVoiceMail'=>$_REQUEST]);

        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_HUMAN'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_clean();
                echo trim('<?xml version="1.0" encoding="UTF-8"?>
                        <Response>
                            <Play>'.$audioLibrary->file_url.'</Play>
                        </Response>');
            }
        }
    }

    public function playMachineVoiceMail(){
        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_MACHINE'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_clean();
                echo "<?xml version='1.0' encoding='UTF-8'?>
                <Response>
                    <play>".$audioLibrary->file_url."</play>
                    <Hangup></Hangup>
                </Response>";
            }
        }
    }

    public function playDeltaMachineVoiceMail(){
        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_MACHINE'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_clean();
                echo trim('<?xml version="1.0" encoding="UTF-8"?>
                        <Response>
                            <Play>'.$audioLibrary->file_url.'</Play>
                        </Response>');
            }
        }
    }

    public function playPlivoMachineVoiceMail(){
        $this->mongo->insert('test_logs',['playPlivoMachineVoiceMail'=>$_REQUEST]);
        $voiceCampaignId = $_REQUEST['voiceCampaignId'];
        $voiceMailId = $_REQUEST['voiceMailFileId'];
        $audioLibrary = $this->mongo->fetch_1('audio_libraries',['_id'=>$this->mongo->_id($voiceMailId)]);
        if($audioLibrary){
            $updateStatus = $this->mongo->update('voice_campaigns',
                ['_id'=>$this->mongo->_id($voiceCampaignId)],
                [
                    '$inc'=>['summary.ANSWERED_MACHINE'=>1]
                ],
                [
                    'operator'=>true
                ]);
            if($updateStatus){
                ob_clean();
                echo trim('<?xml version="1.0" encoding="UTF-8"?>
                        <Response>
                            <Play>'.$audioLibrary->file_url.'</Play>
                        </Response>');
            }
        }
    }



}

