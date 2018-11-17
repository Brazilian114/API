<?php
require 'config.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();





$app->post('/login','login'); /* User login */
$app->post('/signup','signup'); /* User Signup  */
//app->get('/getFeed','getFeed'); /* User Feeds  */
$app->post('/feed','feed'); /* User Feeds  */
$app->post('/feedUpdate','feedUpdate'); /* User Feeds  */
$app->post('/profileUpdate','profileUpdate');
$app->post('/history','history');

$app->post('/feedDelete','feedDelete'); /* User Feeds  */
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
        $sql = "SELECT *  FROM customer  INNER JOIN province ON customer.province=province.province_id WHERE  email=:username and password=:password ";
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
    $province_name=$data->province_name;
    
    $password=$data->password;
    
    try {
        
        
        $emain_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        $password_check = preg_match('~^[A-Za-z0-9!@#$%^&*()_]{6,20}$~i', $password);
        
        
        
        if (strlen(trim($username))>0 && strlen(trim($password))>0 && strlen(trim($email))>0 && $emain_check>0  && $password_check>0)
        {
            $db = getDB();
            $userData = '';
            $sql = "SELECT * FROM customer INNER JOIN province ON customer.province=province.province_id WHERE username=:username or email=:email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username,PDO::PARAM_STR);
            $stmt->bindParam("email", $email,PDO::PARAM_STR);
            
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $created=time();
            if($mainCount==0)
            {
                
                /*Inserting user values*/
                $sql1="INSERT INTO customer(username,password,email,tel,province,license)VALUES(:username,:password,:email,:tel,:province_name,:license)";
                $stmt1 = $db->prepare($sql1);
                $stmt1->bindParam("username", $username,PDO::PARAM_STR);
                $password=hash('sha256',$data->password);
                $stmt1->bindParam("password", $password,PDO::PARAM_STR);
                $stmt1->bindParam("email", $email,PDO::PARAM_STR);
                $stmt1->bindParam("tel", $tel,PDO::PARAM_STR);
                $stmt1->bindParam("province_name", $province_name,PDO::PARAM_STR);
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
        $sql = "SELECT user_id, email, username, tel, license, province_name FROM customer INNER JOIN province ON customer.province=province.province_id 
                       WHERE username=:input or email=:input";
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
    $service_name=$data->service_name;
    
    $time=$data->time;
    $time2=$data->time2;
    $status_id=$data->status_id;
    $user_type=$data->user_type;
    $lastInsertId=$data->lastInsertId;
    
    

    
    $systemToken=apiToken($user_id);
    try {
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            $sql = "INSERT INTO booking ( username ,license ,province,time_id,status_id, tel,created,user_id_fk,user_type) VALUES 
                                        (:username,:license,:province,:time,:status_id,:tel,:created,:user_id,:user_type)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->bindParam("license", $license, PDO::PARAM_STR);
            $stmt->bindParam("province", $province, PDO::PARAM_STR);
            //$stmt->bindParam("service_name", $service_name,PDO::PARAM_STR);
            $stmt->bindParam("time", $time, PDO::PARAM_STR);
            $stmt->bindParam("status_id", $status_id, PDO::PARAM_STR);
            $stmt->bindParam("tel", $tel, PDO::PARAM_STR);
            $created = time();
            $stmt->bindParam("created", $created, PDO::PARAM_STR);
            $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam("user_type", $user_type, PDO::PARAM_INT);
            
            $mainCount=$stmt->rowCount();
            $stmt->execute();
            $id = $db->lastInsertId();

           if($mainCount ==0)
                {
            $feedData = '';
            $db = getDB();

                 $sql="INSERT INTO booking_detail (booking_service_id, booking_id) VALUES
                                                  (:service_name,:id)";
                 $stmt = $db->prepare($sql);
                      for($i=0;$i<=$service_name;$i++)
                            {

                             $stmt->bindParam("service_name", $service_name[$i],PDO::PARAM_STR);
                             $stmt->bindParam("id", $id,PDO::PARAM_STR);
                             $stmt->execute();
                         
                            }
                
                 } 

            /*$sql1 = "SELECT * FROM booking_detail  INNER JOIN booking_service ON booking_detail.booking_service_id=booking_service.booking_service_id INNER JOIN booking ON booking_detail.booking_id=booking.booking_id
                              WHERE booking_id=:id ORDER BY detail_id DESC ";*/
              $sql1 = "SELECT * FROM booking INNER JOIN booking_time ON booking.time_id=booking_time.time_id LEFT JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                              WHERE user_id_fk=:user_id  ORDER BY booking_id DESC LIMIT 1";
            $stmt1 = $db->prepare($sql1);
            $stmt1->bindParam("user_id", $user_id, PDO::PARAM_INT);
            //$stmt1->bindParam("id", $id, PDO::PARAM_INT);
            
            
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

/*
function feedUpdate(){
    
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $username=$data->username;
    $license=$data->license;
    $tel=$data->tel;
    $province=$data->province;
    $service_name=$data->service_name;
    //$service_name = array();
    $time=$data->time;
    $time2=$data->time2;
    $status_id=$data->status_id;
    $user_type=$data->user_type;
    $lastInsertId=$data->lastInsertId;
    

    $systemToken=apiToken($user_id);

    try {
        if($systemToken == $token){

            $feedData = '';
            $db = getDB(); 

            
            $sql = "INSERT INTO booking ( username ,license ,province,time_id ,status_id, tel,created,user_id_fk,user_type) VALUES 
                                        (:username,:license,:province,:time,:status_id,:tel,:created,:user_id,:user_type)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->bindParam("license", $license, PDO::PARAM_STR);
            $stmt->bindParam("province", $province, PDO::PARAM_STR);
            $stmt->bindParam("service_name", $service_name, PDO::PARAM_STR);
            
            $stmt->bindParam("time", $time, PDO::PARAM_STR);
            $stmt->bindParam("status_id", $status_id, PDO::PARAM_STR);
            $stmt->bindParam("tel", $tel, PDO::PARAM_STR);
            $created = time();
            $stmt->bindParam("created", $created, PDO::PARAM_STR);
            $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam("user_type", $user_type, PDO::PARAM_INT);
            $mainCount=$stmt->rowCount();
            $stmt->execute();
            $id = $db->lastInsertId();

            if($mainCount==0){
               
                
                
                $sql3="INSERT INTO booking_detail(booking_service_id,booking_id)VALUES
                                                 (:service_name,:id)";
                $stmt3 = $db->prepare($sql3);
                $stmt3->bindParam("id", $id,PDO::PARAM_STR); 
                $stmt3->bindParam("service_name", $service_name,PDO::PARAM_STR);
                

                $stmt3->execute();      
                  
                
              }
            
            $sql1 = "SELECT * FROM booking INNER JOIN booking_time ON booking.time_id=booking_time.time_id LEFT JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                              WHERE user_id_fk=:user_id AND booking_id=:booking_id ORDER BY booking_id DESC LIMIT 1";
            $sql2 = "SELECT * FROM booking_detail  INNER JOIN booking_service ON booking_detail.booking_service_id=booking_service.booking_service_id INNER JOIN booking ON booking_detail.booking_id=booking.booking_id
                               WHERE booking_id=:id ORDER BY detail_id DESC LIMIT 1";
           
            $stmt1 = $db->prepare($sql1,$sql2);
            
            $stmt1->bindParam("user_id", $user_id, PDO::PARAM_INT);
            //$stmt1->bindParam("id", $id, PDO::PARAM_INT);
            
           
            
            
            $stmt1->execute();
            
            $feedData = $stmt1->fetch(PDO::FETCH_OBJ);
            
            
            $db = null;
            echo '{"feedData": ' . json_encode($feedData) . '}';
            
        }
   
        else{
            echo '{"error":{"text":"No access"}}';
        }

    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }

}
*/
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
            $sql = "UPDATE customer SET email = :email, username = :username, license = :license, province = :province,
                    tel = :tel  WHERE user_id = :user_id"; 
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
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



function feed(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $lastCreated = $data->lastCreated;
    
    
    $systemToken=apiToken($user_id);
   
    try {
         
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            if($lastCreated){
                $sql = "SELECT *  FROM booking 
                
                        INNER JOIN customer ON booking.user_id_fk=customer.user_id LEFT JOIN booking_time ON booking.time_id=booking_time.time_id 
                        INNER JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                        INNER JOIN status ON booking.status_id=status.status_id WHERE user_id_fk=:user_id AND created < :lastCreated ORDER BY booking_id DESC LIMIT 5 ";
                $stmt = $db->prepare($sql);
                $stmt->bindParam("lastCreated", $lastCreated, PDO::PARAM_STR);
                $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
             }
            else{
                $sql = "SELECT * FROM booking 
                               
                               INNER JOIN customer ON booking.user_id_fk=customer.user_id LEFT JOIN booking_time ON booking.time_id=booking_time.time_id 
                               INNER JOIN status ON booking.status_id=status.status_id
                               INNER JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                               WHERE user_id_fk=:user_id AND booking.status_id < '3' ORDER BY booking_id DESC LIMIT 5";
                $stmt = $db->prepare($sql);
                $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
                
                
                
            }
            
            $stmt->execute();
            $feedData = $stmt->fetchAll(PDO::FETCH_OBJ);
           
            $db = null;
            if($feedData){
                echo '{"feedData": ' . json_encode($feedData) . '}';
            }
            else{
                echo '{"feedData": "" }';
            }
            
        } else{
            echo '{"error":{"text":"No access"}}';
        }
       
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
    
    
}
function history(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $lastCreated = $data->lastCreated;
    
    
    $systemToken=apiToken($user_id);
   
    try {
         
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            if($lastCreated){
                $sql = "SELECT *  FROM booking 
                
                        INNER JOIN customer ON booking.user_id_fk=customer.user_id LEFT JOIN booking_time ON booking.time_id=booking_time.time_id 
                        INNER JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                        INNER JOIN status ON booking.status_id=status.status_id WHERE user_id_fk=:user_id AND created < :lastCreated ORDER BY booking_id DESC ";
                $stmt = $db->prepare($sql);
                $stmt->bindParam("lastCreated", $lastCreated, PDO::PARAM_STR);
                $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
             }
            else{
                $sql = "SELECT * FROM booking 
                               
                               INNER JOIN customer ON booking.user_id_fk=customer.user_id LEFT JOIN booking_time ON booking.time_id=booking_time.time_id 
                               INNER JOIN status ON booking.status_id=status.status_id
                               INNER JOIN booking_service ON booking.booking_service_id=booking_service.booking_service_id
                               WHERE user_id_fk=:user_id AND booking.status_id = '3' ORDER BY booking_id DESC ";
                $stmt = $db->prepare($sql);
                $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
                
                
                
            }
            
            $stmt->execute();
            $feedData = $stmt->fetchAll(PDO::FETCH_OBJ);
           
            $db = null;
            if($feedData){
                echo '{"feedData": ' . json_encode($feedData) . '}';
            }
            else{
                echo '{"feedData": "" }';
            }
            
        } else{
            echo '{"error":{"text":"No access"}}';
        }
       
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
    
    
}


function feedDelete(){
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $user_id=$data->user_id;
    $token=$data->token;
    $booking_id=$data->booking_id;
    
    $systemToken=apiToken($user_id);
   
    try {
         
        if($systemToken == $token){
            $feedData = '';
            $db = getDB();
            $sql = "DELETE FROM booking  WHERE user_id_fk=:user_id AND booking_id=:booking_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam("booking_id", $booking_id, PDO::PARAM_INT);
            $stmt->execute();
            
           
            $db = null;
            echo '{"success":{"text":"Feed deleted"}}';
        } else{
            echo '{"error":{"text":"No access"}}';
        }
       
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
}

?>







