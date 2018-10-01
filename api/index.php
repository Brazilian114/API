<?php
require 'config.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();





$app->post('/login','login'); /* User login */
$app->post('/signup','signup'); /* User Signup  */
//app->get('/getFeed','getFeed'); /* User Feeds  */
//$app->post('/feed','feed'); /* User Feeds  */
$app->post('/feedUpdate','feedUpdate'); /* User Feeds  */
$app->post('/profileUpdate','profileUpdate');

//$app->post('/feedDelete','feedDelete'); /* User Feeds  */
//$app->post('/getImages', 'getImages');

$app->run();

/************************* USER LOGIN *************************************/
/* ### User login ### */
function login() {
    
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    
    try {
        
        $db = getDB();
        $userData ='';
        $sql = "SELECT *  FROM customer WHERE  email=:username and password=:password ";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("username", $data->username, PDO::PARAM_STR);
        $password=hash('sha256',$data->password);
        $stmt->bindParam("password", $password, PDO::PARAM_STR);
        $stmt->execute();
        $mainCount=$stmt->rowCount();
        $userData = $stmt->fetch(PDO::FETCH_OBJ);
        
        if(!empty($userData))
        {
            $user_id=$userData->user_id;
            $userData->token = apiToken($user_id);
        }
        
        $db = null;      
        echo '{"userData": ' .json_encode($userData) . '}';
          
            

           
    }
    catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
}




function signup() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $email=$data->email;
    $username=$data->username;
    $tel=$data->tel;
    $license=$data->license;
    $province=$data->province;
    $status=$data->status;
    $password=$data->password;
    
    try {
        
        
        $emain_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        $password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,20}$~i', $password);
        
        
        
        if (strlen(trim($username))>0 && strlen(trim($password))>0 && strlen(trim($email))>0 && $emain_check>0  && $password_check>0)
        {
            $db = getDB();
            $userData = '';
            $sql = "SELECT user_id FROM customer WHERE username=:username or email=:email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username,PDO::PARAM_STR);
            $stmt->bindParam("email", $email,PDO::PARAM_STR);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $created=time();
            if($mainCount==0)
            {
                
                /*Inserting user values*/
                $sql1="INSERT INTO customer(username,password,email,tel,province,license)VALUES(:username,:password,:email,:tel,:province,:license)";
                $stmt1 = $db->prepare($sql1);
                $stmt1->bindParam("username", $username,PDO::PARAM_STR);
                $password=hash('sha256',$data->password);
                $stmt1->bindParam("password", $password,PDO::PARAM_STR);
                $stmt1->bindParam("email", $email,PDO::PARAM_STR);
                $stmt1->bindParam("tel", $tel,PDO::PARAM_STR);
                $stmt1->bindParam("province", $province,PDO::PARAM_STR);
                $stmt1->bindParam("license", $license,PDO::PARAM_STR);
                
                $stmt1->execute();
                
                $userData=internalUserDetails($email);
                
            }
            
            $db = null;
            echo '{"userData": ' . json_encode($userData) . '}';
                           
           }
       }
       catch(PDOException $e) {
           echo '{"error":{"text":'. $e->getMessage() .'}}';
       }
}

function internalUserDetails($input) {
    
    try {
        $db = getDB();
        $sql = "SELECT user_id, email, username, tel, license, province  FROM customer WHERE username=:input or email=:input";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("input", $input,PDO::PARAM_STR);
        $stmt->execute();
        $usernameDetails = $stmt->fetch(PDO::FETCH_OBJ);
        $usernameDetails->token = apiToken($usernameDetails->user_id);
        $db = null;
        return $usernameDetails;
        
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
}

function feedUpdate(){
    
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $username=$data->username;
    $license=$data->license;
    $tel=$data->tel;
    $province=$data->province;
    $booking_service_id=$data->booking_service_id;
    $datetime=$data->datetime;
    $status=$data->status;
    
    $systemToken=apiToken($user_id);

    try {
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            $sql = "INSERT INTO booking ( username ,license ,province, booking_service_id, datetime, status, tel,user_id_fk) VALUES 
                                        (:username,:license,:province,:booking_service_id,:datetime,:status,:tel,:user_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->bindParam("license", $license, PDO::PARAM_STR);
            $stmt->bindParam("province", $province, PDO::PARAM_STR);
            $stmt->bindParam("booking_service_id", $booking_service_id, PDO::PARAM_STR);
            $stmt->bindParam("datetime", $datetime, PDO::PARAM_STR);
            $stmt->bindParam("status", $status, PDO::PARAM_STR);
            $stmt->bindParam("tel", $tel, PDO::PARAM_STR);
            
            $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
            
            $stmt->execute();

            $sql1 = "SELECT * FROM booking WHERE user_id_fk=:user_id  ORDER BY 
                           booking_id DESC LIMIT 1";
            $stmt1 = $db->prepare($sql1);
            $stmt1->bindParam("user_id", $user_id, PDO::PARAM_INT);
            
            $stmt1->execute();
            $feedData = $stmt1->fetch(PDO::FETCH_OBJ);
            $db = null;
            echo '{"feedData": ' . json_encode($feedData) . '}';
        } else{
            echo '{"error":{"text":"No access"}}';
        }

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}

function profileUpdate(){
    
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $email=$data->email;
    $username=$data->username;
    $license=$data->license;
    $tel=$data->tel;
    $province=$data->province;
   
    
    $systemToken=apiToken($user_id);

    try {
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            $sql = "UPDATE customer SET email = :email, username = :username, license = :license,
                    tel = :tel, province = :province  WHERE user_id = :user_id"; 
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $username, PDO::PARAM_STR);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->bindParam("license", $license, PDO::PARAM_STR);
            $stmt->bindParam("province", $province, PDO::PARAM_STR);           
            $stmt->bindParam("tel", $tel, PDO::PARAM_STR);
            
            $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
            
            $stmt->execute();

            
            $db = null;
            echo '{"feedData": ' . json_encode($feedData) . '}';
        } else{
            echo '{"error":{"text":"No access"}}';
        }

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}
?>

