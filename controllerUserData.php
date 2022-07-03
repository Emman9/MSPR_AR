<?php 
session_start();
require "connection.php";
$email = "";
$name = "";
$errors = array();

//si le bouton d'inscription de l'utilisateur
if(isset($_POST['signup'])){
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);

    if($password !== $cpassword){
        $errors['password'] = "le mot de passe ne correspond pas!";
    }

    $email_check = "SELECT * FROM usertable WHERE email = '$email'";

    $res = mysqli_query($con, $email_check);

    if(mysqli_num_rows($res) > 0){
        $errors['email'] = "L'e-mail que vous avez entré existe déjà!";
    }

    if(count($errors) === 0){
        $encpass = password_hash($password, PASSWORD_BCRYPT);
        $code = rand(999999, 111111);
        $status = "Non verifie";

        $insert_data = "INSERT INTO usertable (name, email, password, code, status)
                        values('$name', '$email', '$encpass', '$code', '$status')";

        $data_check = mysqli_query($con, $insert_data);

        if($data_check){
            $subject = 'Code de vérification de courrier électronique';
            $message = 'Votre code de vérification est: ';
            $pin = "$code" ;
            $sender = 'From: emmanuelatamadji99@gmail.com';

            if(mail ($email, $subject, $message, $pin, $sender) )
            {
                $info = 'Nous avons envoyé un code de vérification à votre adresse e-mail - $email';

                $_SESSION['info'] = $info;
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;

                header('location: user-otp.php');

                exit();

            }else{
                $errors['otp-error'] = "Échec lors de l'envoi du code !";
            }
        }else{
            $errors['db-error'] = "Échec lors de l'insertion des données dans la base de données !";
        }
    }

}
    //si l'utilisateur clique sur le bouton d'envoi du code de vérification
    if(isset($_POST['check'])){
        $_SESSION['info'] = "";
        $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
        $check_code = "SELECT * FROM usertable WHERE code = $otp_code";
        $code_res = mysqli_query($con, $check_code);

        if(mysqli_num_rows($code_res) > 0){
            $fetch_data = mysqli_fetch_assoc($code_res);
            $fetch_code = $fetch_data['code'];
            $email = $fetch_data['email'];
            $code = 0;
            $status = 'verifie';
            $update_otp = "UPDATE usertable SET code = $code, status = '$status' WHERE code = $fetch_code";
            $update_res = mysqli_query($con, $update_otp);
            if($update_res){
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                header('location: home.php');
                exit();
            }else{
                $errors['otp-error'] = "Échec lors de la mise à jour du code!";
            }
        }else{
            $errors['otp-error'] = "Vous avez saisi un code incorrect !";
        }
    }

    //si l'utilisateur clique sur le bouton de connexion
    if(isset($_POST['login'])){
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $password = mysqli_real_escape_string($con, $_POST['password']);
        $check_email = "SELECT * FROM usertable WHERE email = '$email'";
        $res = mysqli_query($con, $check_email);
        if(mysqli_num_rows($res) > 0){
            $fetch = mysqli_fetch_assoc($res);
            $fetch_pass = $fetch['password'];
            if(password_verify($password, $fetch_pass)){
                $_SESSION['email'] = $email;
                $status = $fetch['status'];
                if($status == 'vérifié'){
                  $_SESSION['email'] = $email;
                  $_SESSION['password'] = $password;
                    header('location: home.php');
                }else{
                    $info = "Il semble que vous n'ayez pas encore vérifié votre adresse e-mail - $email";
                    $_SESSION['info'] = $info;
                    header('location: user-otp.php');
                }
            }else{
                $errors['email'] = "Email ou mot de passe incorrect!";
            }
        }else{
            $errors['email'] = "Il semble que vous n'êtes pas encore membre ! Cliquez sur le lien du bas pour vous inscrire.";
        }
    }

    //si l'utilisateur clique sur le bouton continuer dans le formulaire de mot de passe oublié
    if(isset($_POST['check-email'])){
        $email = mysqli_real_escape_string($con, $_POST['email']);
        $check_email = "SELECT * FROM usertable WHERE email='$email'";
        $run_sql = mysqli_query($con, $check_email);
        if(mysqli_num_rows($run_sql) > 0){
            $code = rand(999999, 111111);
            $insert_code = "UPDATE usertable SET code = $code WHERE email = '$email'";
            $run_query =  mysqli_query($con, $insert_code);
            if($run_query){
                $subject = "Code de réinitialisation du mot de passe";
                $message = "Votre code de réinitialisation de mot de passe est $code";
                $sender = "From: beniameyikpo@gmail.com";
                if(mail($email, $subject, $message, $sender)){
                    $info = "Nous avons envoyé un otp de réinitialisation du mot de passe à votre adresse e-mail - $email";
                    $_SESSION['info'] = $info;
                    $_SESSION['email'] = $email;
                    header('location: reset-code.php');
                    exit();
                }else{
                    $errors['otp-error'] = "Échec lors de l'envoi du code !";
                }
            }else{
                $errors['db-error'] = "Quelque chose s'est mal passé !";
            }
        }else{
            $errors['email'] = "Cette adresse e-mail n'existe pas !";
        }
    }

    //si l'utilisateur clique sur le bouton de réinitialisation otp
    if(isset($_POST['check-reset-otp'])){
        $_SESSION['info'] = "";
        $otp_code = mysqli_real_escape_string($con, $_POST['otp']);
        $check_code = "SELECT * FROM usertable WHERE code = $otp_code";
        $code_res = mysqli_query($con, $check_code);
        if(mysqli_num_rows($code_res) > 0){
            $fetch_data = mysqli_fetch_assoc($code_res);
            $email = $fetch_data['email'];
            $_SESSION['email'] = $email;
            $info = "Veuillez créer un nouveau mot de passe que vous n'utilisez sur aucun autre site.";
            $_SESSION['info'] = $info;
            header('location: new-password.php');
            exit();
        }else{
            $errors['otp-error'] = "Vous avez saisi un code incorrect !";
        }
    }

    //si l'utilisateur clique sur le bouton de modification du mot de passe
    if(isset($_POST['change-password'])){
        $_SESSION['info'] = "";
        $password = mysqli_real_escape_string($con, $_POST['password']);
        $cpassword = mysqli_real_escape_string($con, $_POST['cpassword']);
        if($password !== $cpassword){
            $errors['password'] = "Confirmer le mot de passe ne correspond pas !";
        }else{
            $code = 0;
            $email = $_SESSION['email']; //obtenir cet e-mail en utilisant la session
            $encpass = password_hash($password, PASSWORD_BCRYPT);
            $update_pass = "UPDATE usertable SET code = $code, password = '$encpass' WHERE email = '$email'";
            $run_query = mysqli_query($con, $update_pass);
            if($run_query){
                $info = "Votre mot de passe a changé. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.";
                $_SESSION['info'] = $info;
                header('Location: password-changed.php');
            }else{
                $errors['db-error'] = "Échec de la modification de votre mot de passe !";
            }
        }
    }
    
   //si vous vous connectez maintenant, cliquez sur le bouton
    if(isset($_POST['login-now'])){
        header('Location: login-user.php');
    }
?>