<?php
//cuenta del sitio publico: puede ver el catalogo pero no entrar al panel
class Miembro {
    private ?int $id;
    private string $nombre;
    private string $email;
    private string $telefono;
    private string $password;
    private ?string $avatar;
    private ?string $fechaAlta;

    //$password es la contraseña cifrada con password_hash, nunca la contraseña en texto plano
    //$avatar es el nombre del archivo dentro de img/avatares, o null si no tiene foto
    public function __construct(string $nombre, string $email, string $telefono, string $password, ?int $id = null, ?string $avatar = null, ?string $fechaAlta = null)
    {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->email = $email;
        $this->telefono = $telefono;
        $this->password = $password;
        $this->avatar = $avatar;
        $this->fechaAlta = $fechaAlta;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getNombre(): string {
        return $this->nombre;
    }

    public function getEmail(): string {
        return $this->email;
    }

    public function getTelefono(): string {
        return $this->telefono;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function getAvatar(): ?string {
        return $this->avatar;
    }

    public function getFechaAlta(): ?string {
        return $this->fechaAlta;
    }

    public function setNombre(string $nombre): void {
        $this->nombre = $nombre;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function setTelefono(string $telefono): void {
        $this->telefono = $telefono;
    }
}
?>
