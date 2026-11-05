<?php

require_once __DIR__ . "/director.php";

class DirectorDAO {
    private $conexion;

  public function __construct()
    {
        //los datos de conexion se cargan aqui y no con global, asi funciona
        //aunque la clase se cargue por primera vez dentro de una funcion
        require __DIR__ . "/datos/config.php";

        $this->conexion = new PDO("mysql:host=$servidor;dbname=$nombreBase;charset=utf8mb4", $usuarioBD, $passwordBD);

        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }

    public function insert(Director $director) {
        $sql = "INSERT INTO director
         (id, nombre, nacionalidad, fecha_nacimiento, biografia) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $director->getId(),
            $director->getNombre(),
            $director->getNacionalidad(),
            $director->getFechaNacimiento(),
            $director->getBiografia()
        ]);
    }

    public function update(Director $director) {
        $sql = "UPDATE director SET nombre = ?, nacionalidad = ?, fecha_nacimiento = ?, biografia = ? WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            $director->getNombre(),
            $director->getNacionalidad(),
            $director->getFechaNacimiento(),
            $director->getBiografia(),
            $director->getId()
        ]);
    }

    //devuelve true si se ha borrado, false si no existia ese id
    public function delete($id): bool {
        $sql = "DELETE FROM director WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function findById($id) {
        $sql = "SELECT * FROM director WHERE id = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([$id]);
        $fila = $stmt->fetch(PDO::FETCH_NUM);
        return $fila ? new Director(...$fila) : null;
    }

    public function findAll() {
        $sql = "SELECT * FROM director";
        $stmt = $this->conexion->query($sql);
        return array_map(function ($row) {
            return new Director(...$row);
        }, $stmt->fetchAll(PDO::FETCH_NUM));
    }
}
?>