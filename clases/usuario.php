<?php
class Usuario {
    private ?int $id;
    private string $nombre;
    private string $password;
    private string $rol;

    //$password es la contraseña cifrada con password_hash, nunca la contraseña en texto plano
    public function __construct(string $nombre, string $password,string $rol = "usuario", ?int $id = null)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->password = $password;
        $this->rol = $rol;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getNombre(): string {
        return $this->nombre;
    }

    public function getPassword(): string {
        return $this->password;
    }
    public function getRol(): string {
        return $this->rol;
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function setNombre(string $nombre): void {
        $this->nombre = $nombre;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }
    public function setRol(string $rol): void {
        $this->rol = $rol;
    }
}
?>
