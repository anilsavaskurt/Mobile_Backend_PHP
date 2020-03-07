<?php

//database bilgilerinin girildiği kısım
$host = "";
$user = "";
$pass = "";
$db = "";


try {
    //database bağlantısını kuruyoruz
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $pass);

    //json olarak ekrana bastırılacak error message ve işlem yapılan datayı array olarak döndüren fonksiyon
    function createOutput($error, $response_message, $response_data = array())
    {
        $output_array = array(
            'error' => $error,
            'message' => $response_message,
            'data' => $response_data
        );
        return $output_array;
    }

    //işlem türünü istek olarak alıyoruz(select,insert,update,delete) gibi
    $operation_type = $_POST['operation_type'];
    //işlem yapılacak bölümü seçiyoruz(bu uygulamada sadece notes tablomuz var)
    $service_type = $_POST['service_type'];

    //giriş sorgulama
    if ($operation_type == "login" && $service_type == "UserList") {

        $email = $_POST['User_email'];
        $pass = $_POST['User_password'];

        $query = $pdo->prepare("SELECT * FROM  UserList WHERE User_email = :mail AND  User_password = :pass");
        $query->bindParam(":mail", $email, PDO::PARAM_STR);
        $query->bindParam(":pass", $pass, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Hatalı Giriş', []);
            echo json_encode($output);
            return;
        }

        $output = createOutput('false', "Giris basarili", $result);
        echo json_encode($output);
    } else if ($operation_type == 'sing_up' && $service_type == 'UserList') {

        $name = $_POST['User_name'];
        $surname = $_POST['User_surname'];
        $email = $_POST['User_email'];
        $pass = $_POST['User_password'];
        $birthday = $_POST['User_birth_date'];
        $city = $_POST['User_living_city'];

        $query = $pdo->prepare("INSERT INTO UserList(User_name,User_surname,User_email,User_password,User_birth_date,User_living_city) VALUES(:username,:surname ,:email,:password,:birthdays,:city)");
        $query->bindParam(":username", $name, PDO::PARAM_STR);
        $query->bindParam(":surname", $surname, PDO::PARAM_STR);
        $query->bindParam(":email", $email, PDO::PARAM_STR);
        $query->bindParam(":password", $pass, PDO::PARAM_STR);
        $query->bindParam(":birthdays", $birthday, PDO::PARAM_STR);
        $query->bindParam(":city", $city, PDO::PARAM_STR);
        $process = $query->execute();

        if (!$process) {

            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        }
        $result = array(
            'User_name' => $name,
            'User_surname' => $surname,
            'User_email' => $email,
            'User_password' => $pass,
            'User_birth_date' => $birthday,
            'User_living_city' => $city
        );
        $output = createOutput('false', "Kayıt Başarılı", $result);
        echo json_encode($output);
    } else if ($operation_type == 'forget_password' && $service_type == 'UserList') {

        $email = $_POST['User_email'];
        //kullanıcının mailine göre şifresini bul
        $query = $pdo->prepare("SELECT User_password FROM UserList WHERE User_email = :email");
        $query->bindParam(":email", $email, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);

        require("mail/class.phpmailer.php");
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPDebug = 1; // debug mod: 1  olması faydali, hata varsa gosterir.
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'tls';
        $mail->Host = ""; // Hosting hesabinizda ki domaininiz, mail.siteadresiniz.com gibi kullanin.
        $mail->Port = 587;
        $mail->IsHTML(true);
        $mail->SetLanguage("tr", "phpmailer/language");
        $mail->CharSet = "utf-8";
        $mail->Username = ""; // Hosting hesabinizda actiginiz mail adresi
        $mail->Password = ""; // mail adresi sifresi
        $mail->SetFrom("", ""); // Mail attigimizda yazacak isim
        $mail->AddAddress($email); // Maili gonderecegimiz kisi/ alici
        $mail->Subject = "Forgot Password"; // Konu basligi
        $mail->Body = "Podcast Şifreniz :" . $result["User_password"]; // Mailin icerigi

        if (!$mail->Send()) {
            // echo "Mailer Error: ".$mail->ErrorInfo;
            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        } else {
            $output = createOutput('false', "Şifre Mail Adresine Gönderildi", []);
            echo json_encode($output);
        }
    } else if ($operation_type == 'change_password' && $service_type == 'UserList') {

        $email = $_POST['User_email'];
        $old_password = $_POST['User_oldPassword'];
        $new_password1 = $_POST['User_newPassword1'];
        $new_password2 = $_POST['User_newPassword2'];

        $query = $pdo->prepare("SELECT * FROM UserList WHERE User_email =:email AND User_password=:old_pass");
        $query->bindParam(":email", $email, PDO::PARAM_STR);
        $query->bindParam(":old_pass", $old_password, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Girdiğiniz Email veya Şifre Yanlış', []);
            echo json_encode($output);
            return;
        } else {

            if ($new_password1 == $new_password2) {

                $update = $pdo->prepare("UPDATE UserList SET User_password=:new_pass WHERE User_email =:email ");
                $update->bindParam(":new_pass", $new_password1, PDO::PARAM_STR);
                $update->bindParam(":email", $email, PDO::PARAM_STR);
                $process = $update->execute();

                if (!$process) {
                    $output = createOutput('true', 'Bir Hata Oluştu', []);
                    echo json_encode($output);
                    return;
                }

                $result = array(
                    'User_email' => $email,
                    'User_oldPassword' => $old_password,
                    'User_newPassword1' => $new_password1,
                );
                $output = createOutput('false', "Şifre Değiştirildi", $result);
                echo json_encode($output);

            } else {
                $output = createOutput('false', "Girdiğiniz şifreler eşleşmiyor", $result);
                echo json_encode($output);
            }
        }
    } else if ($operation_type == 'get_all_books' && $service_type == 'BookList') {
        $email = $_POST['User_email'];
        $query = $pdo->prepare("SELECT BookList.Book_id, BookList.Book_name, BookList.Book_writer, BookList.Book_category, BookList.Total_pages, BookList.Book_duration, BookList.Edition_year, BookList.Publisher, BookList.Narrator_name, BookList.IsNotTranslated, BookList.Interpreter_name, BookList.Preview_text, BookList.Book_image, BookList.Book_views, Views.User_email, COALESCE (Views.Last_point,'00:00:00') AS Last_Point FROM BookList LEFT JOIN Views ON BookList.Book_id = Views.Book_id AND Views.User_email = :email GROUP BY BookList.Book_id");
        $query->bindParam(":email",$email,PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        }

        $output = createOutput('false', "Kitaplar Getiriliyor", $result);
        echo json_encode($output);

    }else if($operation_type == 'get_all_podcasts' && $service_type == 'Podcast'){

        $email = $_POST['User_email'];
        $query = $pdo->prepare("SELECT Podcast.Podcast_id,  Podcast.Title,  Podcast.Subject,  Podcast.Time,  Podcast.Likes,  Podcast.Rate,  Podcast.Number_rest,  Podcast.Subscribe, ViewsPodcast.User_email, COALESCE (ViewsPodcast.Last_point,'00:00:00') AS Last_Point FROM Podcast LEFT JOIN ViewsPodcast ON Podcast.Podcast_id = ViewsPodcast.Podcast_id AND ViewsPodcast.User_email = :email GROUP BY Podcast.Podcast_id");
        $query->bindParam(":email",$email,PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        }

        $output = createOutput('false', "Podcastler Getiriliyor", $result);
        echo json_encode($output);

    }else if($operation_type == 'get_last_books' && $service_type == 'Views'){

        $email = $_POST['User_email'];
        $query = $pdo->prepare("SELECT BookList.Book_id, BookList.Book_name, BookList.Book_writer, BookList.Book_category, BookList.Total_pages, BookList.Book_duration, BookList.Edition_year, BookList.Publisher, BookList.Narrator_name, BookList.IsNotTranslated, BookList.Interpreter_name, BookList.Preview_text, BookList.Book_image, BookList.Book_views, Views.User_email , Views.Last_point, Views.Update_time  FROM BookList INNER JOIN Views ON BookList.Book_id = Views.Book_id AND Views.User_email = :email GROUP BY Views.Update_time DESC");
        $query->bindParam(":email",$email,PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        }

        $output = createOutput('false', "Kitaplar Getiriliyor", $result);
        echo json_encode($output);


    }else if($operation_type == 'get_last_podcasts' && $service_type == 'ViewsPodcast'){

        $email = $_POST['User_email'];
        $query = $pdo->prepare("SELECT  Podcast.Podcast_id,  Podcast.Title,  Podcast.Subject,  Podcast.Time,  Podcast.Likes,  Podcast.Rate,  Podcast.Number_rest,  Podcast.Subscribe, ViewsPodcast.User_email, ViewsPodcast.Last_point, ViewsPodcast.Update_time  FROM Podcast INNER JOIN ViewsPodcast ON Podcast.Podcast_id = ViewsPodcast.Podcast_id AND ViewsPodcast.User_email = :email GROUP BY ViewsPodcast.Update_time DESC");
        $query->bindParam(":email",$email,PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $output = createOutput('true', 'Bir Hata Oluştu', []);
            echo json_encode($output);
            return;
        }

        $output = createOutput('false', "Podcastler Getiriliyor", $result);
        echo json_encode($output);



    } else if ($operation_type == 'views_information' && $service_type == 'Views') {

        $email = $_POST['User_email'];
        $book_id = $_POST['Book_id'];
        $last_point = $_POST['Last_point'];

        $query = $pdo->prepare("SELECT * FROM Views WHERE Book_id = :bid AND User_email = :email");
        $query->bindParam(":email", $email, PDO::PARAM_STR);
        $query->bindParam(":bid", $book_id, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $currentDateTime = date('Y-m-d H:i:s');
            $insert_query = $pdo->prepare("INSERT INTO Views(Book_id,User_email,Update_time) VALUES (:bid,:email,:insert_time)");
            $insert_query->bindParam(":bid", $book_id, PDO::PARAM_STR);
            $insert_query->bindParam(":email", $email, PDO::PARAM_STR);
            $insert_query->bindParam(":insert_time", $currentDateTime, PDO::PARAM_STR);
            $process = $insert_query->execute();

            if (!$process) {

                $output = createOutput('true', 'Bir Hata Oluştu', []);
                echo json_encode($output);
                return;

            } else {

                $result = array(
                    'User_email' => $email,
                    'Book_id' => $book_id,
                    'Last_point' => $last_point,
                    'Update_time' => $currentDateTime
                );

                $output = createOutput('false', "Kitap ve Kullanıcı Eşleştirildi", $result);
                echo json_encode($output);
                return;
            }

        } else {

            $currentDateTime = date('Y-m-d H:i:s');
            $update = $pdo->prepare("UPDATE Views SET Last_point = :last_point,Update_time = :insert_time WHERE User_email = :email AND Book_id = :bid");
            $update->bindParam(":email", $email, PDO::PARAM_STR);
            $update->bindParam(":bid", $book_id, PDO::PARAM_STR);
            $update->bindParam(":last_point", $last_point, PDO::PARAM_STR);
            $update->bindParam(":insert_time", $currentDateTime, PDO::PARAM_STR);
            $update->execute();
            $process = $query->execute();

            if (!$process) {
                $output = createOutput('true', 'Bir Hata Oluştu', []);
                echo json_encode($output);
                return;
            } else {

                $result = array(
                    'User_email' => $email,
                    'Book_id' => $book_id,
                    'Last_point' => $last_point,
                    'Update_time' => $currentDateTime
                );
                $output = createOutput('false', "Kalınan Son Nokta Güncellendi", $result);
                echo json_encode($output);
            }

        }
    }else if($operation_type == 'views_podcast_information' && $service_type == 'ViewsPodcast'){

        $email = $_POST['User_email'];
        $podcast_id = $_POST['Podcast_id'];
        $last_point = $_POST['Last_point'];

        $query = $pdo->prepare("SELECT * FROM ViewsPodcast WHERE Podcast_id = :pid AND User_email = :email");
        $query->bindParam(":email", $email, PDO::PARAM_STR);
        $query->bindParam(":pid",  $podcast_id, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        if (count($result) == 0) {

            $currentDateTime = date('Y-m-d H:i:s');
            $insert_query = $pdo->prepare("INSERT INTO ViewsPodcast(Podcast_id,User_email,Update_time) VALUES (:pid,:email,:insert_time)");
            $insert_query->bindParam(":pid", $podcast_id, PDO::PARAM_STR);
            $insert_query->bindParam(":email", $email, PDO::PARAM_STR);
            $insert_query->bindParam(":insert_time", $currentDateTime, PDO::PARAM_STR);
            $process = $insert_query->execute();

            if (!$process) {

                $output = createOutput('true', 'Bir Hata Oluştu', []);
                echo json_encode($output);
                return;

            } else {

                $result = array(
                    'User_email' => $email,
                    'Podcast_id' => $podcast_id,
                    'Last_point' => $last_point,
                    'Update_time' => $currentDateTime
                );

                $output = createOutput('false', "Podcast ve Kullanıcı Eşleştirildi", $result);
                echo json_encode($output);
                return;
            }

        } else {

            $currentDateTime = date('Y-m-d H:i:s');
            $update = $pdo->prepare("UPDATE ViewsPodcast SET Last_point = :last_point,Update_time = :insert_time WHERE User_email = :email AND Podcast_id = :pid");
            $update->bindParam(":email", $email, PDO::PARAM_STR);
            $update->bindParam(":pid", $podcast_id, PDO::PARAM_STR);
            $update->bindParam(":last_point", $last_point, PDO::PARAM_STR);
            $update->bindParam(":insert_time", $currentDateTime, PDO::PARAM_STR);
            $update->execute();
            $process = $query->execute();

            if (!$process) {
                $output = createOutput('true', 'Bir Hata Oluştu', []);
                echo json_encode($output);
                return;
            } else {

                $result = array(
                    'User_email' => $email,
                    'Podcast_id' => $podcast_id,
                    'Last_point' => $last_point,
                    'Update_time' => $currentDateTime
                );
                $output = createOutput('false', "Kalınan Son Nokta Güncellendi", $result);
                echo json_encode($output);
            }

        }

    } else{
           echo "ERROR ! 404 NOT FOUND...";
    }

}catch (PDOException $e){
        echo "Error!: ".$e->getMessage();
    }
?>
