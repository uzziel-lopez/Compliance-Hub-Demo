<?php
  class FileController {
    function __construct() {

    }
    public function getFolder($folder){
      // Lineas de código es para guardar las imagenes en un disco o unidad externa externo
      /*
      Los navegadores web no pueden acceder directamente a rutas del sistema de archivos local del servidor (como E:/uploads/users/).
      Los navegadores solo pueden acceder a rutas a través de URLs públicas (por ejemplo, usando HTTP o HTTPS).
      Esto se debe a razones de seguridad; el acceso directo a las rutas del sistema de archivos está prohibido
      No puedes usar rutas locales del sistema de archivos (como E:/) directamente en las URLs de tu código HTML/JavaScript.
      Necesitas una URL pública accesible a través de HTTP o HTTPS para mostrar las imágenes en el navegador.
      */
      // $server = "E:/uploads"; // Ajusta la ruta a tu estructura de carpetas
      
      // ESTE CÓDIGO ES PARA RUTAS RELATIVAS
      // Aquí puedes usar una ruta relativa en lugar de una absoluta
      $server = realpath(__DIR__ . '/../uploads'); // Ruta relativa para almacenamiento en el servidor
      $url = "/uploads"; // Esto es la URL relativa que usarás para acceder a los archivos desde la web
      
      // ESTE CÓDIGO ES PARA RUTAS EN EL SERVIDOR DIRECTAMENTE DE ENTORNO INTERNO - (omitir)
      // $server = "/var/www/vhosts/example.com/demo.example.com/uploads";
      // $url = "https://demo.example.com/uploads";
      
      // Lineas de código para despliegue en Compliance Hub Y DIRECTAMENTE EN EL SERVER DE Compliance Hub (opcional)-----
      // $server = "/var/www/vhosts/example.net/demo.example.com/uploads";
      // $url = "https://demo.example.com/uploads";
      
      $folders = array(
        // Sección para subir la Fotografía del Usuario (photo_user -> tabla: users)
        "ord.imguser" => array(
          "folder" => "/users/",
          "url" => "/users/"
        ),
      );
      
      if(isset($folders[$folder])){
        $response['server'] = $server.$folders[$folder]['folder'];
        $response['url'] = $url.$folders[$folder]['url'];
        if(!file_exists($response['server'])){
          mkdir($response['server'], 0777, true);
        }
      }
      else {
        $response = null;
      }
      return $response;
    }
    
    public function upload($title, $file, $folder) {
      $response = null;
      if ($folder = $this->getFolder($folder)) {
        $flag = false;
        $filename = null;
        
        if (!empty($file['name'])) {
          // ESTE CÓDIGO ES PARA SUSTITUIR EL NOMBRE DE LA IMAGEN CON LA CLAVE DEL EMPLEADO (key_user) y la extensión
          // $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
          // $filename = str_replace(' ', '_', "$title.$ext");
          
          $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
          $filename = str_replace(' ', '_', "{$title}_{$file['name']}");
          
          if (move_uploaded_file($file['tmp_name'], $folder['server'] . $filename)) $flag = true;
        }
        if ($flag) $response = $filename;
      }
      return $response;
    }
    
    public function uploadMultiple($title, $file, $folder) {
      $response = null;
      if ($folder = $this->getFolder($folder)) {
        $flag = false;
        $filename = null;
        if (!empty($file['name'])) {
          $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
          $count = 1;
          do {
            $filename = str_replace(' ', '_', "$title-$count.$ext");
            $count++;
          } while(file_exists($folder['server'] . $filename));
          if (move_uploaded_file($file['tmp_name'], $folder['server'] . $filename)) $flag = true;
        }
        if ($flag) $response = $filename;
      }
      return $response;
    }
    
    public function getFile($title, $folder) {
      $response = null;
      if (!empty($title)) {
        if ($folder = $this->getFolder($folder)) {
          $response['server'] = $folder['server'] . $title;
          $response['url'] = $folder['url']  . $title;
          
          if (!file_exists($response['server'])) {
            $response = null;
          }
        
        }
      }
      return $response;
    }
  }
?>