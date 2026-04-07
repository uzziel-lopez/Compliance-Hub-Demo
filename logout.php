<?php
  // Iniciar la sesión
  session_start();

  // Anular el valor de 'login' en la sesión
  $_SESSION['user']['login'] = NULL;

  // Destruir completamente la sesión
  session_destroy();

  // Redirigir al usuario a la página de inicio de sesión
  //header('location: login.php');
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2; url=login.php"> <!-- Redirige después de 2 segundos -->
    <title>Logout</title>
    <script>
      // Limpiar el localStorage
      localStorage.removeItem('sidebarState'); // Esto elimina solo el estado del menú
      localStorage.clear(); // Si quieres limpiar todo el localStorage, puedes usar esto

      // Redirigir automáticamente al login
      window.location.href = 'login.php';  // Si no quieres esperar los 2 segundos
    </script>
  </head>
<body>
  <p>Estás siendo redirigido al inicio de sesión...</p>
</body>
</html>