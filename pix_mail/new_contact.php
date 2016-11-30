<?php
if($_POST)
{
    // PixFort Contact Form
    // $mail_type = "ce";
    // $customEmail = false;
    // $to_Email       = "pixfort.com@gmail.com"; //Replace with recipient email address
    // $subject        = 'An email from FLATPACK contact form'; //Subject line for emails
    // //-----------------------------------------------------------------------------------------
    
    // // your recaptcha secret key
    // $secret = "6LfUPcYSAAAAAE_4f6VG9wDfQBJi47I8eeT4lspa";      // Add your reCAPTCHA secret key
    // //----------------------------------------------------------    -------------------------------


    // /* Mailchimp setting. To enable a setting, uncomment (remove the '#' at the start of the line) */
    // $MailChimp = false;
    // define('MC_APIKEY', '9b07b7022c21e0d049427ef551d88bc1-us10'); // Your API key from here - http://admin.mailchimp.com/account/api
    // define('MC_LISTID', 'e04199343e'); // List unique id from here - http://admin.mailchimp.com/lists/

    // /* Campaign Monitor setting. */
    // $CampaignMonitor = false;
    // define('CM_APIKEY', 'dd27956d3f26b2e04d17b4d034f7d52f'); // Your APIKEY from here - https://pixfort.createsend.com/admin/account/
    // define('CM_LISTID', '34f39f7422c747040d6e7432f761b2b9'); // List ID from here - https://www.campaignmonitor.com/api/getting-started/#listid

    // /* GetResponse setting. To enable a setting, uncomment (remove the '#' at the start of the line)*/
    // $GetResponse = true;
    // define('GR_APIKEY', '4fe5967b0ef47a6a2bc881663d54d8f7'); // Your API key from here - https://app.getresponse.com/my_api_key.html
    // define('GR_CAMPAIGN', 'flatpacktest1'); // Campaign name from here - https://app.getresponse.com/campaign_list.html
    
    // /* AWeber setting. To enable a setting, uncomment (remove the '#' at the start of the line)*/
    // $AWeber = false;
    // define('AW_AUTHCODE', ''); // Your Authcode from here - https://auth.aweber.com/1.0/oauth/authorize_app/647b2efd
    // define('AW_LISTNAME', ''); // List name from here - https://www.aweber.com/users/autoresponder/manage

    include("config.php");    


    //-----------------------------------------------------------------------------------------
    //-----------------------------------------------------------------------------------------
    $use_reCaptcha = false;   
    if($secret != ""){
        $use_reCaptcha = true;   
    }


    /* Install headers */
    header('Expires: 0');
    header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
    header('Pragma: no-cache');
    header('Content-Type: application/json; charset=utf-8');


    if($use_reCaptcha){
        // empty response
        $response = null;
        // grab recaptcha library
        require_once "recaptchalib.php";
        // check secret key
        $reCaptcha = new ReCaptcha($secret);
    }
    require_once('api_mailchimp/MCAPI.class.php');
    require_once('api_getresponse/GetResponseAPI.class.php');
    require_once('api_campaign/CMBase.php');
    require_once('api_aweber/aweber_api.php');


    //check if its an ajax request, exit if not
    if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        //exit script outputting json data
        $output = json_encode(
        array(
            'type'=>'error', 
            'text' => 'Request must come from Ajax'
        ));
        die($output);
    } 
    

    $values = array($_POST);
    $o_string = "";
    $user_Email = $to_Email;
    $pix_extra = array();
    $has_type = false;
    $the_type = "";
    foreach ($values as  $value) {
        foreach ($value as $variable => $v) {
            if(filter_var($variable, FILTER_SANITIZE_STRING) == 'pixfort_form_type'){
                if(filter_var($variable, FILTER_SANITIZE_STRING) != ''){
                    $the_type = $v;
                    $has_type =true;
                }
            }elseif(filter_var($variable, FILTER_SANITIZE_STRING) == 'g-recaptcha-response'){
                if($use_reCaptcha){
                    $response = $reCaptcha->verifyResponse(
                        $_SERVER["REMOTE_ADDR"],
                        $v
                    );
                    if ($response == null || (!$response->success)) {
                        $output = json_encode(array('type'=>'error', 'text' => 'Please check the Captcha!'));
                        die($output);
                    }
                }
            }else{
                $o_string .= filter_var($variable, FILTER_SANITIZE_STRING) . ': '. filter_var($v, FILTER_SANITIZE_STRING) ." -  \n";
                if(filter_var($variable, FILTER_SANITIZE_STRING) == 'email'){
                    $user_Email = $v;
                    if(!validMail($user_Email)) //email validation
                    {
                        $output = json_encode(array('type'=>'error', 'text' => 'Please enter a valid email!'));
                        die($output);
                    }
                }else{
                    $pix_extra[filter_var($variable, FILTER_SANITIZE_STRING)] = filter_var($v, FILTER_SANITIZE_STRING);
                }
                // if($use_reCaptcha){
                //     if(filter_var($variable, FILTER_SANITIZE_STRING) == 'g-recaptcha-response'){
                //         $response = $reCaptcha->verifyResponse(
                //             $_SERVER["REMOTE_ADDR"],
                //             $v
                //         );
                //         if ($response == null || (!$response->success)) {
                //             $output = json_encode(array('type'=>'error', 'text' => 'Please check the Captcha!'));
                //             die($output);
                //         }
                //     }
                // }
            }
        }
    }

    if($has_type){
        if($the_type == 'ce'){
            pixmail($o_string, $user_Email, $to_Email, $subject);
        }elseif($the_type == 'mc'){
            sendMailChimp($user_Email, $pix_extra);    
        }elseif($the_type == 'cm'){
            sendCampaign($user_Email, $pix_extra);   
        }elseif($the_type == 'gr'){
            sendGetResponse($user_Email, $pix_extra);
        }elseif($the_type == 'aw'){
            sendAWeber($user_Email, $pix_extra);
        }else{
            $output = json_encode(array('type'=>'error', 'text' => 'Error: Wrong pix-form-type attribute provided for the form!'));
            die($output);
        }
    }else{
        if($mail_type == 'ce'){
            pixmail($o_string, $user_Email, $to_Email, $subject);
        }elseif($mail_type == 'mc'){
            sendMailChimp($user_Email, $pix_extra);    
        }elseif($mail_type == 'cm'){
            sendCampaign($user_Email, $pix_extra);   
        }elseif($mail_type == 'gr'){
            sendGetResponse($user_Email, $pix_extra);
        }elseif($mail_type == 'aw'){
            sendAWeber($user_Email, $pix_extra);
        }else{
            $output = json_encode(array('type'=>'error', 'text' => 'Error: Wrong mail_type attribute provided in config.php file!'));
            die($output);
        }
    }
    
} // End POST

    function pixmail($o_string, $user_Email, $to_Email, $subject)
    {
        $final_msg = "\n"."Subscribe using flatpack form,"."\n";
        $final_msg .= $o_string;
            
        //proceed with PHP email.
        $headers = 'From: '.$user_Email.'' . "\r\n" .
        'Reply-To: '.$user_Email.'' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
        
        // send mail
        $sentMail = @mail($to_Email, $subject, $final_msg, $headers);
        
        
        if(!$sentMail)
        {
            $output = json_encode(array('type'=>'error', 'text' => 'Could not send mail! Please check your PHP mail configuration.'));
            die($output);
        }else{
            $output = json_encode(array('type'=>'message', 'text' => 'Hi, Thank you for your email'));
            die($output);
        }
    }

    function validMail($email)
    {
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        } else {
            return false;
        }
    }

    function sendMailChimp($mailSubscribe, $merge_vars=NULL)
    {
        // $ii = serialize($merge_vars);
        // $output = json_encode(array('type'=>'error', 'text' => 'Hi, Thank you for your emails'. $ii ));
        //  die($output);
        if(defined('MC_APIKEY') && defined('MC_LISTID')){
            $api = new MCAPI(MC_APIKEY);
          //   $merge_vars2 = array(
          //        'NAME' => "frias",
          //        'NUMBER' => '34234234234'
          //   );
          //   $ii = serialize($merge_vars);
          //   $output = json_encode(array('type'=>'error', 'text' => 'Hi, Thank you for your emails'. $ii ));
          // //die($output);
            if($api->listSubscribe(MC_LISTID, $mailSubscribe, $merge_vars) !== true){
                if($api->errorCode == 214){
                    $output = json_encode(array('type'=>'error', 'text' => 'Email Already Exists'));
                } else {
                    $output = json_encode(array('type'=>'error', 'text' => $api->errorMessage));
                    //errorLog("MailChimp","[".$api->errorCode."] ".$api->errorMessage);
                    die($output);
                }
            }else{
                $output = json_encode(array('type'=>'message', 'text' => 'Hi, Thank you for your email'));
                die($output);
            }
        }
    }


    function sendCampaign($mailSubscribe, $merge_vars=NULL)
    {
        if(defined('CM_APIKEY') && defined('CM_LISTID')){
            
            $api_key = CM_APIKEY;
            $client_id = null;
            $campaign_id = null;
            $list_id = CM_LISTID;
            $cm = new CampaignMonitor( $api_key, $client_id, $campaign_id, $list_id );
            $result = $cm->subscriberAddWithCustomFields($mailSubscribe, getName($mailSubscribe), $merge_vars, null, false);
            if($result['Code'] == 0){
                $output = json_encode(array('type'=>'message', 'text' => 'Thank you for your Subscription.'));
                die($output);
            }else{
                $output = json_encode(array('type'=>'error', 'text' => 'Error : ' . $result['Message']));
                die($output);
            }
        }
    }

    function sendGetResponse($mailSubscribe, $merge_vars=NULL)
    {
        if(defined('GR_APIKEY') && defined('GR_CAMPAIGN')){
            $api = new GetResponse(GR_APIKEY);
            
            $campaign = $api->getCampaignByName(GR_CAMPAIGN);

            $subscribe = $api->addContact($campaign, getName($mailSubscribe), $mailSubscribe, 'standard', 0, $merge_vars);
            //$firas = $api->getContacts($campaign);
            //$firas = json_decode($subscribe, true);
             // $output = json_encode(array('type'=>'error', 'text' => 'err: '. serialize($subscribe) ));
             // die($output);
            //if(array_key_exists('duplicated', $subscribe)){
            if($subscribe){
                $output = json_encode(array('type'=>'message', 'text' => 'Thank you for your Subscription.'));
                die($output);
            }else{
                $output = json_encode(array('type'=>'error', 'text' => 'Error: Email Already Exists'));
                die($output);
            }
        }
    }

    function sendAWeber($mailSubscribe, $merge_vars=NULL)
    {
        if(defined('AW_AUTHCODE') && defined('AW_LISTNAME') && $merge_vars){
            $token = 'api_aweber/'. substr(AW_AUTHCODE, 0, 10);
            
            if(!file_exists($token)){
                try {
                    $auth = AWeberAPI::getDataFromAweberID(AW_AUTHCODE);
                    file_put_contents($token, json_encode($auth));
                } catch(AWeberAPIException $exc) {
                    errorLog("AWeber","[".$exc->type."] ". $exc->message ." Docs: ". $exc->documentation_url);
                    throw new Exception("Authorization error",5);
                }  
            }
            
            if(file_exists($token)){
                $key = file_get_contents($token);
            }
            list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = json_decode($key);
            
            $aweber = new AWeberAPI($consumerKey, $consumerSecret);
            try {
                $account = $aweber->getAccount($accessToken, $accessSecret);
                $foundLists = $account->lists->find(array('name' => AW_LISTNAME));
                $lists = $foundLists[0];
                
                
                if(!isset($merge_vars['name'])){
                    $pix_extra['name'] = getName($mailSubscribe);
                }
                $custom_arr = array();
                foreach ($merge_vars as $variable => $v) {
                    if($variable != 'name'){
                        $custom_arr[filter_var($variable, FILTER_SANITIZE_STRING)] = filter_var($v, FILTER_SANITIZE_STRING);
                    }
                }

                $params = array(
                    'email' => $mailSubscribe,
                    'name' => $merge_vars['name'],
                    'custom_fields' => $custom_arr
                );
            
                if(isset($lists)){
                    $lists->subscribers->create($params);
                    $output = json_encode(array('type'=>'message', 'text' => 'Thank you for your Subscription.'));
                    die($output);
                } else{
                    //errorLog("AWeber","List is not found");
                    $output = json_encode(array('type'=>'error', 'text' => 'Error: List is not found'));
                    die($output);
                    //throw new Exception("Error found Lists",4);
                }
        
            } catch(AWeberAPIException $exc) {
                if($exc->status == 400){
                    //throw new Exception("Email exist",2);
                    $output = json_encode(array('type'=>'error', 'text' => 'Error: Email Already Exists'));
                    die($output);
                }else{
                    //errorLog("AWeber","[".$exc->type."] ". $exc->message ." Docs: ". $exc->documentation_url);
                    $output = json_encode(array('type'=>'error', 'text' => 'Error: '."[".$exc->type."] ". $exc->message ." Docs: ". $exc->documentation_url));
                    die($output);
                }
            }
        }else{
            $output = json_encode(array('type'=>'error', 'text' => 'Error: AWeber configuration Error, please check config.php settings!'));
            die($output);
        }
    }

    function errorLog($name,$desc)
    {
        file_put_contents(ERROR_LOG, date("m.d.Y H:i:s")." (".$name.") ".$desc."\n", FILE_APPEND);
    }

    function getName($mail)
    {
        preg_match("/([a-zA-Z0-9._-]*)@[a-zA-Z0-9._-]*$/",$mail,$matches);
        return $matches[1];
    }

?>