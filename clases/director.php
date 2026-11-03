<?php
class Director {
    private $id;
    private $nombre;
    private $nacionalidad;
    private $fechaNacimiento;
    private $biografia;

    public function __construct($id, $nombre, $nacionalidad, $fechaNacimiento, $biografia = "") {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->nacionalidad = $nacionalidad;
        $this->fechaNacimiento = $fechaNacimiento;
        $this->biografia = $biografia ?? "";
    }

    // Getters and Setters
    public function getId() {
        return $this->id;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function setNombre($nombre) {
        $this->nombre = $nombre;
    }

    public function getNacionalidad() {
        return $this->nacionalidad;
    }

    public function setNacionalidad($nacionalidad) {
        $this->nacionalidad = $nacionalidad;
    }

    public function getFechaNacimiento() {
        return $this->fechaNacimiento;
    }

    public function setFechaNacimiento($fechaNacimiento) {
        $this->fechaNacimiento = $fechaNacimiento;
    }

    public function getBiografia() {
        return $this->biografia;
    }

    public function setBiografia($biografia) {
        $this->biografia = $biografia;
    }
}
?>
