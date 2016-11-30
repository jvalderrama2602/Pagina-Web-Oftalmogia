<?php
if($_POST)
{
    $to_Email       = "pixfort.com@gmail.com"; //Replace with recipient email address
    $subject        = 'An email from FLATPACK contact form'; //Subject line for emails
    
    include("config.php");        
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
    

    //check $_POST vars are set, exit if any missing
    if( !isset($_POST["user_name"]) || !isset($_POST["user_email"]) )
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Input fields are empty!'));
        die($output);
    }


    //Sanitize input data using PHP filter_var().
    $user_Name        = filter_var($_POST["user_name"], FILTER_SANITIZE_STRING);
    $user_Email       = filter_var($_POST["user_email"], FILTER_SANITIZE_EMAIL);
    
    //additional php validation
    if(strlen($user_Name)<4) // If length is less than 4 it will throw an HTTP error.
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Name is too short or empty!'));
        die($output);
    }
    if(!filter_var($user_Email, FILTER_VALIDATE_EMAIL)) //email validation
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Please enter a valid email!'));
        die($output);
    }

    // if(!is_numeric($user_Phone)) //check entered data is numbers
    // {
    //     $output = json_encode(array('type'=>'error', 'text' => 'Only numbers allowed in phone field'));
    //     die($output);
    // }
    // if(strlen($user_Message)<5) //check emtpy message
    // {
    //     $output = json_encode(array('type'=>'error', 'text' => 'Too short message! Please enter something.'));
    //     die($output);
    // }
    
    //proceed with PHP email.
    $headers = 'From: '.$user_Email.'' . "\r\n" .
    'Reply-To: '.$user_Email.'' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
    
        // send mail
    $sentMail = @mail($to_Email, $subject, 'Subscribe using flatpack form, Name: '. $user_Name .'  -- Email: '.$user_Email , $headers);
    
    if(!$sentMail)
    {
        $output = json_encode(array('type'=>'error', 'text' => 'Could not send mail! Please check your PHP mail configuration.'));
        die($output);
    }else{
        $output = json_encode(array('type'=>'message', 'text' => 'Hi, Thank you for your email'));
        die($output);
    }
}
?>