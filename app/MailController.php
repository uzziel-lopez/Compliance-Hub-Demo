<?php
    include "phpmailer/class.phpmailer.php";
    include "phpmailer/class.smtp.php";
    
    class MailController {
        private $mail;
        
        private function env($key, $default = '') {
            $value = getenv($key);
            if ($value === false || $value === null || $value === '') return $default;
            return $value;
        }

        public function __construct(){
            try {
                $this->mail = new PHPMailer();
                $fromName = $this->env('APP_SMTP_FROM_NAME', 'Compliance Hub');
                $fromEmail = $this->env('APP_SMTP_FROM_EMAIL', 'no-reply@example.com');
                $smtpEnabled = filter_var($this->env('APP_SMTP_ENABLED', 'false'), FILTER_VALIDATE_BOOLEAN);

                $this->mail->CharSet = 'UTF-8';
                $this->mail->From = $fromEmail;
                $this->mail->FromName = $fromName;
                $this->mail->SMTPAuth = $smtpEnabled;

                if ($smtpEnabled) {
                    $smtpPort = (int)$this->env('APP_SMTP_PORT', '587');
                    $smtpSecure = $this->env('APP_SMTP_SECURE', 'tls');

                    $this->mail->IsSMTP();
                    $this->mail->Host = $this->env('APP_SMTP_HOST', 'smtp.example.com');
                    $this->mail->Username = $this->env('APP_SMTP_USERNAME', '');
                    $this->mail->Password = $this->env('APP_SMTP_PASSWORD', '');
                    $this->mail->Port = $smtpPort;
                    if (!empty($smtpSecure)) {
                        $this->mail->SMTPSecure = $smtpSecure;
                    }
                }
            } catch (Exception $e) {
                $errorMessage = 'Exception caught: ' . $e->getMessage();
                $this->logError($errorMessage); // Registrar el error
                return $errorMessage;
            }
        }
        
        public function sendNoticeCustomers($dataNotice, $emails, $dataUser, $dataFolder) {
            try {
                $fromName = $this->env('APP_SMTP_FROM_NAME', 'Compliance Hub');
                $fromEmail = $this->env('APP_SMTP_FROM_EMAIL', 'no-reply@example.com');

                $this->mail->Subject = "Compliance Hub || Nuevo seguimiento";
                $this->mail->IsHTML(true);
                $this->mail->ClearReplyTos();
                $this->mail->addReplyTo($fromEmail, $fromName);
                
                // Iterar sobre los correos electrónicos
                foreach ($emails as $email) {
                    $this->mail->addAddress($email);
                }
                
                // Generar el cuerpo del correo usando la plantilla
                ob_start();
                // Combinar todas las variables en un solo array asociativo
                $templateData = array_merge($dataNotice, $dataUser, $dataFolder);
                extract($templateData, EXTR_SKIP);
                include 'emailsTemplates/sendNoticeCustomers.php';
                $this->mail->Body = ob_get_clean();
                
                // Enviar el correo
                if (!$this->mail->send()) {
                    $errorMessage = 'Mailer Error: ' . $this->mail->ErrorInfo;
                    $this->logError($errorMessage); // Registrar el error
                    return $errorMessage;
                }

                // Limpiar destinatarios para evitar problemas en futuros envíos
                $this->mail->clearAddresses();
                return true;
            } catch (Exception $e) {
                $errorMessage = 'Exception caught: ' . $e->getMessage();
                $this->logError($errorMessage); // Registrar el error
                return $errorMessage;
            }
        }
        
        /* Función para registrar errores en un archivo .log */
        private function logError($message) {
            $logFile = __DIR__ . '/MailError.log'; // Archivo donde se almacenarán los errores
            $date = date('Y-m-d H:i:s'); // Fecha y hora actual
            $formattedMessage = "[{$date}] ERROR: {$message}\n"; // Formato del mensaje
            file_put_contents($logFile, $formattedMessage, FILE_APPEND); // Escribir en el archivo
        }
    }
?>
