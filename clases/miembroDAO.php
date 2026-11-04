<?php

require_once __DIR__ . "/miembro.php";

class MiembroDAO {
    private $conexion;

    public function __construct()
    {
        //los datos de conexion se cargan aqui y no con global, asi funciona
        //aunque la clase se cargue por primera vez dentro de una funcion
        require __DIR__ . "/datos/config.php";

        $this->conexion = new PDO("mysql:host=$servidor;dbname=$nombreBase;charset=utf8mb4", $usuarioBD, $passwordBD);

        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    //crea un miembro; recibe la contraseña en texto plano y la guarda cifrada. Devuelve el id nuevo
    public function insert(string $nombre, string $email, string $telefono, string $password): int {
        $sql = "INSERT INTO miembro (nombre, email, telefono, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            $nombre,
            $email,
            $telefono,
            password_hash($password, PASSWORD_DEFAULT)
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function findById($id) {
        $sql = "SELECT * FROM miembro WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->crearMiembro($fila) : null;
    }

    public function findByEmail(string $email) {
        $sql = "SELECT * FROM miembro WHERE email = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$email]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->crearMiembro($fila) : null;
    }

    //guarda nombre, email y telefono (la contraseña y el avatar tienen sus propios metodos)
    public function update(Miembro $miembro) {
        $sql = "UPDATE miembro SET nombre = ?, email = ?, telefono = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $miembro->getNombre(),
            $miembro->getEmail(),
            $miembro->getTelefono(),
            $miembro->getId()
        ]);
    }

    //recibe la nueva contraseña en texto plano y la guarda cifrada
    public function cambiarPassword(int $id, string $nuevaPassword) {
        $sql = "UPDATE miembro SET password = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            password_hash($nuevaPassword, PASSWORD_DEFAULT),
            $id
        ]);
    }

    //$avatar es el nombre del archivo en img/avatares, o null para quitar la foto
    public function cambiarAvatar(int $id, ?string $avatar) {
        $sql = "UPDATE miembro SET avatar = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([$avatar, $id]);
    }

    //comprueba email y contraseña; devuelve el miembro si son correctos o null si no
    public function login(string $email, string $password) {
        $miembro = $this->findByEmail($email);
        if ($miembro && password_verify($password, $miembro->getPassword())) {
            return $miembro;
        }
        return null;
    }

    private function crearMiembro(array $fila): Miembro {
        return new Miembro($fila["nombre"], $fila["email"], $fila["telefono"], $fila["password"], (int) $fila["id"], $fila["avatar"], $fila["fecha_alta"]);
    }
}
?>
