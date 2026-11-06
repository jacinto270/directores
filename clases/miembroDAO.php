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

    // ---------- verificacion del email (adaptado de login-app) ----------

    //ejecuta varias operaciones como una sola: si algo falla (por ejemplo, el envio del correo)
    //se deshace todo y no queda una cuenta a medias
    public function transaccion(callable $operaciones) {
        $this->conexion->beginTransaction();
        try {
            $resultado = $operaciones($this);
            $this->conexion->commit();
            return $resultado;
        } catch (Throwable $e) {
            $this->conexion->rollBack();
            throw $e;
        }
    }

    //cuenta que se registro pero nunca verifico el email: al volver a registrarse se actualizan sus datos
    public function actualizarSinVerificar(int $id, string $nombre, string $telefono, string $password): void {
        $sql = "UPDATE miembro SET nombre = ?, telefono = ?, password = ? WHERE id = ? AND verificado = 0";
        $this->conexion->prepare($sql)->execute([$nombre, $telefono, password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    /*
     * Crea un token de verificacion y devuelve el token en claro (va en el enlace del correo).
     * En la base de datos solo se guarda su hash SHA-256: aunque alguien leyera la tabla,
     * no podria usar los enlaces. Cada miembro tiene un solo token pendiente a la vez.
     */
    public function crearTokenVerificacion(int $miembroId, int $horas): string {
        $this->conexion->prepare("DELETE FROM token_email WHERE miembro_id = ? AND tipo = 'verificar' AND usado_en IS NULL")
            ->execute([$miembroId]);

        $token = bin2hex(random_bytes(32));    //64 caracteres hexadecimales, 256 bits aleatorios

        $sql = "INSERT INTO token_email (miembro_id, tipo, token_hash, caduca_en) VALUES (?, 'verificar', ?, DATE_ADD(NOW(), INTERVAL ? HOUR))";
        $this->conexion->prepare($sql)->execute([$miembroId, hash("sha256", $token), $horas]);

        return $token;
    }

    //true si se envio un enlace hace menos de $segundos (para no reenviar correos sin parar)
    public function tokenEnviadoHaceMenosDe(int $miembroId, int $segundos): bool {
        $sql = "SELECT COUNT(*) FROM token_email WHERE miembro_id = ? AND tipo = 'verificar' AND usado_en IS NULL
                AND creado_en > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$miembroId, $segundos]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /*
     * Comprueba el token del enlace y, si es valido, activa la cuenta.
     * Devuelve [estado, miembro]: estado es "ok", "invalido", "usado" o "caducado".
     */
    public function verificarEmail(string $token): array {
        //el token siempre son 64 caracteres hexadecimales: cualquier otra cosa ni se busca
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return ["invalido", null];
        }

        $stmt = $this->conexion->prepare("SELECT *, caduca_en < NOW() AS caducado FROM token_email WHERE token_hash = ? AND tipo = 'verificar'");
        $stmt->execute([hash("sha256", $token)]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fila) {
            return ["invalido", null];
        }
        $miembro = $this->findById((int) $fila["miembro_id"]);
        if ($fila["usado_en"] !== null) {
            return ["usado", $miembro];
        }
        if ($fila["caducado"]) {
            return ["caducado", $miembro];
        }

        $this->transaccion(function () use ($fila) {
            $this->conexion->prepare("UPDATE miembro SET verificado = 1, email_verificado_en = NOW() WHERE id = ?")
                ->execute([$fila["miembro_id"]]);
            $this->conexion->prepare("UPDATE token_email SET usado_en = NOW() WHERE id = ?")
                ->execute([$fila["id"]]);
        });

        return ["ok", $this->findById((int) $fila["miembro_id"])];
    }

    private function crearMiembro(array $fila): Miembro {
        return new Miembro($fila["nombre"], $fila["email"], $fila["telefono"], $fila["password"], (int) $fila["id"], $fila["avatar"], $fila["fecha_alta"], (bool) ($fila["verificado"] ?? false));
    }
}
?>
