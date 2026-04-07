<?php
class WebController extends Connector
{
  public $mssg_errors;
  public function __construct()
  {
    parent::__construct();
  }

  // Función para el LOGIN DE INICIO DE SESIÓN DE LOS USERS / EMPLEADOS
  // Archivos -> login.php
  public function loginUser($email_user, $password_user)
  {
    // Consulta SQL para seleccionar un usuario con el correo electrónico proporcionado y que esté activo
    $query = "SELECT * FROM users WHERE email_user = ? AND status_user = 1";
    // Parámetros para la consulta preparada, en este caso, solo se necesita el correo electrónico del usuario
    $params = array($email_user);
    // Ejecuta la consulta utilizando el método consult() definido en la clase WebController y guarda el resultado en la variable $user
    $user = $this->consult($query, $params, true);
    // Verifica si se encontró un usuario y si la contraseña proporcionada coincide con la contraseña almacenada en la base de datos (después de haber sido cifrada)
    if ($user && password_verify($password_user, $user['password_user'])) {
      // Si la contraseña coincide, devuelve los datos del usuario encontrado
      return $user;
    } else {
      // Si no se encuentra el usuario o la contraseña no coincide, devuelve false
      return false;
    }
  }

  // Función para MOSTRAR TODOS LOS TIPOS DE USUARIO (Administrador, Empleado, Ventas)
  // Archivos -> backoffice/users/create-user.php , backoffice/users/update-user.php 
  public function getUserTypes()
  {
    // Consulta SQL para seleccionar todos los registros de la tabla 'types'.
    $query = "SELECT * FROM types";
    // Llama al método 'consult' de la clase actual, pasando la consulta como parámetro.
    // Este método ejecutará la consulta y devolverá el resultado.
    return $this->consult($query);
  }

  //--------------------ADMINISTRACIÓN DE ADMINISTRADORES / USERS / EMPLEADOS ------------------
  // Función para crear un nuevo usuario en la base de datos.
  // Archivos -> backoffice/users/create-user.php
  /**
   * Crear usuario con empresa (MODIFICADO)
   */
  public function createUser($data)
  {
    $key = password_hash($data['password_user'], PASSWORD_BCRYPT);
    $query = "INSERT INTO users(
            id_type_user, id_company, company_role, key_user, name_user, rfc_user, 
            phone_user, email_user, password_user, status_user, created_at_user, updated_at_user
        ) VALUES (?,?,?,?,?,?,?,?,?,1,NOW(),NOW())";

    $params = array(
      $data['id_type_user'],
      !empty($data['id_company']) ? $data['id_company'] : null,
      !empty($data['company_role']) ? $data['company_role'] : 'operador',
      $data['key_user'],
      $data['name_user'],
      $data['rfc_user'],
      $data['phone_user'],
      $data['email_user'],
      $key
    );
    return $this->execute($query, $params);
  }

  // Función para actualizar la foto de un usuario en la base de datos.
  // Archivos -> my-profile.php , backoffice/users/create-user.php , backoffice/users/update-user.php 
  public function updatePhotoUser($userId, $files)
  {
    // Consulta SQL para actualizar la foto de usuario en la tabla 'users'.
    $query = "UPDATE users SET photo_user = ? WHERE id_user = ?";
    // Parámetros para la consulta preparada.
    $params = array(
      $files['imguser'], // La imagen del usuario extraída del array de archivos.
      $userId // El ID del usuario cuya foto se actualizará.
    );
    // Ejecuta la consulta preparada con los parámetros proporcionados.
    return $this->execute($query, $params);
  }

  // Función para obtener información de un usuario por su dirección de correo electrónico.
  // Archivos -> my-profile.php , backoffice/users/create-user.php , backoffice/users/update-user.php 
  public function getEmailUser($emailUser)
  {
    // Consulta SQL para seleccionar todos los campos de la tabla 'users' donde el correo electrónico coincida con el proporcionado.
    $query = "SELECT * FROM users WHERE email_user = ? AND status_user = 1";
    // Parámetros para la consulta preparada. Se utiliza un array para evitar inyecciones SQL.
    $params = array($emailUser);
    // Llamada al método 'consult' para ejecutar la consulta y obtener el resultado.
    // Se espera un único resultado, por lo que se establece el parámetro 'true' para indicar que se espera un solo registro.
    return $this->consult($query, $params, true);
  }

  // Función que obtiene la información de un usuario a partir de su número de teléfono.
  // Archivos -> my-profile.php , backoffice/users/create-user.php , backoffice/users/update-user.php 
  public function getPhoneUser($phoneUser)
  {
    // Query para seleccionar todos los datos de la tabla 'users' donde el número de teléfono coincida.
    $query = "SELECT * FROM users WHERE phone_user = ? AND status_user = 1";
    // Parámetros a pasar en la consulta preparada, en este caso solo el número de teléfono. Se utiliza un array para evitar inyecciones SQL.
    $params = array($phoneUser);
    // Llama a la función 'consult' para ejecutar la consulta preparada y obtener los resultados.
    // El tercer parámetro 'true' indica que se espera un único resultado (un solo usuario).
    return $this->consult($query, $params, true);
  }

  // Función para obtener los datos de un usuario por su RFC.
  // Archivos -> my-profile.php , backoffice/users/create-user.php , backoffice/users/update-user.php 
  public function getRFCUser($rfcUser)
  {
    // Consulta SQL para seleccionar todos los datos de la tabla 'users' donde el RFC del usuario sea igual al proporcionado.
    $query = "SELECT * FROM users WHERE rfc_user = ?";
    // Parámetros a ser utilizados en la consulta preparada, con el RFC del usuario. Se utiliza un array para evitar inyecciones SQL.
    $params = array($rfcUser);
    // Llamada a la función consult() para ejecutar la consulta SQL con los parámetros proporcionados y obtener el resultado.
    // Se espera un solo resultado, por eso se establece el tercer parámetro como 'true'.
    return $this->consult($query, $params, true);
  }

  // Función para obtener los usuarios según su estatus.
  // Archivos -> index.php , app/webservice.php
  /**
   * Obtener usuarios con información de empresa (MODIFICADO)
   */
  public function getUsers($status)
  {
    $query = "SELECT u.*, t.name_type, c.name_company, c.rfc_company 
                  FROM users u 
                  JOIN types t ON u.id_type_user = t.id_type 
                  LEFT JOIN companies c ON u.id_company = c.id_company 
                  WHERE u.status_user = ? 
                  ORDER BY u.created_at_user DESC";
    $params = array($status);
    return $this->consult($query, $params);
  }

  // Función para obtener los detalles de un usuario por su ID y clave.
  // Archivos -> index.php , my-profile.php , navbar.php , backoffice/templates/navbar.php , backoffice/users/detail-user.php , backoffice/users/update-user.php
  /**
   * Obtener detalle de usuario con empresa (CORREGIDO)
   * Archivos -> index.php , my-profile.php , navbar.php , backoffice/templates/navbar.php , backoffice/users/detail-user.php , backoffice/users/update-user.php
   */
  public function getDetailUser($idUser, $keyUser)
  {
    $query = "SELECT u.*, t.name_type, c.name_company, c.rfc_company 
                  FROM users u 
                  JOIN types t ON u.id_type_user = t.id_type 
                  LEFT JOIN companies c ON u.id_company = c.id_company 
                  WHERE u.id_user = ? AND u.key_user = ?";
    $params = array($idUser, $keyUser);
    return $this->consult($query, $params, true);
  }

  // Función para actualizar los datos de un usuario.
  // Archivos -> my-profile.php , backoffice/users/update-user.php
  /**
   * Actualizar usuario con empresa (CORREGIDO)
   * Archivos -> my-profile.php , backoffice/users/update-user.php
   */
  public function updateUser($data, $userId)
  {
    if (empty($data['password_user'])) {
      // Sin actualizar contraseña
      $query = "UPDATE users SET 
                name_user = ?, rfc_user = ?, phone_user = ?, email_user = ?, id_type_user = ?, 
                id_company = ?, company_role = ?, status_user = ?, updated_at_user = NOW() 
                WHERE id_user = ?";

      $params = array(
        $data['name_user'],
        $data['rfc_user'],
        $data['phone_user'],
        $data['email_user'],
        $data['id_type_user'],
        !empty($data['id_company']) ? $data['id_company'] : null,
        !empty($data['company_role']) ? $data['company_role'] : 'operador',
        $data['status_user'],
        $userId
      );
    } else {
      // Con actualizar contraseña
      $key = password_hash($data['password_user'], PASSWORD_BCRYPT);

      $query = "UPDATE users SET 
                name_user = ?, rfc_user = ?, phone_user = ?, email_user = ?, id_type_user = ?, 
                id_company = ?, company_role = ?, password_user = ?, status_user = ?, updated_at_user = NOW() 
                WHERE id_user = ?";

      $params = array(
        $data['name_user'],
        $data['rfc_user'],
        $data['phone_user'],
        $data['email_user'],
        $data['id_type_user'],
        !empty($data['id_company']) ? $data['id_company'] : null,
        !empty($data['company_role']) ? $data['company_role'] : 'operador',
        $key,
        $data['status_user'],
        $userId
      );
    }
    return $this->execute($query, $params);
  }
  // Función para ACTUALIZAR UN USUARIO A ESTATUS DE ELIMINADO (status_user -> 3)
  // Archivos -> backoffice/users/users.php
  public function deleteUser($data)
  {
    // Definición de la consulta SQL para actualizar el estado del usuario a eliminado y establecer la fecha de eliminación
    $query = "UPDATE users SET status_user = 3, eliminated_at_user = NOW() WHERE id_user = ? AND key_user = ?";
    // Definición de los parámetros para la consulta SQL
    $params = array(
      $data['idUser'],    // ID del usuario a eliminar
      $data['keyUser'],   // Clave del usuario para verificar su identidad
    );
    // Ejecución de la consulta SQL con los parámetros proporcionados
    return $this->execute($query, $params);
  }

  // Función para crear una nueva carpeta o folder en la base de datos.
  // Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php

  // Función para crear una nueva carpeta o folder en la base de datos.
// Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php
  public function createFolder($data)
  {
    // Comprobamos si se ha seleccionado un ejecutivo de ventas
    $id_customer_folder = !empty($data['id_customer_folder']) ? $data['id_customer_folder'] : 0;

    // Inicializamos las variables para las fechas como null.
    $firstFech = null;
    $secondFech = null;
    $fech_orig_recib_folder = null;

    // Inicializamos las variables para los campos de los checks como null.
    $chk_alta_fact_folder = isset($data['chk_alta_fact_folder']) ? $data['chk_alta_fact_folder'] : null;
    $chk_lib_folder = isset($data['chk_lib_folder']) ? $data['chk_lib_folder'] : null;
    $chk_orig_recib_folder = isset($data['chk_orig_recib_folder']) ? $data['chk_orig_recib_folder'] : null;

    // Verificamos si las fechas están vacías
    if (empty($data['first_fech_folder']) || empty($data['second_fech_folder'])) {
      $firstFech = null;
      $secondFech = null;
    } else {
      $firstFech = $data['first_fech_folder'];
      $secondFech = $data['second_fech_folder'];
    }

    // Verificamos si 'chk_orig_recib_folder' esta vacio para colocar la fecha como NULL
    if (empty($data['chk_orig_recib_folder'])) {
      $fech_orig_recib_folder = null;
    } else {
      $fech_orig_recib_folder = $data['fech_orig_recib_folder'];
    }

    // Determinar el nombre completo y RFC según el tipo de persona
    $name_folder = '';
    $rfc_folder = '';
    $curp_folder = null;

    if (isset($data['tipo_persona'])) {
      switch ($data['tipo_persona']) {
        case 'fisica':
          // Construir nombre completo para persona física
          $name_folder = trim($data['pf_nombre'] . ' ' . $data['pf_apellido_paterno'] . ' ' . $data['pf_apellido_materno']);
          $rfc_folder = $data['pf_rfc'];
          $curp_folder = !empty($data['pf_curp']) ? $data['pf_curp'] : null;
          break;

        case 'moral':
          $name_folder = $data['pm_razon_social'];
          $rfc_folder = $data['pm_rfc'];
          break;

        case 'fideicomiso':
          $name_folder = $data['fid_razon_social'];
          $rfc_folder = $data['fid_rfc'];
          break;
      }
    } else {
      // Mantener compatibilidad con el código anterior
      $name_folder = $data['name_folder'];
      $rfc_folder = isset($data['rfc_folder']) ? $data['rfc_folder'] : null;
      $curp_folder = isset($data['curp_folder']) ? $data['curp_folder'] : null;
    }

    // Construir la consulta SQL dinámicamente según el tipo de persona
    $baseQuery = "INSERT INTO folders (
        id_user_folder, id_customer_folder, company_id, fk_folder, key_folder, 
        name_folder, rfc_folder, curp_folder, address_folder, 
        first_fech_folder, second_fech_folder, 
        chk_alta_fact_folder, chk_lib_folder, chk_orig_recib_folder, fech_orig_recib_folder,
        status_folder, created_at_folder, updated_at_folder, tipo_persona";

    $baseValues = "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW(),?";

    $params = array(
      $data['id_user_folder'],
      $id_customer_folder,
      isset($data['company_id']) ? $data['company_id'] : null,
      $data['fk_folder'],
      $data['key_folder'],
      $name_folder,
      $rfc_folder,
      $curp_folder,
      isset($data['address_folder']) ? $data['address_folder'] : null,
      $firstFech,
      $secondFech,
      $chk_alta_fact_folder,
      $chk_lib_folder,
      $chk_orig_recib_folder,
      $fech_orig_recib_folder,
      isset($data['tipo_persona']) ? $data['tipo_persona'] : null
    );

    // Agregar campos específicos según el tipo de persona
    if (isset($data['tipo_persona'])) {
      switch ($data['tipo_persona']) {
        case 'fisica':
          $baseQuery .= ", pf_nombre, pf_apellido_paterno, pf_apellido_materno, pf_fecha_nacimiento,
                              pf_estado, pf_ciudad, pf_colonia, pf_codigo_postal, pf_calle, 
                              pf_num_exterior, pf_num_interior, pf_telefono, pf_email,
                              pf_tiene_domicilio_extranjero, pf_pais_origen, pf_estado_extranjero,
                              pf_ciudad_extranjero, pf_colonia_extranjero, pf_calle_extranjero,
                              pf_num_exterior_ext, pf_num_interior_ext, pf_codigo_postal_ext";

          $baseValues .= ",?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";

          // Agregar parámetros de persona física
          $params[] = $data['pf_nombre'];
          $params[] = $data['pf_apellido_paterno'];
          $params[] = !empty($data['pf_apellido_materno']) ? $data['pf_apellido_materno'] : null;
          $params[] = !empty($data['pf_fecha_nacimiento']) ? $data['pf_fecha_nacimiento'] : null;
          $params[] = !empty($data['pf_estado']) ? $data['pf_estado'] : null;
          $params[] = !empty($data['pf_ciudad']) ? $data['pf_ciudad'] : null;
          $params[] = !empty($data['pf_colonia']) ? $data['pf_colonia'] : null;
          $params[] = !empty($data['pf_codigo_postal']) ? $data['pf_codigo_postal'] : null;
          $params[] = !empty($data['pf_calle']) ? $data['pf_calle'] : null;
          $params[] = !empty($data['pf_num_exterior']) ? $data['pf_num_exterior'] : null;
          $params[] = !empty($data['pf_num_interior']) ? $data['pf_num_interior'] : null;
          $params[] = !empty($data['pf_telefono']) ? $data['pf_telefono'] : null;
          $params[] = !empty($data['pf_email']) ? $data['pf_email'] : null;
          $params[] = isset($data['pf_tiene_domicilio_extranjero']) ? 1 : 0;
          $params[] = !empty($data['pf_pais_origen']) ? $data['pf_pais_origen'] : null;
          $params[] = !empty($data['pf_estado_extranjero']) ? $data['pf_estado_extranjero'] : null;
          $params[] = !empty($data['pf_ciudad_extranjero']) ? $data['pf_ciudad_extranjero'] : null;
          $params[] = !empty($data['pf_colonia_extranjero']) ? $data['pf_colonia_extranjero'] : null;
          $params[] = !empty($data['pf_calle_extranjero']) ? $data['pf_calle_extranjero'] : null;
          $params[] = !empty($data['pf_num_exterior_ext']) ? $data['pf_num_exterior_ext'] : null;
          $params[] = !empty($data['pf_num_interior_ext']) ? $data['pf_num_interior_ext'] : null;
          $params[] = !empty($data['pf_codigo_postal_ext']) ? $data['pf_codigo_postal_ext'] : null;
          break;

        case 'moral':
          $baseQuery .= ", pm_razon_social, pm_fecha_constitucion, 
                              pm_apoderado_nombre, pm_apoderado_paterno, pm_apoderado_materno,
                              pm_apoderado_fecha_nacimiento, pm_apoderado_rfc, pm_apoderado_curp,
                              pm_estado, pm_ciudad, pm_colonia, pm_codigo_postal, pm_calle,
                              pm_num_exterior, pm_num_interior, pm_telefono, pm_email,
                              pm_tiene_domicilio_extranjero, pm_pais_origen, pm_estado_extranjero,
                              pm_ciudad_extranjero, pm_colonia_extranjero, pm_calle_extranjero,
                              pm_num_exterior_ext, pm_num_interior_ext, pm_codigo_postal_ext";

          $baseValues .= ",?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";

          // Agregar parámetros de persona moral
          $params[] = $data['pm_razon_social'];
          $params[] = !empty($data['pm_fecha_constitucion']) ? $data['pm_fecha_constitucion'] : null;
          $params[] = !empty($data['pm_apoderado_nombre']) ? $data['pm_apoderado_nombre'] : null;
          $params[] = !empty($data['pm_apoderado_paterno']) ? $data['pm_apoderado_paterno'] : null;
          $params[] = !empty($data['pm_apoderado_materno']) ? $data['pm_apoderado_materno'] : null;
          $params[] = !empty($data['pm_apoderado_fecha_nacimiento']) ? $data['pm_apoderado_fecha_nacimiento'] : null;
          $params[] = !empty($data['pm_apoderado_rfc']) ? $data['pm_apoderado_rfc'] : null;
          $params[] = !empty($data['pm_apoderado_curp']) ? $data['pm_apoderado_curp'] : null;
          $params[] = !empty($data['pm_estado']) ? $data['pm_estado'] : null;
          $params[] = !empty($data['pm_ciudad']) ? $data['pm_ciudad'] : null;
          $params[] = !empty($data['pm_colonia']) ? $data['pm_colonia'] : null;
          $params[] = !empty($data['pm_codigo_postal']) ? $data['pm_codigo_postal'] : null;
          $params[] = !empty($data['pm_calle']) ? $data['pm_calle'] : null;
          $params[] = !empty($data['pm_num_exterior']) ? $data['pm_num_exterior'] : null;
          $params[] = !empty($data['pm_num_interior']) ? $data['pm_num_interior'] : null;
          $params[] = !empty($data['pm_telefono']) ? $data['pm_telefono'] : null;
          $params[] = !empty($data['pm_email']) ? $data['pm_email'] : null;
          $params[] = isset($data['pm_tiene_domicilio_extranjero']) ? 1 : 0;
          $params[] = !empty($data['pm_pais_origen']) ? $data['pm_pais_origen'] : null;
          $params[] = !empty($data['pm_estado_extranjero']) ? $data['pm_estado_extranjero'] : null;
          $params[] = !empty($data['pm_ciudad_extranjero']) ? $data['pm_ciudad_extranjero'] : null;
          $params[] = !empty($data['pm_colonia_extranjero']) ? $data['pm_colonia_extranjero'] : null;
          $params[] = !empty($data['pm_calle_extranjero']) ? $data['pm_calle_extranjero'] : null;
          $params[] = !empty($data['pm_num_exterior_ext']) ? $data['pm_num_exterior_ext'] : null;
          $params[] = !empty($data['pm_num_interior_ext']) ? $data['pm_num_interior_ext'] : null;
          $params[] = !empty($data['pm_codigo_postal_ext']) ? $data['pm_codigo_postal_ext'] : null;
          break;

        case 'fideicomiso':
          $baseQuery .= ", fid_razon_social, fid_numero_referencia,
                              fid_apoderado_nombre, fid_apoderado_paterno, fid_apoderado_materno,
                              fid_apoderado_fecha_nacimiento, fid_apoderado_rfc, fid_apoderado_curp,
                              fid_estado, fid_ciudad, fid_colonia, fid_codigo_postal, fid_calle,
                              fid_num_exterior, fid_num_interior, fid_telefono, fid_email,
                              fid_tiene_domicilio_extranjero, fid_pais_origen, fid_estado_extranjero,
                              fid_ciudad_extranjero, fid_colonia_extranjero, fid_calle_extranjero,
                              fid_num_exterior_ext, fid_num_interior_ext, fid_codigo_postal_ext";

          $baseValues .= ",?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?";

          // Agregar parámetros de fideicomiso
          $params[] = $data['fid_razon_social'];
          $params[] = !empty($data['fid_numero_referencia']) ? $data['fid_numero_referencia'] : null;
          $params[] = !empty($data['fid_apoderado_nombre']) ? $data['fid_apoderado_nombre'] : null;
          $params[] = !empty($data['fid_apoderado_paterno']) ? $data['fid_apoderado_paterno'] : null;
          $params[] = !empty($data['fid_apoderado_materno']) ? $data['fid_apoderado_materno'] : null;
          $params[] = !empty($data['fid_apoderado_fecha_nacimiento']) ? $data['fid_apoderado_fecha_nacimiento'] : null;
          $params[] = !empty($data['fid_apoderado_rfc']) ? $data['fid_apoderado_rfc'] : null;
          $params[] = !empty($data['fid_apoderado_curp']) ? $data['fid_apoderado_curp'] : null;
          $params[] = !empty($data['fid_estado']) ? $data['fid_estado'] : null;
          $params[] = !empty($data['fid_ciudad']) ? $data['fid_ciudad'] : null;
          $params[] = !empty($data['fid_colonia']) ? $data['fid_colonia'] : null;
          $params[] = !empty($data['fid_codigo_postal']) ? $data['fid_codigo_postal'] : null;
          $params[] = !empty($data['fid_calle']) ? $data['fid_calle'] : null;
          $params[] = !empty($data['fid_num_exterior']) ? $data['fid_num_exterior'] : null;
          $params[] = !empty($data['fid_num_interior']) ? $data['fid_num_interior'] : null;
          $params[] = !empty($data['fid_telefono']) ? $data['fid_telefono'] : null;
          $params[] = !empty($data['fid_email']) ? $data['fid_email'] : null;
          $params[] = isset($data['fid_tiene_domicilio_extranjero']) ? 1 : 0;
          $params[] = !empty($data['fid_pais_origen']) ? $data['fid_pais_origen'] : null;
          $params[] = !empty($data['fid_estado_extranjero']) ? $data['fid_estado_extranjero'] : null;
          $params[] = !empty($data['fid_ciudad_extranjero']) ? $data['fid_ciudad_extranjero'] : null;
          $params[] = !empty($data['fid_colonia_extranjero']) ? $data['fid_colonia_extranjero'] : null;
          $params[] = !empty($data['fid_calle_extranjero']) ? $data['fid_calle_extranjero'] : null;
          $params[] = !empty($data['fid_num_exterior_ext']) ? $data['fid_num_exterior_ext'] : null;
          $params[] = !empty($data['fid_num_interior_ext']) ? $data['fid_num_interior_ext'] : null;
          $params[] = !empty($data['fid_codigo_postal_ext']) ? $data['fid_codigo_postal_ext'] : null;
          break;
      }
    }

    // Cerrar la consulta
    $query = $baseQuery . ") " . $baseValues . ")";

    // Ejecutar la consulta
    return $this->execute($query, $params);
  }





  // Método auxiliar para construir la dirección completa
  private function construirDireccion($data, $prefijo)
  {
    $direccion = '';

    if (!empty($data[$prefijo . '_calle'])) {
      $direccion .= $data[$prefijo . '_calle'];
    }

    if (!empty($data[$prefijo . '_num_exterior'])) {
      $direccion .= ' #' . $data[$prefijo . '_num_exterior'];
    }

    if (!empty($data[$prefijo . '_num_interior'])) {
      $direccion .= ' Int. ' . $data[$prefijo . '_num_interior'];
    }

    if (!empty($data[$prefijo . '_colonia'])) {
      $direccion .= ', Col. ' . $data[$prefijo . '_colonia'];
    }

    if (!empty($data[$prefijo . '_ciudad'])) {
      $direccion .= ', ' . $data[$prefijo . '_ciudad'];
    }

    if (!empty($data[$prefijo . '_estado'])) {
      $direccion .= ', ' . $data[$prefijo . '_estado'];
    }

    if (!empty($data[$prefijo . '_codigo_postal'])) {
      $direccion .= ' C.P. ' . $data[$prefijo . '_codigo_postal'];
    }

    return trim($direccion);
  }

  // Función para obtener todas las carpetas según el estatus especificado.
  // Archivos -> backoffice/folders/folders.php
  public function getFolders($statusFolder)
  {
    // Construir la consulta SQL para seleccionar todas las carpetas con el status y ordenarlas por fecha de vencimiento en orden DESCENDENTE.
    $query = "SELECT *, DATEDIFF(NOW(), second_fech_folder) as dias FROM folders WHERE status_folder = ? AND fk_folder = 0 ORDER BY second_fech_folder DESC";
    // Establecer los parámetros de la consulta SQL.
    $params = array($statusFolder);
    // Ejecutar la consulta SQL y devolver los resultados.
    return $this->consult($query, $params);
  }

  // Función para obtener todas las carpetas según el estatus especificado.
  // Archivos -> index.php
  public function getAllFolders($statusFolder)
  {
    // Construir la consulta SQL para seleccionar todas las carpetas con el status enviado al controlador
    $query = "SELECT * FROM folders WHERE status_folder = ? AND fk_folder = 0";
    // Establecer los parámetros de la consulta SQL.
    $params = array($statusFolder);
    // Ejecutar la consulta SQL y devolver los resultados.
    return $this->consult($query, $params);
  }

  // Función para actualizar la información de una carpeta (nombre y plazo de vencimiento - fecha inicial y fecha final)
  // Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php


  // Función para actualizar la información completa de una carpeta
// Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php
  // Función para actualizar la información completa de una carpeta
// Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php

public function updateNameFolder($data)
{
    // Debug: Verificar qué datos llegan
    error_log("updateNameFolder called with data: " . print_r($data, true));

    // Comprobamos si se ha seleccionado un ejecutivo de ventas
    $id_customer_folder = !empty($data['id_customer_folder']) ? $data['id_customer_folder'] : 0;

    // Inicializamos las variables para las fechas como null.
    $firstFech = null;
    $secondFech = null;
    $fech_orig_recib_folder = null;

    // Inicializamos las variables para los campos de los checks como null.
    $chk_alta_fact_folder = isset($data['chk_alta_fact_folder']) ? $data['chk_alta_fact_folder'] : null;
    $chk_lib_folder = isset($data['chk_lib_folder']) ? $data['chk_lib_folder'] : null;
    $chk_orig_recib_folder = isset($data['chk_orig_recib_folder']) ? $data['chk_orig_recib_folder'] : null;

    // Verificamos si 'first_fech_folder' o 'second_fech_folder' están vacíos.
    if (empty($data['first_fech_folder']) || empty($data['second_fech_folder'])) {
        $firstFech = null;
        $secondFech = null;
    } else {
        $firstFech = $data['first_fech_folder'];
        $secondFech = $data['second_fech_folder'];
    }

    // Verificamos si 'chk_orig_recib_folder' esta vacio para colocar la fecha como NULL
    if (empty($data['chk_orig_recib_folder'])) {
        $fech_orig_recib_folder = null;
    } else {
        $fech_orig_recib_folder = $data['fech_orig_recib_folder'];
    }

    // ========== NUEVOS CAMPOS PARA PERSONA FÍSICA ==========
    $pf_nombre = isset($data['pf_nombre']) ? $data['pf_nombre'] : null;
    $pf_apellido_paterno = isset($data['pf_apellido_paterno']) ? $data['pf_apellido_paterno'] : null;
    $pf_apellido_materno = isset($data['pf_apellido_materno']) ? $data['pf_apellido_materno'] : null;
    $pf_fecha_nacimiento = !empty($data['pf_fecha_nacimiento']) ? $data['pf_fecha_nacimiento'] : null;
    $pf_estado = isset($data['pf_estado']) ? $data['pf_estado'] : null;
    $pf_ciudad = isset($data['pf_ciudad']) ? $data['pf_ciudad'] : null;
    $pf_colonia = isset($data['pf_colonia']) ? $data['pf_colonia'] : null;
    $pf_codigo_postal = isset($data['pf_codigo_postal']) ? $data['pf_codigo_postal'] : null;
    $pf_calle = isset($data['pf_calle']) ? $data['pf_calle'] : null;
    $pf_num_exterior = isset($data['pf_num_exterior']) ? $data['pf_num_exterior'] : null;
    $pf_num_interior = isset($data['pf_num_interior']) ? $data['pf_num_interior'] : null;
    $pf_telefono = isset($data['pf_telefono']) ? $data['pf_telefono'] : null;
    $pf_email = isset($data['pf_email']) ? $data['pf_email'] : null;

    // Domicilio extranjero persona física
    $pf_tiene_domicilio_extranjero = isset($data['pf_tiene_domicilio_extranjero']) ? 1 : 0;
    $pf_pais_origen = isset($data['pf_pais_origen']) ? $data['pf_pais_origen'] : null;
    $pf_estado_extranjero = isset($data['pf_estado_extranjero']) ? $data['pf_estado_extranjero'] : null;
    $pf_ciudad_extranjero = isset($data['pf_ciudad_extranjero']) ? $data['pf_ciudad_extranjero'] : null;
    $pf_colonia_extranjero = isset($data['pf_colonia_extranjero']) ? $data['pf_colonia_extranjero'] : null;
    $pf_calle_extranjero = isset($data['pf_calle_extranjero']) ? $data['pf_calle_extranjero'] : null;
    $pf_num_exterior_ext = isset($data['pf_num_exterior_ext']) ? $data['pf_num_exterior_ext'] : null;
    $pf_num_interior_ext = isset($data['pf_num_interior_ext']) ? $data['pf_num_interior_ext'] : null;
    $pf_codigo_postal_ext = isset($data['pf_codigo_postal_ext']) ? $data['pf_codigo_postal_ext'] : null;

    // ========== NUEVOS CAMPOS PARA PERSONA MORAL ==========
    $pm_razon_social = isset($data['pm_razon_social']) ? $data['pm_razon_social'] : null;
    $pm_fecha_constitucion = !empty($data['pm_fecha_constitucion']) ? $data['pm_fecha_constitucion'] : null;
    $pm_apoderado_nombre = isset($data['pm_apoderado_nombre']) ? $data['pm_apoderado_nombre'] : null;
    $pm_apoderado_paterno = isset($data['pm_apoderado_paterno']) ? $data['pm_apoderado_paterno'] : null;
    $pm_apoderado_materno = isset($data['pm_apoderado_materno']) ? $data['pm_apoderado_materno'] : null;
    $pm_apoderado_fecha_nacimiento = !empty($data['pm_apoderado_fecha_nacimiento']) ? $data['pm_apoderado_fecha_nacimiento'] : null;
    $pm_apoderado_rfc = isset($data['pm_apoderado_rfc']) ? $data['pm_apoderado_rfc'] : null;
    $pm_apoderado_curp = isset($data['pm_apoderado_curp']) ? $data['pm_apoderado_curp'] : null;
    $pm_estado = isset($data['pm_estado']) ? $data['pm_estado'] : null;
    $pm_ciudad = isset($data['pm_ciudad']) ? $data['pm_ciudad'] : null;
    $pm_colonia = isset($data['pm_colonia']) ? $data['pm_colonia'] : null;
    $pm_codigo_postal = isset($data['pm_codigo_postal']) ? $data['pm_codigo_postal'] : null;
    $pm_calle = isset($data['pm_calle']) ? $data['pm_calle'] : null;
    $pm_num_exterior = isset($data['pm_num_exterior']) ? $data['pm_num_exterior'] : null;
    $pm_num_interior = isset($data['pm_num_interior']) ? $data['pm_num_interior'] : null;
    $pm_telefono = isset($data['pm_telefono']) ? $data['pm_telefono'] : null;
    $pm_email = isset($data['pm_email']) ? $data['pm_email'] : null;

    // Domicilio extranjero persona moral
    $pm_tiene_domicilio_extranjero = isset($data['pm_tiene_domicilio_extranjero']) ? 1 : 0;
    $pm_pais_origen = isset($data['pm_pais_origen']) ? $data['pm_pais_origen'] : null;
    $pm_estado_extranjero = isset($data['pm_estado_extranjero']) ? $data['pm_estado_extranjero'] : null;
    $pm_ciudad_extranjero = isset($data['pm_ciudad_extranjero']) ? $data['pm_ciudad_extranjero'] : null;
    $pm_colonia_extranjero = isset($data['pm_colonia_extranjero']) ? $data['pm_colonia_extranjero'] : null;
    $pm_calle_extranjero = isset($data['pm_calle_extranjero']) ? $data['pm_calle_extranjero'] : null;
    $pm_num_exterior_ext = isset($data['pm_num_exterior_ext']) ? $data['pm_num_exterior_ext'] : null;
    $pm_num_interior_ext = isset($data['pm_num_interior_ext']) ? $data['pm_num_interior_ext'] : null;
    $pm_codigo_postal_ext = isset($data['pm_codigo_postal_ext']) ? $data['pm_codigo_postal_ext'] : null;

    // ========== NUEVOS CAMPOS PARA FIDEICOMISO ==========
    $fid_razon_social = isset($data['fid_razon_social']) ? $data['fid_razon_social'] : null;
    $fid_numero_referencia = isset($data['fid_numero_referencia']) ? $data['fid_numero_referencia'] : null;
    $fid_apoderado_nombre = isset($data['fid_apoderado_nombre']) ? $data['fid_apoderado_nombre'] : null;
    $fid_apoderado_paterno = isset($data['fid_apoderado_paterno']) ? $data['fid_apoderado_paterno'] : null;
    $fid_apoderado_materno = isset($data['fid_apoderado_materno']) ? $data['fid_apoderado_materno'] : null;
    $fid_apoderado_fecha_nacimiento = !empty($data['fid_apoderado_fecha_nacimiento']) ? $data['fid_apoderado_fecha_nacimiento'] : null;
    $fid_apoderado_rfc = isset($data['fid_apoderado_rfc']) ? $data['fid_apoderado_rfc'] : null;
    $fid_apoderado_curp = isset($data['fid_apoderado_curp']) ? $data['fid_apoderado_curp'] : null;
    $fid_estado = isset($data['fid_estado']) ? $data['fid_estado'] : null;
    $fid_ciudad = isset($data['fid_ciudad']) ? $data['fid_ciudad'] : null;
    $fid_colonia = isset($data['fid_colonia']) ? $data['fid_colonia'] : null;
    $fid_codigo_postal = isset($data['fid_codigo_postal']) ? $data['fid_codigo_postal'] : null;
    $fid_calle = isset($data['fid_calle']) ? $data['fid_calle'] : null;
    $fid_num_exterior = isset($data['fid_num_exterior']) ? $data['fid_num_exterior'] : null;
    $fid_num_interior = isset($data['fid_num_interior']) ? $data['fid_num_interior'] : null;
    $fid_telefono = isset($data['fid_telefono']) ? $data['fid_telefono'] : null;
    $fid_email = isset($data['fid_email']) ? $data['fid_email'] : null;

    // Domicilio extranjero fideicomiso
    $fid_tiene_domicilio_extranjero = isset($data['fid_tiene_domicilio_extranjero']) ? 1 : 0;
    $fid_pais_origen = isset($data['fid_pais_origen']) ? $data['fid_pais_origen'] : null;
    $fid_estado_extranjero = isset($data['fid_estado_extranjero']) ? $data['fid_estado_extranjero'] : null;
    $fid_ciudad_extranjero = isset($data['fid_ciudad_extranjero']) ? $data['fid_ciudad_extranjero'] : null;
    $fid_colonia_extranjero = isset($data['fid_colonia_extranjero']) ? $data['fid_colonia_extranjero'] : null;
    $fid_calle_extranjero = isset($data['fid_calle_extranjero']) ? $data['fid_calle_extranjero'] : null;
    $fid_num_exterior_ext = isset($data['fid_num_exterior_ext']) ? $data['fid_num_exterior_ext'] : null;
    $fid_num_interior_ext = isset($data['fid_num_interior_ext']) ? $data['fid_num_interior_ext'] : null;
    $fid_codigo_postal_ext = isset($data['fid_codigo_postal_ext']) ? $data['fid_codigo_postal_ext'] : null;

    // ========== CAMPOS GENERALES ==========
    $tipo_persona = isset($data['tipo_persona']) ? $data['tipo_persona'] : null;
    $curp_folder = isset($data['curp_folder']) ? $data['curp_folder'] : null;

    // ===== AQUÍ ESTÁ EL CAMBIO PRINCIPAL - RFC SEGÚN TIPO DE PERSONA =====
    $rfc_folder = null;
    
    // Obtener el RFC del campo correcto según el tipo de persona
    if ($tipo_persona === 'fisica') {
        $rfc_folder = isset($data['pf_rfc']) ? $data['pf_rfc'] : null;
    } elseif ($tipo_persona === 'moral') {
        $rfc_folder = isset($data['pm_rfc']) ? $data['pm_rfc'] : null;
    } elseif ($tipo_persona === 'fideicomiso') {
        $rfc_folder = isset($data['fid_rfc']) ? $data['fid_rfc'] : null;
    }

    error_log("RFC DEBUG - Tipo: $tipo_persona, RFC: $rfc_folder");

    // GENERAR name_folder dinámicamente según tipo de persona
    $name_folder = '';
    switch ($tipo_persona) {
        case 'fisica':
            $name_folder = trim($pf_nombre . ' ' . $pf_apellido_paterno . ' ' . $pf_apellido_materno);
            break;
        case 'moral':
            $name_folder = $pm_razon_social ?: 'Empresa';
            break;
        case 'fideicomiso':
            $name_folder = $fid_razon_social ?: 'Fideicomiso';
            break;
        default:
            $name_folder = isset($data['name_folder']) ? $data['name_folder'] : 'Cliente';
    }

    // Definición de la consulta SQL para actualizar la información completa de la carpeta
    $query = "UPDATE folders SET 
                id_customer_folder = ?, 
                name_folder = ?, 
                tipo_persona = ?,
                rfc_folder = ?,
                curp_folder = ?,
                first_fech_folder = ?, 
                second_fech_folder = ?, 
                chk_alta_fact_folder = ?, 
                chk_lib_folder = ?, 
                chk_orig_recib_folder = ?, 
                fech_orig_recib_folder = ?,
                
                -- Campos Persona Física
                pf_nombre = ?,
                pf_apellido_paterno = ?,
                pf_apellido_materno = ?,
                pf_fecha_nacimiento = ?,
                pf_estado = ?,
                pf_ciudad = ?,
                pf_colonia = ?,
                pf_codigo_postal = ?,
                pf_calle = ?,
                pf_num_exterior = ?,
                pf_num_interior = ?,
                pf_telefono = ?,
                pf_email = ?,
                pf_tiene_domicilio_extranjero = ?,
                pf_pais_origen = ?,
                pf_estado_extranjero = ?,
                pf_ciudad_extranjero = ?,
                pf_colonia_extranjero = ?,
                pf_calle_extranjero = ?,
                pf_num_exterior_ext = ?,
                pf_num_interior_ext = ?,
                pf_codigo_postal_ext = ?,
                
                -- Campos Persona Moral
                pm_razon_social = ?,
                pm_fecha_constitucion = ?,
                pm_apoderado_nombre = ?,
                pm_apoderado_paterno = ?,
                pm_apoderado_materno = ?,
                pm_apoderado_fecha_nacimiento = ?,
                pm_apoderado_rfc = ?,
                pm_apoderado_curp = ?,
                pm_estado = ?,
                pm_ciudad = ?,
                pm_colonia = ?,
                pm_codigo_postal = ?,
                pm_calle = ?,
                pm_num_exterior = ?,
                pm_num_interior = ?,
                pm_telefono = ?,
                pm_email = ?,
                pm_tiene_domicilio_extranjero = ?,
                pm_pais_origen = ?,
                pm_estado_extranjero = ?,
                pm_ciudad_extranjero = ?,
                pm_colonia_extranjero = ?,
                pm_calle_extranjero = ?,
                pm_num_exterior_ext = ?,
                pm_num_interior_ext = ?,
                pm_codigo_postal_ext = ?,
                
                -- Campos Fideicomiso
                fid_razon_social = ?,
                fid_numero_referencia = ?,
                fid_apoderado_nombre = ?,
                fid_apoderado_paterno = ?,
                fid_apoderado_materno = ?,
                fid_apoderado_fecha_nacimiento = ?,
                fid_apoderado_rfc = ?,
                fid_apoderado_curp = ?,
                fid_estado = ?,
                fid_ciudad = ?,
                fid_colonia = ?,
                fid_codigo_postal = ?,
                fid_calle = ?,
                fid_num_exterior = ?,
                fid_num_interior = ?,
                fid_telefono = ?,
                fid_email = ?,
                fid_tiene_domicilio_extranjero = ?,
                fid_pais_origen = ?,
                fid_estado_extranjero = ?,
                fid_ciudad_extranjero = ?,
                fid_colonia_extranjero = ?,
                fid_calle_extranjero = ?,
                fid_num_exterior_ext = ?,
                fid_num_interior_ext = ?,
                fid_codigo_postal_ext = ?,
                
                updated_at_folder = NOW() 
              WHERE id_folder = ?";

    // Definición de los parámetros que serán insertados en la sentencia SQL
    $params = array(
        // Campos originales
        $id_customer_folder,
        $name_folder,
        $tipo_persona,
        $rfc_folder,  // ← AQUÍ SE USA EL RFC CORRECTO
        $curp_folder,
        $firstFech,
        $secondFech,
        $chk_alta_fact_folder,
        $chk_lib_folder,
        $chk_orig_recib_folder,
        $fech_orig_recib_folder,

        // Campos Persona Física
        $pf_nombre,
        $pf_apellido_paterno,
        $pf_apellido_materno,
        $pf_fecha_nacimiento,
        $pf_estado,
        $pf_ciudad,
        $pf_colonia,
        $pf_codigo_postal,
        $pf_calle,
        $pf_num_exterior,
        $pf_num_interior,
        $pf_telefono,
        $pf_email,
        $pf_tiene_domicilio_extranjero,
        $pf_pais_origen,
        $pf_estado_extranjero,
        $pf_ciudad_extranjero,
        $pf_colonia_extranjero,
        $pf_calle_extranjero,
        $pf_num_exterior_ext,
        $pf_num_interior_ext,
        $pf_codigo_postal_ext,

        // Campos Persona Moral
        $pm_razon_social,
        $pm_fecha_constitucion,
        $pm_apoderado_nombre,
        $pm_apoderado_paterno,
        $pm_apoderado_materno,
        $pm_apoderado_fecha_nacimiento,
        $pm_apoderado_rfc,
        $pm_apoderado_curp,
        $pm_estado,
        $pm_ciudad,
        $pm_colonia,
        $pm_codigo_postal,
        $pm_calle,
        $pm_num_exterior,
        $pm_num_interior,
        $pm_telefono,
        $pm_email,
        $pm_tiene_domicilio_extranjero,
        $pm_pais_origen,
        $pm_estado_extranjero,
        $pm_ciudad_extranjero,
        $pm_colonia_extranjero,
        $pm_calle_extranjero,
        $pm_num_exterior_ext,
        $pm_num_interior_ext,
        $pm_codigo_postal_ext,

        // Campos Fideicomiso
        $fid_razon_social,
        $fid_numero_referencia,
        $fid_apoderado_nombre,
        $fid_apoderado_paterno,
        $fid_apoderado_materno,
        $fid_apoderado_fecha_nacimiento,
        $fid_apoderado_rfc,
        $fid_apoderado_curp,
        $fid_estado,
        $fid_ciudad,
        $fid_colonia,
        $fid_codigo_postal,
        $fid_calle,
        $fid_num_exterior,
        $fid_num_interior,
        $fid_telefono,
        $fid_email,
        $fid_tiene_domicilio_extranjero,
        $fid_pais_origen,
        $fid_estado_extranjero,
        $fid_ciudad_extranjero,
        $fid_colonia_extranjero,
        $fid_calle_extranjero,
        $fid_num_exterior_ext,
        $fid_num_interior_ext,
        $fid_codigo_postal_ext,

        // ID de la carpeta que se actualizará (debe ir al final)
        $data['id_folder']
    );

    // Debug: Mostrar query y parámetros antes de ejecutar
    error_log("Query: " . $query);
    error_log("Params count: " . count($params));
    error_log("Generated name_folder: " . $name_folder);

    // Ejecución de la consulta SQL con los parámetros proporcionados
    return $this->execute($query, $params);
}


  // Función para ACTUALIZAR UNA CARPETA A ESTATUS DE ELIMINADO
  // Archivos -> backoffice/folders/all_folders.php , backoffice/folders/folders.php , backoffice/folders/subfolder.php
  public function deleteFolder($data)
  {
    // Preparar la consulta SQL para actualizar el estado de la carpeta a eliminado (status_folder = 3)
    // y registrar el tiempo de eliminación (eliminated_at_folder = NOW()) donde el id de la carpeta sea igual al proporcionado.
    $query = "UPDATE folders SET status_folder = 3, eliminated_at_folder = NOW() WHERE id_folder = ?";
    // Preparar los parámetros de la consulta, en este caso, solo se espera el id de la carpeta a eliminar.
    $params = array(
      $data['idFolder'], // Se añade el id de la carpeta al array de parámetros.
    );
    // Ejecutar la consulta con los parámetros preparados y devolver el resultado.
    return $this->execute($query, $params);
  }

  /**
   * * Función para obtener y mostrar los detalles generales de una carpeta.
   * * Esta función consulta la base de datos para recuperar los detalles de una carpeta específica
   * * basada en su ID, clave y status.
   * */
  // Archivos -> backoffice/folders/subfolder.php
  public function getDetailFolder($idFolder, $keyFolder, $statusFolder)
  {
    // Consulta SQL para seleccionar los detalles de la carpeta con el ID, clave y status especificados.
    $query = "SELECT * FROM folders WHERE id_folder = ? AND key_folder = ? AND status_folder = ?";
    // Parámetros para la consulta preparada.
    $params = array($idFolder, $keyFolder, $statusFolder);
    // Llama a la función consult() para ejecutar la consulta y recuperar los detalles de la carpeta.
    return $this->consult($query, $params, true);
  }

  /**
   * * Función para obtener y mostrar los detalles de una carpeta padre por medio de una carpeta hijo.
   * * Esta función consulta la base de datos para recuperar los detalles de una carpeta padre se requiere la key para poder navegar de regreso entre subcarpetas
   * * basada en su ID (fk_folder) llama la subcarpeta a traves del id foraneo a los detalles de la carpeta padre
   * */
  // Archivos -> backoffice/folders/subfolder.php
  public function getKeyFolder($idFolder)
  {
    // Consulta SQL para seleccionar los detalles de la carpeta con el ID de la llave foranea
    $query = "SELECT * FROM folders WHERE id_folder = ?";
    // Parámetros para la consulta preparada.
    $params = array($idFolder);
    // Llama a la función consult() para ejecutar la consulta y recuperar los detalles de la carpeta en este caso solo requerimos de la key.
    return $this->consult($query, $params, true);
  }

  /**
   * * Función para obtener y mostrar los detalles de una carpeta.
   * * Esta función consulta la base de datos para recuperar los detalles de una carpeta específica
   * * basada en su ID y status.
   * * SE USA EN LA SECCIÓN DE FOLDERS
   * */
  // Archivos -> app/webservice.php
  public function getFolderDetail($idFolder, $statusFolder)
  {
    // Consulta SQL para seleccionar los detalles de la carpeta con el ID y status especificados.
    $query = "SELECT * FROM folders WHERE id_folder = ? AND status_folder = ?";
    // Parámetros para la consulta preparada.
    $params = array($idFolder, $statusFolder);
    // Llama a la función consult() para ejecutar la consulta y recuperar los detalles de la carpeta.
    return $this->consult($query, $params, true);
  }

  // Función para mostrar TODAS LAS SUBCARPETAS de una carpeta principal
  // Archivos -> backoffice/folders/subfolder.php
  public function getSubFolders($idFolder)
  {
    // Consulta SQL para obtener subcarpetas junto con sus respectivas carpetas principales
    $query = "SELECT 
        DATEDIFF(NOW(), FOL2.second_fech_folder) as dias,
        -- Campos de la carpeta principal
        FOL1.id_folder AS 'id_folder',
        FOL1.fk_folder AS 'fk_folder',
        FOL1.key_folder AS 'key_folder',
        FOL1.name_folder AS 'name_folder',
        FOL1.first_fech_folder AS 'first_fech_folder',
        FOL1.second_fech_folder AS 'second_fech_folder',
        FOL1.chk_alta_fact_folder AS 'chk_alta_fact_folder',
        FOL1.chk_lib_folder AS 'chk_lib_folder',
        FOL1.chk_orig_recib_folder AS 'chk_orig_recib_folder',
        FOL1.fech_orig_recib_folder AS 'fech_orig_recib_folder',
        FOL1.status_folder AS 'status_folder',
        FOL1.created_at_folder AS 'created_at_folder',
        FOL1.updated_at_folder AS 'updated_at_folder',
        FOL1.eliminated_at_folder AS 'eliminated_at_folder',
        -- Campos de la subcarpeta
        FOL2.id_folder AS 'id_sub_folder',
        FOL2.fk_folder AS 'fk_sub_folder',
        FOL2.key_folder AS 'key_sub_folder',
        FOL2.name_folder AS 'name_sub_folder',
        FOL2.first_fech_folder AS 'first_fech_sub_folder',
        FOL2.second_fech_folder AS 'second_fech_sub_folder',
        FOL2.chk_alta_fact_folder AS 'chk_alta_fact_sub_folder',
        FOL2.chk_lib_folder AS 'chk_lib_sub_folder',
        FOL2.chk_orig_recib_folder AS 'chk_orig_recib_sub_folder',
        FOL2.fech_orig_recib_folder AS 'fech_orig_recib_sub_folder',
        FOL2.status_folder AS 'status_sub_folder',
        FOL2.created_at_folder AS 'created_at_sub_folder',
        FOL2.updated_at_folder AS 'updated_at_sub_folder',
        FOL2.eliminated_at_folder AS 'eliminated_at_sub_folder' 
        FROM folders FOL1
        -- Unir la tabla de carpetas consigo misma para obtener las subcarpetas
        JOIN folders FOL2 ON FOL1.id_folder = FOL2.fk_folder
        WHERE FOL1.id_folder = ? -- Filtro para obtener solo las subcarpetas de una carpeta específica
        AND FOL2.status_folder = 1 -- Filtro para obtener solo las subcarpetas activas 
        ORDER BY FOL2.second_fech_folder DESC"; // Ordenar las subcarpetas por fecha de vencimiento descendente

    // Ordenar las subcarpetas por fecha de creación descendente -- Sustituir Código
    // ORDER BY FOL2.created_at_folder DESC";

    $params = array($idFolder); // Parámetros para la consulta (ID de la carpeta principal)
    // Ejecutar la consulta y devolver el resultado
    return $this->consult($query, $params);
  }

  // Función para crear un nuevo documento en la base de datos.
  // Esta función inserta un nuevo registro en la tabla de documentos con los datos proporcionados.
  // Archivos -> backoffice/folders/subfolder.php
  public function createDocument($data)
  {
    // Consulta SQL para insertar un nuevo documento en la base de datos
    $query = "INSERT INTO documents(id_folder_document, id_user_document, key_document, file_name_document, file_extension_document, first_fech_document, second_fech_document, status_document, created_at_document, updated_at_document) VALUES (?,?,?,?,?,?,?,1,NOW(),NOW())";
    // Parámetros para la consulta preparada
    $params = array(
      $data['id_folder_document'],
      $data['id_user_document'],
      $data['key_document'],
      $data['file_name_document'],
      $data['file_extension_document'],
      $data['first_fech_document'],
      $data['second_fech_document'],
    );
    // Ejecutar la consulta y retornar el resultado
    return $this->execute($query, $params);
  }

  // Función para obtener todos los documentos relacionados con una carpeta
  // Esta función recibe el ID de la carpeta y realiza una consulta SQL para recuperar todos los documentos asociados a esa carpeta.
  // Archivos -> backoffice/folders/subfolder.php
  public function getAllDocumentsFolder($idFolder)
  {
    // Consulta SQL para recuperar los documentos asociados a la carpeta especificada
    $query = "SELECT *, DATEDIFF(NOW(), documents.second_fech_document) as dias 
        FROM documents 
        JOIN folders ON documents.id_folder_document = folders.id_folder 
        JOIN users ON documents.id_user_document = users.id_user 
        WHERE folders.id_folder = ? AND documents.status_document = 1 
        ORDER BY documents.file_name_document ASC"; // Ordenamiento de documentos por nombre de archivo en orden Ascendente

    // Ordenamiento de documentos por fecha de creación Descendente -- Sustituir código
    // ORDER BY documents.created_at_document DESC";

    // Parámetros de la consulta
    $params = array($idFolder);
    // Ejecutar la consulta y devolver los resultados
    return $this->consult($query, $params);
  }

  // Función para ACTUALIZAR UN DOCUMENTO A ESTATUS DE ELIMINADO (status_document -> 2)
  // Archivos -> backoffice/documents/documents.php , backoffice/folders/subfolder.php
  public function deleteDocument($data)
  {
    // Construir la consulta SQL para actualizar el documento a estado eliminado
    $query = "UPDATE documents SET status_document = 2, eliminated_at_document = NOW() WHERE id_document = ? AND key_document = ?";
    // Parámetros para la consulta preparada
    $params = array(
      $data['id_document'],   // ID del documento a eliminar
      $data['key_document'],  // Clave única del documento a eliminar
    );
    // Ejecutar la consulta preparada con los parámetros proporcionados
    return $this->execute($query, $params); // Retorna el resultado de la ejecución de la consulta (puede ser verdadero si se realiza correctamente o falso si hay un error)
  }

  // Función para obtener los detalles de un documento por su ID.
  // Archivos -> app/webservice.php
  public function getDetailDocument($idDocument)
  {
    // Consulta SQL para obtener los detalles del documento, la carpeta a la que pertenece y el usuario que lo creó.
    $query = "SELECT * FROM documents 
        JOIN folders ON documents.id_folder_document = folders.id_folder 
        JOIN users ON documents.id_user_document = users.id_user 
        WHERE documents.id_document = ?";

    // Parámetros de la consulta, que en este caso es solo el ID del documento.
    $params = array($idDocument);
    // Llamar a la función consult() para ejecutar la consulta SQL con los parámetros proporcionados.
    // El tercer parámetro true indica que se espera un único resultado de la consulta.
    return $this->consult($query, $params, true);
  }

  /**
   * * Función para actualizar la información de un documento en la base de datos.
   * * Se actualizan los campos first_fech_document, second_fech_document y updated_at_document.
   **/
  // Archivos -> backoffice/documents/documents.php , backoffice/folders/subfolder.php
  public function updateDocument($data)
  {
    // Consulta SQL para actualizar los campos del documento
    $query = "UPDATE documents SET first_fech_document = ?, second_fech_document = ?, updated_at_document = NOW() WHERE id_document = ? AND key_document = ?";
    // Parámetros para la consulta preparada
    $params = array(
      $data['first_fech_document'],   // Primera fecha del documento
      $data['second_fech_document'],  // Segunda fecha del documento
      $data['id_document'],           // ID del documento
      $data['key_document'],          // Clave del documento
    );
    // Ejecutar la consulta y retornar el resultado
    return $this->execute($query, $params);
  }

  // Función para seleccionar todos los documentos y calcular los días desde 'second_fech_document'
  // Archivos -> app/webservice.php
  public function ws_getAllDocuments($fecha1, $fecha2)
  {
    // Construye la consulta SQL base para seleccionar todos los documentos y calcular los días desde 'second_fech_document'
    $query = "SELECT *, DATEDIFF(NOW(), documents.second_fech_document) as dias 
        FROM documents 
        JOIN folders ON documents.id_folder_document = folders.id_folder 
        JOIN users ON documents.id_user_document = users.id_user 
        WHERE documents.status_document = 1";
    // Inicializa el array de parámetros para la consulta preparada
    $params = array();
    // Si ambas fechas no están vacías, añade la condición de rango de fechas a la consulta
    if (!empty($fecha1) && !empty($fecha2)) {
      $query .= " AND documents.created_at_document BETWEEN ? AND ?";
      // Añade las fechas al array de parámetros
      $params[] = $fecha1;
      $params[] = $fecha2;
    }
    // Añade la cláusula ORDER BY para ordenar los resultados por la fecha de creación en orden descendente
    $query .= " ORDER BY documents.created_at_document DESC";
    // Ejecuta la consulta preparada con los parámetros proporcionados y devuelve los resultados
    return $this->consult($query, $params);
  }

  // Función para obtener todos los registros de los documentos con un estatus especifico (1 - activo, 2 - eliminado)
  // Archivos -> index.php
  public function showAllDocuments($statusDocument)
  {
    // Construir la consulta SQL para seleccionar todos los documentos con el status
    $query = "SELECT * FROM documents WHERE status_document = ?";
    // Establecer los parámetros de la consulta SQL.
    $params = array($statusDocument);
    // Ejecutar la consulta SQL y devolver los resultados.
    return $this->consult($query, $params);
  }

  // Método para obtener el número total de notificaciones de documentos
  // Archivos -> app/webservice.php
  public function ws_getIdNotifications()
  {
    // Construir el filtro basado en las notificaciones del usuario actual
    $filter = $this->buildFilter();
    // Construir la consulta SQL para contar el número total de documentos con estado activo (status_document = 1) y que el id de la carpeta donde estan guardados no se haya eliminado
    $totals = "SELECT COUNT(*) AS total FROM documents 
        JOIN folders ON documents.id_folder_document = folders.id_folder 
        $filter 
        AND documents.status_document = 1 
        AND folders.status_folder = 1 ";
    $params = array(); // Array de parámetros para la consulta, actualmente vacío ya que no se necesitan parámetros adicionales
    // Ejecutar la consulta y devolver el resultado
    return $this->consult($totals, $params, true);
  }

  // Método para obtener documentos no visualizados por el usuario y que la carpeta en la que se encuentran no haya sido borrada ni el documento haya sido borrado
  // Archivos -> app/webservice.php
  public function ws_getNotWachDocuments()
  {
    // Construir el filtro basado en las notificaciones del usuario actual
    $filter = $this->buildFilter();
    // Construir la consulta SQL para seleccionar todos los documentos no visualizados (se establece un limite de 100 documentos por consulta), 
    // junto con los datos de las carpetas y usuarios relacionados
    $query = "SELECT * FROM documents 
        JOIN folders ON documents.id_folder_document = folders.id_folder 
        JOIN users ON documents.id_user_document = users.id_user 
        $filter 
        AND documents.status_document = 1 
        AND folders.status_folder = 1 
        ORDER BY documents.created_at_document DESC 
        LIMIT 100";

    $params = array(); // Array de parámetros para la consulta, actualmente vacío ya que no se necesitan parámetros adicionales
    // Ejecutar la consulta y devolver el resultado
    return $this->consult($query, $params);
  }

  // Método privado para construir el filtro de documentos basado en las notificaciones del usuario actual
  private function buildFilter()
  {
    session_start();
    // Obtener el ID del usuario actual de la sesión actual
    $userId = $_SESSION['user']['id_user'];
    // Construir la consulta SQL para obtener los IDs de documentos notificados al usuario
    $query = "SELECT id_documents FROM notifications WHERE id_user_notificacion = ?";
    $params = array($userId); // Array de parámetros para la consulta, contiene el ID del usuario
    // Ejecutar la consulta y obtener los resultados
    $data = $this->consult($query, $params, true);
    // Inicializar el filtro como una cadena vacía
    $filter = "";
    // Verificar si la consulta devolvió filas (notificaciones de documentos)
    if (!empty($data)) {
      // Crear un array para almacenar los IDs de documentos
      $documentIds = array();
      // Recorrer los resultados de la consulta y agregar los IDs de documentos al array
      foreach ($data as $row) {
        $documentIds[] = $row;
      }
      // Construir la cláusula WHERE para excluir los documentos notificados
      $filter = " WHERE documents.id_document NOT IN (" . implode(",", $documentIds) . ")";
    }
    // Devolver el filtro construido
    return $filter;
  }

  // Archivos -> app/webservice.php
  public function ws_clearNotifications($newDocumentIds)
  {
    session_start();
    // Obtener el ID del usuario actual de la sesión actual
    $userId = $_SESSION['user']['id_user'];

    $query = "SELECT id_documents FROM notifications WHERE id_user_notificacion = ?";
    $selectParams = array($userId);
    $result = $this->consult($query, $selectParams, true);

    if ($result) {
      $existingDocumentIds = $result['id_documents'];
      $allDocumentIds = $existingDocumentIds ? $existingDocumentIds . ',' . $newDocumentIds : $newDocumentIds;

      $updateQuery = "UPDATE notifications SET id_documents = ? WHERE id_user_notificacion = ?";
      $updateParams = array($allDocumentIds, $userId);
      $updateResult = $this->execute($updateQuery, $updateParams);

      if ($updateResult) {
        return ['success' => true];
      } else {
        return ['success' => false, 'message' => 'Error al actualizar las notificaciones.'];
      }
    } else {
      return ['success' => false, 'message' => 'No se encontraron notificaciones para el usuario.'];
    }
  }

  // Archivos -> backoffice/users/create-user.php
  public function createNotifications($idUserNotify, $idDocuments)
  {
    $query = "INSERT INTO notifications(id_user_notificacion, id_documents) VALUES (?,?)";
    // Parámetros para la consulta preparada
    $params = array(
      $idUserNotify,
      $idDocuments,
    );
    // Ejecutar la consulta y retornar el resultado
    return $this->execute($query, $params);
  }

  // Archivos -> index.php
  public function idx_getFoldersMonth($fecha1, $fecha2)
  {
    // Construir la consulta SQL
    // Seleccionar todos los registros de la tabla 'folders' donde el estado de la carpeta (status_folder) es igual a 1 (activo)
    // y la fecha de creación de la carpeta (created_at_folder) está entre las fechas proporcionadas ($fecha1 y $fecha2) / se espera el mes actual
    $query = "SELECT * FROM folders WHERE status_folder = 1 AND fk_folder = 0 AND created_at_folder BETWEEN ? AND ?";
    // Inicializa el array de parámetros para la consulta preparada
    // Este array contiene las fechas $fecha1 y $fecha2 que se utilizarán en la consulta
    $params = array($fecha1, $fecha2);
    // Ejecutar la consulta SQL y devolver los resultados
    return $this->consult($query, $params);
  }

  // Archivos -> index.php
  public function idx_getDocumentsMonth($fecha1, $fecha2)
  {
    // Construye la consulta SQL base para seleccionar todos los documentos
    // La consulta selecciona todos los documentos cuya 'created_at_document' (fecha de creación) esté entre las fechas especificadas / se espera el mes actual
    // y cuyo 'status_document' sea 1 (activo)
    $query = "SELECT * FROM documents WHERE documents.status_document = 1 AND documents.created_at_document BETWEEN ? AND ?";
    // Inicializa el array de parámetros para la consulta preparada
    // Este array contiene las fechas $fecha1 y $fecha2 que se utilizarán en la consulta
    $params = array($fecha1, $fecha2);
    // Ejecuta la consulta SQL con los parámetros especificados y devuelve los resultados
    // La función 'consult' toma la consulta y los parámetros, la ejecuta y devuelve el resultado
    return $this->consult($query, $params);
  }

  // Archivos -> index.php
  public function idx_getSelectFolders($status)
  {
    // Construir la consulta SQL básica seleccionando todas las columnas de la tabla 'folders'
    // y filtrando por el status de la carpeta (status_folder) 1 (activo) y el rango de fechas (second_fech_folder)
    $query = "SELECT * FROM folders WHERE status_folder = 1 AND fk_folder = 0";
    // Inicializa el array de parámetros para la consulta preparada
    // Se agregan las dos fechas pasadas como argumentos a este array (se espera el mes actual)
    $params = array();
    // Agregar la condición adicional de estado basado en el parámetro $status
    if ($status == '03') {
      // Si el estado es '03', para seleccionar "carpetas vencidas"
      // La condición verifica si la diferencia de días entre la fecha actual y second_fech_folder es mayor o igual a 1
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) >=1";
    } elseif ($status == '01') {
      // Si el estado es '01', se agregará una condición para seleccionar carpetas "cerca de vencimiento"
      // La condición verifica si la diferencia de días está entre -60 y 0, inclusive
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= 0 AND DATEDIFF(NOW(), second_fech_folder) >= -60";
    }
    // Si el estado no es ni '03' ni '01', no se agrega ninguna condición adicional
    // Ejecutar la consulta SQL con los parámetros preparados y devolver los resultados
    // La función consult ejecuta la consulta SQL utilizando los parámetros proporcionados
    return $this->consult($query, $params);
  }

  // ---------------------------------------------------------------------------------------------------------------------

  // RESPALDO DE LA FUNCIÓN idx_getSelectFolders
  // CÓDIGO DE RESPALDO DONDE LA FUNCIÓN MOSTRABA LOS CLIENTES VENCIDOS Y CERCA DE VENCIMIENTO POR MES
  // Archivos -> index.php
  public function respaldo_idx_getSelectFolders($fecha1, $fecha2, $status)
  {
    // Construir la consulta SQL básica seleccionando todas las columnas de la tabla 'folders'
    // y filtrando por el status de la carpeta (status_folder) 1 (activo) y el rango de fechas (second_fech_folder)
    $query = "SELECT * FROM folders WHERE status_folder = 1 AND fk_folder = 0 AND second_fech_folder BETWEEN ? AND ?";
    // Inicializa el array de parámetros para la consulta preparada
    // Se agregan las dos fechas pasadas como argumentos a este array (se espera el mes actual)
    $params = array(
      $fecha1,
      $fecha2
    );
    // Agregar la condición adicional de estado basado en el parámetro $status
    if ($status == '03') {
      // Si el estado es '03', para seleccionar "carpetas vencidas"
      // La condición verifica si la diferencia de días entre la fecha actual y second_fech_folder es mayor o igual a 1
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) >=1";
    } elseif ($status == '01') {
      // Si el estado es '01', se agregará una condición para seleccionar carpetas "cerca de vencimiento"
      // La condición verifica si la diferencia de días está entre -60 y 0, inclusive
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= 0 AND DATEDIFF(NOW(), second_fech_folder) >= -60";
    }
    // Si el estado no es ni '03' ni '01', no se agrega ninguna condición adicional
    // Ejecutar la consulta SQL con los parámetros preparados y devolver los resultados
    // La función consult ejecuta la consulta SQL utilizando los parámetros proporcionados
    return $this->consult($query, $params);
  }

  // ---------------------------------------------------------------------------------------------------------------------

  // Archivos -> index.php
  public function idx_getFoldersAllSelect($status)
  {
    // Construir la consulta SQL básica para seleccionar todos los registros de la tabla 'folders'
    // que tienen 'status_folder' igual a 1 (es decir, que están activos).
    $query = "SELECT * FROM folders WHERE status_folder = 1 AND fk_folder = 0";
    // Inicializa un array de parámetros vacío para la consulta preparada.
    $params = [];
    // Agregar condiciones adicionales basadas en el valor del parámetro '$status'.
    if ($status == '02') {
      // Si el estado es '02', agregar una condición para seleccionar las carpetas "vigentes"
      // se espera que sean todas las carpetas vigentes del sistema
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= -61";
    } elseif ($status == 'null') {
      // Si el estado es 'null', agregar una condición para seleccionar las carpetas que no tienen un plazo de vencimiento.
      // se espera que sean todas las carpetas del sistema sin plazo de vencimiento
      $query .= " AND second_fech_folder IS NULL";
    }
    // Ejecutar la consulta SQL con los parámetros especificados y devolver los resultados.
    return $this->consult($query, $params);
  }

  // Archivos -> index.php , app/webservice.php , backoffice/folders/all_folders.php
  public function ws_idxGetFolders($fecha1, $fecha2, $status, $customer)
  {
    // Construir la consulta SQL básica
    // Selecciona todos los campos de la tabla 'folders' y 'users' y calcula los días transcurridos desde 'second_fech_folder' hasta hoy
    $query = "SELECT *, DATEDIFF(NOW(), second_fech_folder) as dias,
        EMPL.id_user AS 'id_user',
        EMPL.id_type_user AS 'id_type_user',
        EMPL.key_user AS 'key_user',
        EMPL.name_user AS 'name_user',
        --CUST.name_user AS 'name_customer',
        COALESCE(CUST.name_user, '- - -') AS 'name_customer' 
        FROM folders 
        JOIN users EMPL ON folders.id_user_folder = EMPL.id_user 
        LEFT JOIN users CUST ON folders.id_customer_folder = CUST.id_user -- Permitir registros sin relación 
        WHERE status_folder = 1 AND fk_folder = 0"; // Solo selecciona las carpetas con estado activo (status_folder = 1) y que sean las carpetas padre (fk_folder = 0)
    // Inicializa el array de parámetros para la consulta preparada
    $params = [];

    // PARA AUTOMATIZAR MAS EL CÓDIGO SE PUEDE USAR UN SWITCH EN LUGAR DE CONDICIONALES IF
    // AMBOS CÓDIGOS FUNCIONAN PERO HAREMOS USO DEL SWITCH EN CASO DE HACER USO DEL IF HAY QUE QUITAR LA LINEA 798 Y 837
    switch ($status) {
      case 'all':
        $query .= " AND (second_fech_folder BETWEEN ? AND ?)";
        array_push($params, $fecha1, $fecha2);
        break;
      case 'null':
        $query .= " AND second_fech_folder IS NULL";
        break;
      case '01':
        $query .= " AND second_fech_folder BETWEEN ? AND ? AND DATEDIFF(NOW(), second_fech_folder) BETWEEN -60 AND 0";
        array_push($params, $fecha1, $fecha2);
        break;
      case '02':
        $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= -61";
        break;
      default:
        $query .= " AND second_fech_folder BETWEEN ? AND ? AND DATEDIFF(NOW(), second_fech_folder) >= 1";
        array_push($params, $fecha1, $fecha2);
    }

    /*

    // Agregar la condición de estado según el valor de $status
    if ($status == 'all') {
      // Si el estado es 'all', no se agrega ninguna condición adicional de días
      // Selecciona las carpetas con 'second_fech_folder' entre $fecha1 y $fecha2
      // por defecto se muestran las carpetas "cerca de vencimiento" y "vencidas" y "vigentes" siempre y cuando se encuentren en el intervalo de fechas
      // las carpetas "sin plazo de vencimiento" no se muestran ya que el campo de "second_fech_folder" se guarda nulo
      $query .= " AND (second_fech_folder BETWEEN ? AND ?)";
      // Añade las fechas a los parámetros de la consulta
      array_push($params, $fecha1, $fecha2);
    }
    elseif ($status == 'null') {
      // Si el estado es 'null', selecciona las carpetas sin 'second_fech_folder'
      // muestra absolutamente todas las carpetas sin plazo de vencimiento
      $query .= " AND second_fech_folder IS NULL";
    } 
    elseif ($status == '01') {
      // Si el estado es '01' (Cerca de vencimiento), selecciona las carpetas con 'second_fech_folder' entre $fecha1 y $fecha2
      // y que la diferencia en días entre hoy y 'second_fech_folder' esté entre -60 y 0 días
      $query .= " AND second_fech_folder BETWEEN ? AND ? AND DATEDIFF(NOW(), second_fech_folder) BETWEEN -60 AND 0";
      // Añade las fechas a los parámetros de la consulta
      array_push($params, $fecha1, $fecha2);
    } 
    elseif ($status == '02') {
      // Si el estado es '02' (Carpeta vigente), selecciona las carpetas con 'second_fech_folder' menor o igual a -61 días
      // muestra absolutamente todas las carpetas vigentes sin exepción
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= -61";
      // Añade las fechas a los parámetros de la consulta
    } 
    else {
      // Si el estado es '03' (Carpeta vencida) o cualquier otro valor, selecciona las carpetas con 'second_fech_folder' entre $fecha1 y $fecha2
      // y que la diferencia en días entre hoy y 'second_fech_folder' sea mayor o igual a 1 día
      // muestra solo las carpetas definidas en el intervalo de fechas
      $query .= " AND second_fech_folder BETWEEN ? AND ? AND DATEDIFF(NOW(), second_fech_folder) >= 1";
      // Añade las fechas a los parámetros de la consulta
      array_push($params, $fecha1, $fecha2);
    }

    */

    if (!empty($customer)) {
      $query .= " AND id_customer_folder = ?";
      array_push($params, $customer);
    }

    // Agregar el ordenamiento por la fecha de VENCIMIENTO de la carpeta en orden ASCENDENTE
    $query .= " ORDER BY second_fech_folder ASC";
    // Ejecutar la consulta SQL y devolver los resultados
    // Utiliza la función 'consult' para ejecutar la consulta preparada con los parámetros
    return $this->consult($query, $params);
  }

  // Archivos -> app/webservice.php
  public function ws_getFoldersAll($fecha1, $fecha2, $status)
  {
    // Construir la consulta SQL
    $query = "SELECT *, DATEDIFF(NOW(), second_fech_folder) as dias 
        FROM folders 
        JOIN users ON folders.id_user_folder = users.id_user 
        WHERE status_folder = 1 
        AND created_at_folder BETWEEN ? AND ?";

    // Inicializa el array de parámetros para la consulta preparada
    $params = array($fecha1, $fecha2);

    // Agregar la condición de estado
    if ($status == 'all') {
      // No se agrega condición de días para 'all'
    } elseif ($status == 'null') {
      // Manejar la condición 'null' para sin plazo de vencimiento
      $query .= " AND second_fech_folder IS NULL";
      // el estatus 01 corresponde a Cerca de vencimiento
    } elseif ($status == '01') {
      // Manejar la condición 'null' para sin plazo de vencimiento
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <= 0 AND DATEDIFF(NOW(), second_fech_folder) >= -60";
      // el estatus 02 corresponde al estatus de Carpeta vigente
    } elseif ($status == '02') {
      // Manejar la condición 'null' para sin plazo de vencimiento
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) <=-61";
    }
    // el else corresponde al estatus 03 Carpeta vencida
    else {
      // Agregar la condición de días
      $query .= " AND DATEDIFF(NOW(), second_fech_folder) >=1";
    }
    // Agregar el ordenamiento
    $query .= " ORDER BY created_at_folder DESC";
    // Ejecutar la consulta SQL y devolver los resultados
    return $this->consult($query, $params);
  }

  public function getUser($idUser)
  {
    $query = "SELECT email_user FROM users WHERE id_user = ?";
    $params = array(
      $idUser
    );
    return $this->consult($query, $params, true);
  }

  public function createTracing($data)
  {
    $query = "INSERT INTO tracings(id_folder_tracing, id_user_tracing, key_tracing, comment_tracing, status_tracing, created_at_tracing, updated_at_tracing) VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
    $params = array(
      $data['id_folder_tracing'],
      $data['id_user_tracing'],
      $data['key_tracing'],
      $data['comment_tracing']
    );
    return $this->execute($query, $params);
  }

  public function getTracingsFolder($idFolder, $limit = 5, $offset = 0)
  {
    $query = "SELECT * FROM tracings 
      JOIN folders ON tracings.id_folder_tracing = folders.id_folder 
      JOIN users ON tracings.id_user_tracing = users.id_user 
      WHERE tracings.id_folder_tracing = ? 
      AND tracings.status_tracing = 1 
      ORDER BY tracings.created_at_tracing DESC 
      LIMIT $limit OFFSET $offset";

    $params = array(
      $idFolder
    );
    return $this->consult($query, $params);
  }

  public function countTracings($idFolder)
  {
    $query = "SELECT COUNT(*) as total FROM tracings WHERE id_folder_tracing = ?";
    $params = array($idFolder);
    $result = $this->consult($query, $params);
    return $result[0]['total'] ?? 0;
  }

  public function createTracingNotify($id_tracing_notify, $id_user_assigned_notify, $id_folder_notify)
  {
    $query = "INSERT INTO notify_tracings(id_tracing_notify, id_user_assigned_notify, id_folder_notify, is_reading, is_two_reading, created_at_notify, updated_at_notify) VALUES (?, ?, ?, 0, 0, NOW(), NOW())";
    $params = array(
      $id_tracing_notify,
      $id_user_assigned_notify,
      $id_folder_notify
    );
    return $this->execute($query, $params);
  }

  public function ws_getNoticationsTracings()
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $totals = "SELECT COUNT(*) AS total 
        FROM notify_tracings 
        JOIN tracings ON notify_tracings.id_tracing_notify = tracings.id_tracing 
        JOIN users ON notify_tracings.id_user_assigned_notify = users.id_user 
        JOIN folders ON notify_tracings.id_folder_notify = folders.id_folder 
        WHERE notify_tracings.is_reading = 0 
        AND notify_tracings.id_user_assigned_notify = ? 
        AND tracings.status_tracing = 1 
        AND users.status_user = 1 
        AND folders.status_folder = 1";

    $params = array($userId);
    return $this->consult($totals, $params, true);
  }

  public function ws_getNotWachTracings()
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $query = "SELECT 
      notify_tracings.id_notify,
      notify_tracings.is_reading,
      notify_tracings.created_at_notify,
      usrs.name_user,
      tracings.comment_tracing,
      folders.id_folder,
      folders.key_folder,
      folders.name_folder 
      
      FROM notify_tracings 
      JOIN tracings ON notify_tracings.id_tracing_notify = tracings.id_tracing 
      JOIN users ON notify_tracings.id_user_assigned_notify = users.id_user 
      JOIN folders ON notify_tracings.id_folder_notify = folders.id_folder 
      JOIN users usrs ON tracings.id_user_tracing = usrs.id_user 
      WHERE notify_tracings.is_reading = 0 
      AND notify_tracings.id_user_assigned_notify = ? 
      AND tracings.status_tracing = 1 
      AND users.status_user = 1 
      AND folders.status_folder = 1 
      ORDER BY notify_tracings.created_at_notify DESC LIMIT 20;";

    $params = array(
      $userId
    );
    return $this->consult($query, $params);
  }

  public function ws_clearTracingsNotify($notifyIds)
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    if (is_array($notifyIds) && count($notifyIds) > 0) {
      $placeholders = implode(',', array_fill(0, count($notifyIds), '?'));
      $updateQuery = "UPDATE notify_tracings SET is_reading = 1 WHERE id_user_assigned_notify = ? AND id_notify IN ($placeholders)";
      $updateParams = array_merge([$userId], $notifyIds);
      $updateResult = $this->execute($updateQuery, $updateParams);

      if ($updateResult) {
        return ['success' => true];
      } else {
        return ['success' => false, 'message' => 'Error al actualizar las notificaciones.'];
      }
    } else {
      return ['success' => false, 'message' => 'No se recibieron notificaciones válidas para actualizar.'];
    }
  }

  public function ws_getTracingsFolderUser($userId, $idFolder)
  {
    $totals = "SELECT COUNT(*) AS total 
      FROM notify_tracings 
      JOIN tracings ON notify_tracings.id_tracing_notify = tracings.id_tracing 
      JOIN users ON notify_tracings.id_user_assigned_notify = users.id_user 
      JOIN folders ON notify_tracings.id_folder_notify = folders.id_folder 
      WHERE notify_tracings.is_two_reading = 0 
      AND notify_tracings.id_user_assigned_notify = ? 
      AND notify_tracings.id_folder_notify = ? 
      AND tracings.status_tracing = 1 
      AND users.status_user = 1 
      AND folders.status_folder = 1";

    $params = array(
      $userId,
      $idFolder
    );
    return $this->consult($totals, $params, true);
  }

  public function ws_loadDataTracingsFolderUser($userId, $idFolder)
  {
    $totals = "SELECT notify_tracings.id_notify,
      notify_tracings.id_user_assigned_notify,
      notify_tracings.id_folder_notify
      
      FROM notify_tracings 
      JOIN tracings ON notify_tracings.id_tracing_notify = tracings.id_tracing 
      JOIN users ON notify_tracings.id_user_assigned_notify = users.id_user 
      JOIN folders ON notify_tracings.id_folder_notify = folders.id_folder 
      WHERE notify_tracings.is_two_reading = 0 
      AND notify_tracings.id_user_assigned_notify = ? 
      AND notify_tracings.id_folder_notify = ? 
      AND tracings.status_tracing = 1 
      AND users.status_user = 1 
      AND folders.status_folder = 1";
    $params = array(
      $userId,
      $idFolder
    );
    return $this->consult($totals, $params);
  }

  public function ws_clearTracingsNotifyFolder($notifyIds)
  {
    if (is_array($notifyIds) && count($notifyIds) > 0 && !in_array(null, $notifyIds, true)) {
      $placeholders = implode(',', array_fill(0, count($notifyIds), '?'));
      $updateQuery = "UPDATE notify_tracings SET is_two_reading = 1 WHERE id_notify IN ($placeholders)";
      $updateParams = $notifyIds;
      $updateResult = $this->execute($updateQuery, $updateParams);

      if ($updateResult) {
        return ['success' => true];
      } else {
        error_log("Error SQL: " . $this->lastError());
        return ['success' => false, 'message' => 'Error al actualizar las notificaciones.'];
      }
    } else {
      return ['success' => false, 'message' => 'No se recibieron notificaciones válidas para actualizar.'];
    }
  }

  public function deleteTracing($data)
  {
    $query = "UPDATE tracings SET status_tracing = 2, deleted_at_tracing = NOW() WHERE id_tracing = ? AND key_tracing = ?";

    $params = array(
      $data['id_tracing'],
      $data['key_tracing'],
    );
    return $this->execute($query, $params);
  }

  public function ws_getTracingDetail($idTracing, $keyTracing, $statusTracing)
  {
    $query = "SELECT * FROM tracings WHERE id_tracing = ? AND key_tracing = ? AND status_tracing = ?";

    $params = array(
      $idTracing,
      $keyTracing,
      $statusTracing
    );
    return $this->consult($query, $params, true);
  }

  public function updateDataTracing($data)
  {
    $query = "UPDATE tracings SET comment_tracing = ?, updated_at_tracing = NOW() WHERE id_tracing = ? AND key_tracing = ?";

    $params = array(
      $data['edit_comment_tracing'],
      $data['edit_id_tracing'],
      $data['edit_key_tracing'],
    );
    return $this->execute($query, $params);
  }

  public function createNewSection($data)
  {
    $chk_view_empl = isset($data['chk_view_empl']) ? $data['chk_view_empl'] : null;
    $chk_view_sales = isset($data['chk_view_sales']) ? $data['chk_view_sales'] : null;

    $query = "INSERT INTO sections(id_user_section, key_section, title_section, chk_view_empl, chk_view_sales, status_section, created_at_section, updated_at_section) VALUES (?,?,?,?,?,1,NOW(),NOW())";

    $params = array(
      $data['id_user_section'],
      $data['key_section'],
      $data['title_section'],
      $chk_view_empl,
      $chk_view_sales
    );
    return $this->execute($query, $params);
  }

  public function updateDataSection($data)
  {
    $chk_view_empl = isset($data['chk_view_empl']) ? $data['chk_view_empl'] : null;
    $chk_view_sales = isset($data['chk_view_sales']) ? $data['chk_view_sales'] : null;

    $query = "UPDATE sections SET title_section = ?, chk_view_empl = ?, chk_view_sales = ?, updated_at_section = NOW() WHERE id_section = ? AND key_section = ?";

    $params = array(
      $data['title_section'],
      $chk_view_empl,
      $chk_view_sales,
      $data['id_section'],
      $data['key_section']
    );
    return $this->execute($query, $params);
  }

  public function deleteSection($data)
  {
    $query = "UPDATE sections SET status_section = 2, deleted_at_section = NOW() WHERE id_section = ? AND key_section = ?";

    $params = array(
      $data['idSection'],
      $data['keySection']
    );
    return $this->execute($query, $params);
  }

  public function getSections($statusSection)
  {
    $query = "SELECT * FROM sections 
      JOIN users ON sections.id_user_section = users.id_user 
      WHERE sections.status_section = ? 
      ORDER BY sections.created_at_section DESC";

    $params = array($statusSection);
    return $this->consult($query, $params);
  }

  public function getSectionDetail($idSection, $statusSection)
  {
    $query = "SELECT * FROM sections WHERE id_section = ? AND status_section = ?";

    $params = array(
      $idSection,
      $statusSection
    );
    return $this->consult($query, $params, true);
  }

  public function createMaterial($data)
  {
    $query = "INSERT INTO materials_sections(id_section_material, id_user_material, key_material, file_name_material, file_extension_material, status_material, created_at_material, updated_at_material) VALUES (?,?,?,?,?,1,NOW(),NOW())";

    $params = array(
      $data['id_section_material'],
      $data['id_user_material'],
      $data['key_material'],
      $data['file_name_material'],
      $data['file_extension_material']
    );
    return $this->execute($query, $params);
  }

  public function getDocumentsSection($idSection)
  {
    $query = "SELECT * FROM materials_sections WHERE id_section_material = ? AND status_material = 1 ORDER BY position_material ASC"; // Ordenamiento de documentos por posición en orden Ascendente

    $params = array(
      $idSection
    );
    return $this->consult($query, $params);
  }

  public function updatePositionDocumentsSections($data)
  {
    $query = "UPDATE materials_sections SET position_material = ? WHERE id_material = ?";
    foreach ($data['order'] as $position => $id) {
      $params = array(
        $position + 1,
        $id
      );
      $this->execute($query, $params);
    }
    return json_encode(['success' => true]);
  }

  public function deleteMaterialSection($data)
  {
    $query = "UPDATE materials_sections SET status_material = 2, position_material = 0, deleted_at_material = NOW() WHERE id_material = ? AND id_section_material = ? AND key_material = ?";

    $params = array(
      $data['id_material'],
      $data['id_section_material'],
      $data['key_material']
    );
    return $this->execute($query, $params);
  }

  public function getCustomersList($type_user, $status)
  {
    $query = "SELECT * FROM users JOIN types ON users.id_type_user = types.id_type WHERE users.id_type_user = ? AND users.status_user = ? ORDER BY users.created_at_user DESC";

    $params = array(
      $type_user,
      $status
    );
    return $this->consult($query, $params);
  }

  public function createNotifyFolder($data, $status, $type_user)
  {
    $query = "INSERT INTO notify_folders(id_folder_notify_assigned, id_user_notify_assigned, is_reading_notify, message_notify, usr_type_notify, status_notify_folder, created_at_notify_folder, updated_at_notify_folder) VALUES (?,?,0,?,?,1,NOW(),NOW())";
    $params = array(
      $data['id_folder'],
      $data['id_customer_folder'],
      $status,
      $type_user
    );
    return $this->execute($query, $params);
  }

  public function ws_getFolderNotifications()
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $totals = "SELECT COUNT(*) AS total 
      FROM notify_folders 
      JOIN folders ON notify_folders.id_folder_notify_assigned = folders.id_folder 
      LEFT JOIN users ON notify_folders.id_user_notify_assigned = users.id_user -- Permitir registros sin relación 
      WHERE notify_folders.is_reading_notify = 0 
      AND notify_folders.status_notify_folder = 1 
      AND notify_folders.id_user_notify_assigned = ? 
      AND folders.status_folder = 1";

    $params = array(
      $userId
    );
    return $this->consult($totals, $params, true);
  }

  public function ws_getNotWatchFolderNotifications()
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $query = "SELECT 
      folders.id_folder,
      folders.key_folder,
      folders.id_customer_folder,
      folders.name_folder,
      folders.first_fech_folder,
      folders.second_fech_folder,
      --users.id_user,
      --users.name_user,
      notify_folders.id_notify_folder,
      --notify_folders.id_folder_notify_assigned,
      --notify_folders.id_user_notify_assigned,
      --notify_folders.is_reading_notify,
      notify_folders.message_notify,
      notify_folders.usr_type_notify,
      notify_folders.created_at_notify_folder,
      notify_folders.updated_at_notify_folder,
      cust.id_user AS 'id_user_customer',
      cust.key_user AS 'key_user_customer',
      cust.name_user AS 'name_customer' 
      FROM notify_folders 
      JOIN folders ON notify_folders.id_folder_notify_assigned = folders.id_folder 
      LEFT JOIN users ON notify_folders.id_user_notify_assigned = users.id_user 
      LEFT JOIN users cust ON folders.id_customer_folder = cust.id_user 
      WHERE notify_folders.is_reading_notify = 0 
      AND notify_folders.status_notify_folder = 1 
      AND notify_folders.id_user_notify_assigned = ? 
      AND folders.status_folder = 1 
      ORDER BY notify_folders.updated_at_notify_folder DESC;";

    $params = array(
      $userId
    );
    return $this->consult($query, $params);
  }

  public function ws_clearFolderNotifications($notifyIds)
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    if (is_array($notifyIds) && count($notifyIds) > 0) {
      $placeholders = implode(',', array_fill(0, count($notifyIds), '?'));
      $updateQuery = "UPDATE notify_folders SET is_reading_notify = 1 WHERE id_user_notify_assigned = ? AND id_notify_folder IN ($placeholders)";
      $updateParams = array_merge([$userId], $notifyIds);
      $updateResult = $this->execute($updateQuery, $updateParams);

      if ($updateResult) {
        return ['success' => true];
      } else {
        return ['success' => false, 'message' => 'Error al actualizar las notificaciones.'];
      }
    } else {
      return ['success' => false, 'message' => 'No se recibieron notificaciones válidas para actualizar.'];
    }
  }

  public function ws_deleteFolderNotification($data)
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $query = "UPDATE notify_folders SET is_reading_notify = 1 WHERE id_user_notify_assigned = ? AND id_notify_folder = ?";
    $params = array(
      $userId,
      $data
    );
    return $this->execute($query, $params);
  }

  public function ws_deleteTracingNotify($data)
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $query = "UPDATE notify_tracings SET is_reading = 1 WHERE id_user_assigned_notify = ? AND id_notify = ?";
    $params = array(
      $userId,
      $data
    );

    return $this->execute($query, $params);
  }

  public function ws_deleteDocumentNotify($newDocumentId)
  {
    session_start();
    $userId = $_SESSION['user']['id_user'];

    $query = "SELECT id_documents FROM notifications WHERE id_user_notificacion = ?";
    $selectParams = array(
      $userId
    );
    $result = $this->consult($query, $selectParams, true);

    if ($result) {
      $existingDocumentIds = $result['id_documents'];
      $idsArray = $existingDocumentIds ? explode(',', $existingDocumentIds) : [];

      if (!in_array($newDocumentId, $idsArray)) {
        $idsArray[] = $newDocumentId;
      }

      $updatedDocumentIds = implode(',', $idsArray);
      $updateQuery = "UPDATE notifications SET id_documents = ? WHERE id_user_notificacion = ?";
      $updateParams = array(
        $updatedDocumentIds,
        $userId
      );
      $updateResult = $this->execute($updateQuery, $updateParams);

      if ($updateResult) {
        return ['success' => true];
      } else {
        return ['success' => false, 'message' => 'Error al actualizar la notificación.'];
      }
    } else {
      $insertQuery = "INSERT INTO notifications (id_user_notificacion, id_documents) VALUES (?, ?)";
      $insertParams = array(
        $userId,
        $newDocumentId
      );
      $insertResult = $this->execute($insertQuery, $insertParams);

      if ($insertResult) {
        return ['success' => true];
      } else {
        return ['success' => false, 'message' => 'Error al insertar una nueva notificación.'];
      }
    }
  }

  public function checkNotification($id_folder_notify_assigned, $id_user_notify_assigned)
  {
    $query = "SELECT * FROM notify_folders WHERE id_folder_notify_assigned = ? AND id_user_notify_assigned = ?";

    $params = array(
      $id_folder_notify_assigned,
      $id_user_notify_assigned
    );
    return $this->consult($query, $params, true);
  }

  public function checkVigentes($id_folder_notify_assigned)
  {
    $query = "SELECT * FROM notify_folders WHERE id_folder_notify_assigned = ? AND is_reading_notify = 0";

    $params = array(
      $id_folder_notify_assigned
    );
    return $this->consult($query, $params);
  }

  public function updateNotificationStatus($id_folder_notify_assigned, $id_user_notify_assigned, $new_status)
  {
    $query = "UPDATE notify_folders SET message_notify = ?, is_reading_notify = 0, updated_at_notify_folder = NOW() WHERE id_folder_notify_assigned = ? AND id_user_notify_assigned = ?";
    $params = array(
      $new_status,
      $id_folder_notify_assigned,
      $id_user_notify_assigned
    );
    return $this->execute($query, $params);
  }

  public function updateNotifyVigente($data, $status)
  {
    $query = "UPDATE notify_folders SET message_notify = ?, is_reading_notify = 1, updated_at_notify_folder = NOW() WHERE id_notify_folder = ? AND id_folder_notify_assigned = ?";

    $params = array(
      $status,
      $data['id_notify_folder'],
      $data['id_folder_notify_assigned']
    );
    return $this->execute($query, $params);
  }




  // ============================================================================
  // NUEVOS MÉTODOS PARA WEBCONTROLLER.PHP - SISTEMA EMPRESAS
  // ============================================================================

  // === MÉTODOS PARA EMPRESAS ===

  /**
   * Obtener todas las empresas activas
   */
  public function getActiveCompanies()
  {
    $query = "SELECT * FROM companies WHERE status_company = 1 ORDER BY name_company ASC";
    return $this->consult($query);
  }

  /**
   * Obtener empresa por ID
   */
  public function getCompanyById($idCompany)
  {
    $query = "SELECT * FROM companies WHERE id_company = ? AND status_company = 1";
    $params = array($idCompany);
    return $this->consult($query, $params, true);
  }

  /**
   * Crear nueva empresa
   */
  public function createCompany($data)
  {
    $query = "INSERT INTO companies (
            key_company, name_company, rfc_company, razon_social, tipo_persona, fecha_constitucion,
            estado, ciudad, colonia, calle, num_exterior, num_interior, codigo_postal, telefono, email,
            apoderado_nombre, apoderado_apellido_paterno, apoderado_apellido_materno, apoderado_rfc, apoderado_curp,
            status_company, created_at_company, updated_at_company
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,NOW(),NOW())";

    $params = array(
      $data['key_company'],
      $data['name_company'],
      $data['rfc_company'],
      $data['razon_social'],
      $data['tipo_persona'],
      $data['fecha_constitucion'],
      $data['estado'],
      $data['ciudad'],
      $data['colonia'],
      $data['calle'],
      $data['num_exterior'],
      $data['num_interior'],
      $data['codigo_postal'],
      $data['telefono'],
      $data['email'],
      $data['apoderado_nombre'],
      $data['apoderado_apellido_paterno'],
      $data['apoderado_apellido_materno'],
      $data['apoderado_rfc'],
      $data['apoderado_curp']
    );
    return $this->execute($query, $params);
  }

  /**
   * Actualizar empresa
   */
  public function updateCompany($data, $idCompany)
  {
    // Función helper para limpiar valores vacíos
    $cleanValue = function ($value) {
      return (!empty($value) && $value !== '') ? $value : null;
    };

    // Obtener el nombre anterior de la empresa para actualizar la carpeta
    $getCurrentNameQuery = "SELECT name_company FROM companies WHERE id_company = ?";
    $currentCompany = $this->consult($getCurrentNameQuery, array($idCompany), true);
    $oldName = $currentCompany ? $currentCompany['name_company'] : null;

    // 1. ACTUALIZAR LA EMPRESA
    $query = "UPDATE companies SET 
        name_company = ?, rfc_company = ?, razon_social = ?, tipo_persona = ?, fecha_constitucion = ?,
        estado = ?, ciudad = ?, colonia = ?, calle = ?, num_exterior = ?, num_interior = ?, 
        codigo_postal = ?, telefono = ?, email = ?, 
        apoderado_nombre = ?, apoderado_apellido_paterno = ?, apoderado_apellido_materno = ?, 
        apoderado_rfc = ?, apoderado_curp = ?,
        fiduciario_nombre = ?, fiduciario_rfc = ?, fideicomitente_nombre = ?, 
        fideicomitente_apellido_paterno = ?, fideicomitente_apellido_materno = ?,
        fideicomitente_rfc = ?, fideicomitente_curp = ?, fideicomisario_nombre = ?,
        fideicomisario_apellido_paterno = ?, fideicomisario_apellido_materno = ?,
        fideicomisario_rfc = ?, fideicomisario_curp = ?, numero_fideicomiso = ?, fecha_fideicomiso = ?,
        updated_at_company = NOW()
        WHERE id_company = ?";

    $params = array(
      $data['name_company'],
      $data['rfc_company'],
      $data['razon_social'],
      $data['tipo_persona'],
      $cleanValue($data['fecha_constitucion']),
      $cleanValue($data['estado']),
      $cleanValue($data['ciudad']),
      $cleanValue($data['colonia']),
      $cleanValue($data['calle']),
      $cleanValue($data['num_exterior']),
      $cleanValue($data['num_interior']),
      $cleanValue($data['codigo_postal']),
      $cleanValue($data['telefono']),
      $cleanValue($data['email']),
      // Campos del representante legal
      $cleanValue($data['apoderado_nombre']),
      $cleanValue($data['apoderado_apellido_paterno']),
      $cleanValue($data['apoderado_apellido_materno']),
      $cleanValue($data['apoderado_rfc']),
      $cleanValue($data['apoderado_curp']),
      // Campos del fideicomiso
      $cleanValue($data['fiduciario_nombre']),
      $cleanValue($data['fiduciario_rfc']),
      $cleanValue($data['fideicomitente_nombre']),
      $cleanValue($data['fideicomitente_apellido_paterno']),
      $cleanValue($data['fideicomitente_apellido_materno']),
      $cleanValue($data['fideicomitente_rfc']),
      $cleanValue($data['fideicomitente_curp']),
      $cleanValue($data['fideicomisario_nombre']),
      $cleanValue($data['fideicomisario_apellido_paterno']),
      $cleanValue($data['fideicomisario_apellido_materno']),
      $cleanValue($data['fideicomisario_rfc']),
      $cleanValue($data['fideicomisario_curp']),
      $cleanValue($data['numero_fideicomiso']),
      $cleanValue($data['fecha_fideicomiso']),
      $idCompany
    );

    $updated = $this->execute($query, $params);

    // 2. SI SE ACTUALIZÓ LA EMPRESA Y CAMBIÓ EL NOMBRE, ACTUALIZAR LA CARPETA USANDO company_id
    if ($updated && $oldName && $oldName !== $data['name_company']) {
      $updateFolderQuery = "UPDATE folders SET 
                             name_folder = ?, 
                             updated_at_folder = NOW() 
                             WHERE company_id = ? 
                             AND fk_folder = 0 
                             AND status_folder = 1";

      $folderParams = array(
        $data['name_company'], // Nuevo nombre
        $idCompany // ← USAR LA RELACIÓN DIRECTA company_id
      );

      $this->execute($updateFolderQuery, $folderParams);
    }

    return $updated;
  }


  /**
   * Verificar si RFC de empresa ya existe
   */
  public function getRFCCompany($rfcCompany)
  {
    $query = "SELECT * FROM companies WHERE rfc_company = ? AND status_company = 1";
    $params = array($rfcCompany);
    return $this->consult($query, $params, true);
  }



  /**
   * Obtener datos de empresa para pre-rellenar operaciones PLD
   */
  public function getUserCompanyData($userId)
  {
    $query = "SELECT u.*, c.* 
                  FROM users u 
                  LEFT JOIN companies c ON u.id_company = c.id_company 
                  WHERE u.id_user = ? AND u.status_user = 1";
    $params = array($userId);
    return $this->consult($query, $params, true);
  }

  /**
   * Obtener lista de usuarios por empresa (para administradores de empresa)
   */
  public function getUsersByCompany($idCompany)
  {
    $query = "SELECT u.*, t.name_type 
                  FROM users u 
                  JOIN types t ON u.id_type_user = t.id_type 
                  WHERE u.id_company = ? AND u.status_user = 1 
                  ORDER BY u.name_user ASC";
    $params = array($idCompany);
    return $this->consult($query, $params);
  }


  /**
   * Verificar si RFC de empresa ya existe excluyendo una empresa específica
   */
  public function getRFCCompanyExclude($rfcCompany, $excludeCompanyId)
  {
    $query = "SELECT * FROM companies WHERE rfc_company = ? AND status_company = 1 AND id_company != ?";
    $params = array($rfcCompany, $excludeCompanyId);
    return $this->consult($query, $params, true);
  }


  /**
   * Eliminar empresa (versión simplificada si no usas key_company)
   */
  public function deleteCompany($data)
  {
    // Si usas key_company en tu tabla
    if (isset($data['keyCompany']) && !empty($data['keyCompany'])) {
      $query = "UPDATE companies SET status_company = 3, eliminated_at_company = NOW() 
                  WHERE id_company = ? AND key_company = ?";
      $params = array($data['idCompany'], $data['keyCompany']);
    } else {
      // Si NO usas key_company en tu tabla
      $query = "UPDATE companies SET status_company = 3, eliminated_at_company = NOW() 
                  WHERE id_company = ?";
      $params = array($data['idCompany']);
    }
    return $this->execute($query, $params);
  }






  // Función para obtener EMPRESAS DEL SISTEMA (para select en create-user.php)
  public function getSystemCompanies($status)
  {
    $user_type = $_SESSION['user']['id_type_user'];

    if ($user_type == 1) { // ADMINISTRADOR
      // Ve todas las empresas del sistema para asignar usuarios
      $query = "SELECT * FROM companies WHERE status_company = ? AND type_company = 'system' ORDER BY name_company ASC";
      $params = array($status);
    } else { // EMPLEADO/VENTAS  
      // Solo ve su propia empresa del sistema
      $user_company_id = $_SESSION['user']['id_company'];
      $query = "SELECT * FROM companies WHERE status_company = ? AND id_company = ? ORDER BY name_company ASC";
      $params = array($status, $user_company_id);
    }

    return $this->consult($query, $params);
  }



  // Función para obtener EMPRESAS CLIENTES (para companies.php)
// Función para obtener EMPRESAS CLIENTES (para companies.php)
  public function getClientCompanies($status)
  {
    // TODOS los usuarios ven TODAS las empresas clientes
    // Sin importar quién las creó
    $query = "SELECT c.*, u.name_user as created_by 
              FROM companies c 
              LEFT JOIN users u ON c.created_by_user = u.id_user 
              WHERE c.status_company = ? AND c.type_company = 'client'
              ORDER BY c.created_at_company DESC";
    $params = array($status);

    return $this->consult($query, $params);
  }

  // Función para crear una empresa cliente
// Función para crear una empresa cliente
  public function createClientCompany($data)
  {
    $user_id = $_SESSION['user']['id_user'];

    // Función helper para limpiar valores vacíos
    $cleanValue = function ($value) {
      return (!empty($value) && $value !== '') ? $value : null;
    };

    $query = "INSERT INTO companies(
                key_company, name_company, rfc_company, razon_social, tipo_persona, 
                telefono, email, estado, ciudad, calle, colonia, 
                num_exterior, num_interior, codigo_postal, fecha_constitucion,
                apoderado_nombre, apoderado_apellido_paterno, apoderado_apellido_materno,
                apoderado_rfc, apoderado_curp,
                fiduciario_nombre, fiduciario_rfc, fideicomitente_nombre, 
                fideicomitente_apellido_paterno, fideicomitente_apellido_materno,
                fideicomitente_rfc, fideicomitente_curp, fideicomisario_nombre,
                fideicomisario_apellido_paterno, fideicomisario_apellido_materno,
                fideicomisario_rfc, fideicomisario_curp, numero_fideicomiso, fecha_fideicomiso,
                type_company, created_by_user, status_company, created_at_company, updated_at_company
              ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'client',?,1,NOW(),NOW())";

    $params = array(
      $data['key_company'],
      $data['name_company'],
      $data['rfc_company'],
      $data['razon_social'],
      $data['tipo_persona'],
      $cleanValue($data['telefono']),
      $cleanValue($data['email']),
      $cleanValue($data['estado']),
      $cleanValue($data['ciudad']),
      $cleanValue($data['calle']),
      $cleanValue($data['colonia']),
      $cleanValue($data['num_exterior']),
      $cleanValue($data['num_interior']),
      $cleanValue($data['codigo_postal']),
      $cleanValue($data['fecha_constitucion']),
      $cleanValue($data['apoderado_nombre']),
      $cleanValue($data['apoderado_apellido_paterno']),
      $cleanValue($data['apoderado_apellido_materno']),
      $cleanValue($data['apoderado_rfc']),
      $cleanValue($data['apoderado_curp']),
      // Campos del fideicomiso
      $cleanValue($data['fiduciario_nombre']),
      $cleanValue($data['fiduciario_rfc']),
      $cleanValue($data['fideicomitente_nombre']),
      $cleanValue($data['fideicomitente_apellido_paterno']),
      $cleanValue($data['fideicomitente_apellido_materno']),
      $cleanValue($data['fideicomitente_rfc']),
      $cleanValue($data['fideicomitente_curp']),
      $cleanValue($data['fideicomisario_nombre']),
      $cleanValue($data['fideicomisario_apellido_paterno']),
      $cleanValue($data['fideicomisario_apellido_materno']),
      $cleanValue($data['fideicomisario_rfc']),
      $cleanValue($data['fideicomisario_curp']),
      $cleanValue($data['numero_fideicomiso']),
      $cleanValue($data['fecha_fideicomiso']),
      $user_id
    );

    $companyId = $this->execute($query, $params);

    // 2. SI SE CREÓ LA EMPRESA, CREAR AUTOMÁTICAMENTE UNA CARPETA CON RELACIÓN
    if ($companyId) {
      // Generar clave para la carpeta
      $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $folderKey = substr(str_shuffle($permitted_chars), 0, 10);

      // Crear carpeta con el nombre de la empresa Y la relación company_id
      $folderData = array(
        'id_user_folder' => $user_id,
        'id_customer_folder' => 0,
        'company_id' => $companyId, // ← NUEVA RELACIÓN
        'fk_folder' => 0,
        'key_folder' => $folderKey,
        'name_folder' => $data['name_company'],
        'first_fech_folder' => null,
        'second_fech_folder' => null,
        'chk_alta_fact_folder' => null,
        'chk_lib_folder' => null,
        'chk_orig_recib_folder' => null,
        'fech_orig_recib_folder' => null
      );

      $this->createFolder($folderData);
    }

    return $companyId;
  }


  // Función para obtener empresas clientes para selects



  // Estadísticas de empresas clientes

  public function getClientCompaniesStats()
  {
    // TODOS ven las estadísticas de TODAS las empresas clientes
    $where_clause = "WHERE status_company = 1 AND type_company = 'client'";
    $params = array();

    // Total
    $query_total = "SELECT COUNT(*) as total FROM companies $where_clause";
    $total = $this->consult($query_total, $params, true);

    // Por tipo
    $query_moral = "SELECT COUNT(*) as total FROM companies $where_clause AND tipo_persona = 'Moral'";
    $moral = $this->consult($query_moral, $params, true);

    $query_fisica = "SELECT COUNT(*) as total FROM companies $where_clause AND tipo_persona = 'Física'";
    $fisica = $this->consult($query_fisica, $params, true);

    $query_fideicomiso = "SELECT COUNT(*) as total FROM companies $where_clause AND tipo_persona = 'Fideicomiso'";
    $fideicomiso = $this->consult($query_fideicomiso, $params, true);

    return array(
      'total' => $total['total'],
      'moral' => $moral['total'],
      'fisica' => $fisica['total'],
      'fideicomiso' => $fideicomiso['total']
    );
  }

  // Función para obtener todas las empresas (carpetas principales)
  public function getCompanies($statusFolder = 1)
  {
    $query = "SELECT id_folder, name_folder FROM folders WHERE status_folder = ? AND fk_folder = 0 ORDER BY name_folder ASC";
    $params = array($statusFolder);
    return $this->consult($query, $params);
  }























// =====================================================================
// MÉTODOS CORREGIDOS PARA WebController.php
// La estructura correcta es:
// - Empresa principal: company_id = X, fk_folder = 0 
// - Clientes de empresa: fk_folder = [id_folder de empresa principal]
// =====================================================================

/**
 * Función para obtener clientes solo por empresa (todos los tipos)
 * @param int $empresaId ID de la empresa (company_id de la tabla companies)
 * @return array Array de clientes
 */
public function getClientsByCompany($empresaId) {
    // Primero obtener el id_folder de la carpeta principal de la empresa
    $empresa_folder_query = "SELECT id_folder FROM folders WHERE company_id = ? AND fk_folder = 0 AND status_folder = 1 LIMIT 1";
    $empresa_folder = $this->consult($empresa_folder_query, array($empresaId), true);
    
    if (!$empresa_folder) {
        return array(); // Si no existe la carpeta principal, retornar array vacío
    }
    
    $empresa_folder_id = $empresa_folder['id_folder'];
    
    // Ahora buscar clientes donde fk_folder = id_folder de la empresa principal
    $where_clause = "WHERE f.status_folder = 1 AND f.eliminated_at_folder IS NULL AND f.fk_folder = ?";
    $params = array($empresa_folder_id);
    
    $query = "SELECT 
        f.id_folder,
        f.key_folder,
        f.tipo_persona,
        f.rfc_folder,
        f.curp_folder,
        f.name_folder,
        CASE 
            WHEN f.tipo_persona = 'fisica' THEN CONCAT(f.pf_nombre, ' ', f.pf_apellido_paterno, ' ', IFNULL(f.pf_apellido_materno, ''))
            WHEN f.tipo_persona = 'moral' THEN f.pm_razon_social
            WHEN f.tipo_persona = 'fideicomiso' THEN f.fid_razon_social
            ELSE f.name_folder
        END as nombre_completo,
        f.pf_nombre,
        f.pf_apellido_paterno,
        f.pf_apellido_materno,
        f.pf_fecha_nacimiento,
        f.pf_estado,
        f.pf_ciudad,
        f.pf_colonia,
        f.pf_calle,
        f.pf_num_exterior,
        f.pf_num_interior,
        f.pf_codigo_postal,
        f.pf_telefono,
        f.pf_email,
        f.pm_razon_social,
        f.pm_fecha_constitucion,
        f.pm_apoderado_nombre,
        f.pm_apoderado_paterno,
        f.pm_apoderado_materno,
        f.pm_apoderado_rfc,
        f.pm_apoderado_curp,
        f.pm_estado,
        f.pm_ciudad,
        f.pm_colonia,
        f.pm_calle,
        f.pm_num_exterior,
        f.pm_num_interior,
        f.pm_codigo_postal,
        f.pm_telefono,
        f.pm_email,
        f.fid_razon_social,
        f.fid_numero_referencia,
        f.fid_estado,
        f.fid_ciudad,
        f.fid_colonia,
        f.fid_calle,
        f.fid_num_exterior,
        f.fid_num_interior,
        f.fid_codigo_postal,
        f.fid_telefono,
        f.fid_email,
        c.name_company,
        c.id_company
    FROM folders f 
    LEFT JOIN companies c ON f.company_id = c.id_company
    {$where_clause}
    ORDER BY nombre_completo ASC";
    
    return $this->consult($query, $params);
}

/**
 * Función para obtener clientes por empresa y tipo de persona
 * @param int $empresaId ID de la empresa (company_id)
 * @param string $tipoPersona Tipo de persona (fisica, moral, fideicomiso)
 * @return array Array de clientes
 */
public function getClientsByCompanyAndType($empresaId, $tipoPersona = null) {
    // Primero obtener el id_folder de la carpeta principal de la empresa
    $empresa_folder_query = "SELECT id_folder FROM folders WHERE company_id = ? AND fk_folder = 0 AND status_folder = 1 LIMIT 1";
    $empresa_folder = $this->consult($empresa_folder_query, array($empresaId), true);
    
    if (!$empresa_folder) {
        return array(); // Si no existe la carpeta principal, retornar array vacío
    }
    
    $empresa_folder_id = $empresa_folder['id_folder'];
    
    // Ahora buscar clientes donde fk_folder = id_folder de la empresa principal
    $where_clause = "WHERE f.status_folder = 1 AND f.eliminated_at_folder IS NULL AND f.fk_folder = ?";
    $params = array($empresa_folder_id);
    
    // Filtrar por tipo de persona si se especifica
    if ($tipoPersona) {
        $where_clause .= " AND f.tipo_persona = ?";
        $params[] = $tipoPersona;
    }
    
    $query = "SELECT 
        f.id_folder,
        f.key_folder,
        f.tipo_persona,
        f.rfc_folder,
        f.curp_folder,
        f.name_folder,
        CASE 
            WHEN f.tipo_persona = 'fisica' THEN CONCAT(f.pf_nombre, ' ', f.pf_apellido_paterno, ' ', IFNULL(f.pf_apellido_materno, ''))
            WHEN f.tipo_persona = 'moral' THEN f.pm_razon_social
            WHEN f.tipo_persona = 'fideicomiso' THEN f.fid_razon_social
            ELSE f.name_folder
        END as nombre_completo,
        f.pf_nombre,
        f.pf_apellido_paterno,
        f.pf_apellido_materno,
        f.pf_fecha_nacimiento,
        f.pf_estado,
        f.pf_ciudad,
        f.pf_colonia,
        f.pf_calle,
        f.pf_num_exterior,
        f.pf_num_interior,
        f.pf_codigo_postal,
        f.pf_telefono,
        f.pf_email,
        f.pm_razon_social,
        f.pm_fecha_constitucion,
        f.pm_apoderado_nombre,
        f.pm_apoderado_paterno,
        f.pm_apoderado_materno,
        f.pm_apoderado_rfc,
        f.pm_apoderado_curp,
        f.pm_estado,
        f.pm_ciudad,
        f.pm_colonia,
        f.pm_calle,
        f.pm_num_exterior,
        f.pm_num_interior,
        f.pm_codigo_postal,
        f.pm_telefono,
        f.pm_email,
        f.fid_razon_social,
        f.fid_numero_referencia,
        f.fid_estado,
        f.fid_ciudad,
        f.fid_colonia,
        f.fid_calle,
        f.fid_num_exterior,
        f.fid_num_interior,
        f.fid_codigo_postal,
        f.fid_telefono,
        f.fid_email,
        c.name_company,
        c.id_company
    FROM folders f 
    LEFT JOIN companies c ON f.company_id = c.id_company
    {$where_clause}
    ORDER BY nombre_completo ASC";
    
    return $this->consult($query, $params);
}

/**
 * Función para obtener TODOS los clientes sin filtros de empresa
 * @return array Array de todos los clientes
 */
public function getAllClients() {
    // Buscar todos los registros que sean clientes (fk_folder != 0)
    // Esto excluye las carpetas principales de empresas
    $where_clause = "WHERE f.status_folder = 1 AND f.eliminated_at_folder IS NULL AND f.fk_folder != 0";
    $params = array();
    
    $query = "SELECT 
        f.id_folder,
        f.key_folder,
        f.tipo_persona,
        f.rfc_folder,
        f.curp_folder,
        f.name_folder,
        CASE 
            WHEN f.tipo_persona = 'fisica' THEN CONCAT(f.pf_nombre, ' ', f.pf_apellido_paterno, ' ', IFNULL(f.pf_apellido_materno, ''))
            WHEN f.tipo_persona = 'moral' THEN f.pm_razon_social
            WHEN f.tipo_persona = 'fideicomiso' THEN f.fid_razon_social
            ELSE f.name_folder
        END as nombre_completo,
        f.pf_nombre,
        f.pf_apellido_paterno,
        f.pf_apellido_materno,
        f.pf_fecha_nacimiento,
        f.pf_estado,
        f.pf_ciudad,
        f.pf_colonia,
        f.pf_calle,
        f.pf_num_exterior,
        f.pf_num_interior,
        f.pf_codigo_postal,
        f.pf_telefono,
        f.pf_email,
        f.pm_razon_social,
        f.pm_fecha_constitucion,
        f.pm_apoderado_nombre,
        f.pm_apoderado_paterno,
        f.pm_apoderado_materno,
        f.pm_apoderado_rfc,
        f.pm_apoderado_curp,
        f.pm_estado,
        f.pm_ciudad,
        f.pm_colonia,
        f.pm_calle,
        f.pm_num_exterior,
        f.pm_num_interior,
        f.pm_codigo_postal,
        f.pm_telefono,
        f.pm_email,
        f.fid_razon_social,
        f.fid_numero_referencia,
        f.fid_estado,
        f.fid_ciudad,
        f.fid_colonia,
        f.fid_calle,
        f.fid_num_exterior,
        f.fid_num_interior,
        f.fid_codigo_postal,
        f.fid_telefono,
        f.fid_email,
        c.name_company,
        c.id_company
    FROM folders f 
    LEFT JOIN companies c ON f.company_id = c.id_company
    {$where_clause}
    ORDER BY nombre_completo ASC";
    
    return $this->consult($query, $params);
}

/**
 * Función para obtener empresas clientes para selects
 * Incluye el ID de la carpeta principal para optimizar consultas posteriores
 * @param int $status Estado de la empresa (1 = activa)
 * @return array Array de empresas clientes
 */
public function getClientCompaniesForSelect($status = 1) {
    $query = "SELECT 
        c.id_company,
        c.name_company,
        c.rfc_company,
        c.tipo_persona,
        f.id_folder as folder_principal_id
    FROM companies c
    LEFT JOIN folders f ON c.id_company = f.company_id AND f.fk_folder = 0 AND f.status_folder = 1
    WHERE c.status_company = ? AND c.type_company = 'client' 
    ORDER BY c.name_company ASC";
    
    $params = array($status);
    return $this->consult($query, $params);
}

/**
 * Función auxiliar para obtener el ID de la carpeta principal de una empresa
 * @param int $companyId ID de la empresa
 * @return int|null ID de la carpeta principal o null si no existe
 */
public function getCompanyMainFolderId($companyId) {
    $query = "SELECT id_folder FROM folders WHERE company_id = ? AND fk_folder = 0 AND status_folder = 1 LIMIT 1";
    $result = $this->consult($query, array($companyId), true);
    return $result ? $result['id_folder'] : null;
}

// FUNCIÓN EXISTENTE ACTUALIZADA: getClientsByType
// (Si ya existe, reemplazar con esta versión que respeta la nueva estructura)
public function getClientsByType($tipoPersona = null, $companyId = null) {
    $where_clause = "WHERE f.status_folder = 1 AND f.eliminated_at_folder IS NULL AND f.fk_folder != 0";
    $params = array();
    
    // Filtrar por tipo de persona si se especifica
    if ($tipoPersona) {
        $where_clause .= " AND f.tipo_persona = ?";
        $params[] = $tipoPersona;
    }
    
    // Filtrar por empresa si se especifica (para usuarios de empresa)
    // Necesitamos obtener primero el folder principal de la empresa
    if ($companyId) {
        $empresa_folder_id = $this->getCompanyMainFolderId($companyId);
        if ($empresa_folder_id) {
            $where_clause .= " AND f.fk_folder = ?";
            $params[] = $empresa_folder_id;
        } else {
            // Si no existe carpeta principal, no retornar nada
            return array();
        }
    }
    
    $query = "SELECT 
        f.id_folder,
        f.key_folder,
        f.tipo_persona,
        f.rfc_folder,
        f.curp_folder,
        CASE 
            WHEN f.tipo_persona = 'fisica' THEN CONCAT(f.pf_nombre, ' ', f.pf_apellido_paterno, ' ', IFNULL(f.pf_apellido_materno, ''))
            WHEN f.tipo_persona = 'moral' THEN f.pm_razon_social
            WHEN f.tipo_persona = 'fideicomiso' THEN f.fid_razon_social
            ELSE f.name_folder
        END as nombre_completo,
        f.pf_nombre,
        f.pf_apellido_paterno,
        f.pf_apellido_materno,
        f.pf_fecha_nacimiento,
        f.pf_estado,
        f.pf_ciudad,
        f.pf_colonia,
        f.pf_calle,
        f.pf_num_exterior,
        f.pf_num_interior,
        f.pf_codigo_postal,
        f.pf_telefono,
        f.pf_email,
        f.pm_razon_social,
        f.pm_fecha_constitucion,
        f.pm_apoderado_nombre,
        f.pm_apoderado_paterno,
        f.pm_apoderado_materno,
        f.pm_apoderado_rfc,
        f.pm_apoderado_curp,
        f.pm_estado,
        f.pm_ciudad,
        f.pm_colonia,
        f.pm_calle,
        f.pm_num_exterior,
        f.pm_num_interior,
        f.pm_codigo_postal,
        f.pm_telefono,
        f.pm_email,
        f.fid_razon_social,
        f.fid_numero_referencia,
        f.fid_estado,
        f.fid_ciudad,
        f.fid_colonia,
        f.fid_calle,
        f.fid_num_exterior,
        f.fid_num_interior,
        f.fid_codigo_postal,
        f.fid_telefono,
        f.fid_email,
        c.name_company,
        c.id_company
    FROM folders f 
    LEFT JOIN companies c ON f.company_id = c.id_company
    {$where_clause}
    ORDER BY nombre_completo ASC";
    
    return $this->consult($query, $params);
}

// FUNCIÓN EXISTENTE: getClientById
// (Si ya existe, verificar que tenga esta estructura, si no, agregar)
public function getClientById($idFolder) {
    $query = "SELECT 
        f.*,
        c.name_company,
        c.id_company
    FROM folders f 
    LEFT JOIN companies c ON f.company_id = c.id_company
    WHERE f.id_folder = ? AND f.status_folder = 1 AND f.eliminated_at_folder IS NULL";
    
    $params = array($idFolder);
    return $this->consult($query, $params, true);
}






  
}
?>