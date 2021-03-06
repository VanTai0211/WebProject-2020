<?php
include_once("./mvc/core/DB.php");
include_once("./mvc/models/UserModel.php");
//use PHP mailler
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
    class Login extends Controller {  
        //Handle page, get model data and view
        public function LoginPage($page){
            if ( isset($_COOKIE["Name"]) && isset($_COOKIE["Pass"]) ) {
                $this -> view("LoginPage",["Dashboard" => $this->dashboard,"Page" => $page,"Name" => $_COOKIE["Name"], "Pass" => $_COOKIE["Pass"]]);
            }
            else {
            $this -> view("LoginPage",["Dashboard" => $this->dashboard,"Page" => $page,"Name" => "", "Pass" => ""]);
            }
        }
        //Check Input function 
        protected function test_input($data) {
            $data = trim($data);                    //strip unnecessary characters
            $data = stripslashes($data);            //remove backslashes
            $data = htmlspecialchars($data);        //Escape htmlSpecialChar
            return $data;
        }
        //Handle Register, handle data
        public function HandleRegister(){
            $userFullName = $userName = $userPass = $userEmail = $userGender = $userPhoneNumber = $userAddress = "";
            //make random verify code
            $vkey = md5(time().$userName);
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $userFullName = $this -> test_input($_POST["userFullName"]);
                $userName = $this -> test_input($_POST["userName"]);
                $userPass = $this -> test_input($_POST["userPass"]);
                $userEmail = $this -> test_input($_POST["userEmail"]);
                $userGender = $this -> test_input($_POST["userGender"]);
                $userPhoneNumber = $this -> test_input($_POST["userPhoneNumber"]);
                $userAddress = $this -> test_input($_POST["userAddress"]);
                //Check exits captcha
                if(  !empty($_POST['g-captcha'])) {
                    $secret_key = '6LeXwKwZAAAAAEm5Fe_SoyAnAuMc7IdZeqWNgQRm';
    
                    $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='.$secret_key.'&response='.$_POST['g-captcha']);
                    //json_decode m?? h??a d??? li???u th??nh object
                    $response_data = json_decode($response);
    
                    if(!$response_data->success)
                    {
                        $data  = array('fail' =>  "L???i captcha !");
                    }
                    //Continue
                    else {
                        $userModel = new UserModel();
                        //Check exits email, each account have only 1 mail
                        if( $userModel -> checkExitsEmail( $userEmail ) ){
                            if( $userModel -> checkExitsLoginName($userName) ) {
                                //Insert new User
                                $insertU = $userModel -> insertUser($userFullName,$userName,$userPass,$userEmail,$vkey,$userGender,$userPhoneNumber,$userAddress);
                                if( $insertU ) {
                                    include_once './mvc/core/library.php';                     // include the library file
                                    require './mvc/core/vendor/autoload.php';
                                    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
                                    try {
                                        //Server settings
                                        $mail->CharSet = "UTF-8";
                                        $mail->SMTPDebug = 0;                                  // Enable verbose debug output
                                        $mail->isSMTP();                                      // Set mailer to use SMTP
                                        $mail->Host = SMTP_HOST;                                // Specify main and backup SMTP servers
                                        $mail->SMTPAuth = true;                               // Enable SMTP authentication
                                        $mail->Username = SMTP_UNAME;                           // SMTP username
                                        $mail->Password = SMTP_PWORD;                           // SMTP password
                                        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
                                        $mail->Port = SMTP_PORT;                               // TCP port to connect to
                                        //Recipients
                                        $mail->setFrom(SMTP_UNAME, "Ng?? H???u V??n");
                                        $mail->addAddress($_POST['userEmail'], $userFullName); // Add a recipient | name is option
                                        $mail->addReplyTo(SMTP_UNAME, 'Ng?? H???u V??n');
                                        $mail->isHTML(true);                                  // Set email format to HTML
                                        $mail->Subject = "Th?? x??c nh???n Email";
                                        $mail->Body = "Xin ch??o $userName, ????y l?? th?? x??c nh???n Email c???a b???n.<br>
                                                        Vui l??ng nh???n v??o <a href='http://localhost/WebProject-2020/Vertify/VertifyHandle/$vkey'>???????ng d???n n??y</a> 
                                                        ????? x??c nh???n t??i kho???n c???a b???n!";
                                        $mail->AltBody = ""; //None HTML
                                        $result = $mail->send();
                                        //infused data to client
                                        if (!$result) {
                                            $data = array( 'fail'  => "C?? l???i trong qu?? tr??nh g???i Email!" );
                                        }else{
                                            $data = array( 'success'  => "B???n ???? t???o t??i kho???n th??nh c??ng. Vui l??ng ki???m tra h???p th?? ????? k??ch ho???t t??i kho???n! " );
                                        }
                                    } catch (Exception $e) {
                                        $data = array( 'fail'  => "C?? l???i x???y ra!" );
                                    }
                                }
                                else {
                                    $data = array( 'fail'  => "L???i khi th??m user m???i" );
                                }
                            }
                            else {
                                $data = array( 'fail' => "T??n ???? ???????c s??? d???ng!" );
                            }
                        }
                        else{
                            $data = array( 'fail'  => "Email ???? ???????c s??? d???ng!" );
                        }
                        //encode data to object
                        echo json_encode($data);
                        //Close connection
                        $userModel -> closeConnection();
                    }
                }
            }
        }
        public function HandleLogin(){
            $userName = $userPass = "";
            //Check method
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $userName = $this -> test_input($_POST["userName"]);
                $userPass = $this -> test_input($_POST["userPass"]);
                if(!empty($_POST["remember"])){
                    setcookie("Name",$userName, time() + (86400 * 30), "/");
                    setcookie("Pass",$userPass, time() + (86400 * 30), "/");
                }else{
                    setcookie("Name","", time() - 3600, "/");
                    setcookie("Pass","", time() - 3600, "/");	
                }
                $userModel = new UserModel();
                $user = $userModel -> Login($userName,$userPass);
                if( $user != NULL ) {
                    $confirm = $user["confirm"];
                    if($confirm == 1){
                        $_SESSION["user"]=$userName;
                        $_SESSION["password"] = $userPass;
                        $_SESSION["userid"] = $user ["userid"];
                        $_SESSION["userfullname"] = $user["fullname"];
                        $data = array(
                            'success'  => true
                        );
                    }
                    else{
                        $data = array( 'fail'  => "T??i kho???n n??y ch??a ???????c x??c nh???n!
                         Vui l??ng check mail x??c nh???n t??i kho???n!"  );
                    }
                }
                else {
                    $data = array( 'fail'  => "Sai t??n t??i kho???n ho???c m???t kh???u!"  );
                }
                echo json_encode($data);
            }
        }   
        //ramdom password
        private function randomPassword() {
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            return implode($pass); //turn the array into a string
        }
        //Reset Password
        public function resetPass(){
            $rsUserEmail = "";
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                
                    if( isset($_POST["rsUserEmail"]) ){
                        $rsUserEmail = $this -> test_input($_POST["rsUserEmail"]);
                        $userModel = new UserModel();
                        $checkMail = $userModel -> checkExitsEmail( $rsUserEmail );
                        //Check exits email, each account have only 1 mail
                        if( !$checkMail ){
                                $rsPass = $userModel -> resetPass($rsUserEmail,$this->randomPassword());
                                if( $rsPass ) {
                                    include_once './mvc/core/library.php';                     // include the library file
                                    require './mvc/core/vendor/autoload.php';
                                    $mail = new PHPMailer(true);     
                                    $randomP = $this->randomPassword();                         // Passing `true` enables exceptions
                                    try {
                                        //Server settings
                                        $mail->CharSet = "UTF-8";
                                        $mail->SMTPDebug = 0;                                  // Enable verbose debug output
                                        $mail->isSMTP();                                      // Set mailer to use SMTP
                                        $mail->Host = SMTP_HOST;                                // Specify main and backup SMTP servers
                                        $mail->SMTPAuth = true;                               // Enable SMTP authentication
                                        $mail->Username = SMTP_UNAME;                           // SMTP username
                                        $mail->Password = SMTP_PWORD;                           // SMTP password
                                        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
                                        $mail->Port = SMTP_PORT;                               // TCP port to connect to
                                        //Recipients
                                        $mail->setFrom(SMTP_UNAME, "Ng?? H???u V??n");
                                        $mail->addAddress($rsUserEmail, "A"); // Add a recipient | name is option
                                        $mail->addReplyTo(SMTP_UNAME, 'Ng?? H???u V??n');
                                        $mail->isHTML(true);                                  // Set email format to HTML
                                        $mail->Subject = "Th?? ?????t l???i m???t kh???u";
                                        $mail->Body = "Xin ch??o $rsUserEmail, m???t kh???u m???i c???a b???n l?? $randomP. <br>
                                        H??y ????ng nh???p v?? ?????i l???i m???t kh???u ngay ??i n??o!";
                                        $mail->AltBody = ""; //None HTML
                                        $result = $mail->send();
                                        //infused data to client
                                        if (!$result) {
                                            $data = array( 'fail'  => "C?? l???i trong qu?? tr??nh g???i Email!" );
                                        }
                                        else{
                                            $data = array( 'success'  => "B???n ???? ?????t l???i m???t kh???u. Vui l??ng ki???m tra h???p th?? ! " );
                                        }
                                    } catch (Exception $e) {
                                        $data = array( 'fail'  => "C?? l???i x???y ra!" );
                                    }
                                }
                                else {
                                    $data = array( 'fail'  => "C?? l???i khi ?????t l???i m???t kh???u" );
                                }
                            
                        }
                        else{
                            $data = array( 'fail'  => "Email ch??a ???????c ????ng k??!" );
                        }
                    }
                    else {
                        $data = array( 'fail'  => "Email kh??ng ???????c r???ng!" );
                    }
                     //encode data to object
                     echo json_encode($data);       
            }
        } 

        public function Check(){
           
        }


    }
?>