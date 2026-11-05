<?php

require_once __DIR__ . "/usuario.php";

class UsuarioDAO {
    private $conexion;

    public function __construct()
    {
        //los datos de conexion se cargan aqui y no con global, asi funciona
        //aunque la clase se cargue por primera vez dentro de una funcion
        require __DIR__ . "/datos/config.php";

        $this->conexion = new PDO("mysql:host=$servidor;dbname=$nombreBase;charset=utf8mb4", $usuarioBD, $passwordBD);

        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    //roles que admite la tabla: admin gestiona usuarios, usuario solo peliculas y directores
    const ROLES = ["admin", "usuario"];

    //crea un usuario; recibe la contraseña en texto plano y la guarda cifrada
    public function insert(string $nombre, string $password, string $rol = "usuario") {
        if (!in_array($rol, self::ROLES, true)) {
            $rol = "usuario";
        }
        $sql = "INSERT INTO usuario (nombre, password, rol) VALUES (?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $nombre,
            password_hash($password, PASSWORD_DEFAULT),
            $rol
        ]);
    }

    //actualiza solo el nombre; la contraseña se cambia con cambiarPassword
    public function update(Usuario $usuario) {
        $sql = "UPDATE usuario SET nombre = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $usuario->getNombre(),
            $usuario->getId()
        ]);
    }

    //recibe la nueva contraseña en texto plano y la guarda cifrada
    public function cambiarPassword(int $id, string $nuevaPassword) {
        $sql = "UPDATE usuario SET password = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            password_hash($nuevaPassword, PASSWORD_DEFAULT),
            $id
        ]);
    }

    //devuelve true si se ha borrado, false si no existia ese id
    public function delete($id): bool {
        $sql = "DELETE FROM usuario WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function findById($id) {
        $sql = "SELECT * FROM usuario WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->crearUsuario($fila) : null;
    }

    public function findByNombre(string $nombre) {
        $sql = "SELECT * FROM usuario WHERE nombre = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$nombre]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $this->crearUsuario($fila) : null;
    }

    public function findAll() {
        $sql = "SELECT * FROM usuario";
        $stmt = $this->conexion->query($sql);
        return array_map(fn($fila) => $this->crearUsuario($fila), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function contar(): int {
        return (int) $this->conexion->query("SELECT COUNT(*) FROM usuario")->fetchColumn();
    }

    public function contarAdmins(): int {
        return (int) $this->conexion->query("SELECT COUNT(*) FROM usuario WHERE rol = 'admin'")->fetchColumn();
    }

    //comprueba nombre y contraseña; devuelve el usuario si son correctos o null si no
    public function login(string $nombre, string $password) {
        $usuario = $this->findByNombre($nombre);
        if ($usuario && password_verify($password, $usuario->getPassword())) {
            return $usuario;
        }
        return null;
    }

    //las columnas se leen por nombre para no depender del orden del constructor
    private function crearUsuario(array $fila): Usuario {
        return new Usuario($fila["nombre"], $fila["password"], $fila["rol"], (int) $fila["id"]);
    }
}
?>
