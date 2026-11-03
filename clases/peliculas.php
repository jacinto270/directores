<?php
/**
 * propiedasdes que vamos a manejar decada pelicula
 * id,titulo,año,genero,visto,imagen
 * 
 */
class Pelicula {
    //propiedades o atributos que suelen ser privados parata que no se pùeda acceder desde fuera de la clase
   private ?int $id; 
   private string $titulo;
   private string $director;
   private int $anio;
   private string $genero;
   private string $imagen;
   private bool $visto;
   private array $actores = [];


//constructor es la funcion que se ejecuta al crear un objeto de la clase, sirve para inicializar los atributos de la clase
    public function __construct(string $titulo, string $director, int $anio, string $genero, string $imagen, bool $visto = false, ?int $id = null, array $actores = []){
             $this->id = $id;
             $this->titulo = $titulo;
             $this->director = $director;
             $this->anio = $anio;
             $this->genero = $genero;
             $this->imagen = $imagen;
             $this->visto = $visto;
             $this->actores = $actores;
    }

    //getters son funciones que nos permiten acceder a los atributos de la clase desde fuera de la clase
    public function getId(): ?int{
        return $this->id;
    }
    public function getTitulo(): string{
        return $this->titulo;
    }
    public function getDirector(): string{
        return $this->director;
    }
    public function getAnio(): int{
        return $this->anio;
    }
    public function getGenero(): string{
        return $this->genero;
    }
    public function getImagen(): string{
        return $this->imagen;
    }
    public function isVisto(): bool{
        return $this->visto;
    }
    public function getAntiguedad(): ?int{
        return (int) date("Y") - $this->anio;
    }
    public function getActores(): array{
        return $this->actores;
    }


//setters son funciones que nos permiten modificar los atributos de la clase desde fuera de la clase
    public function setId(?int $id): void{
        $this->id = $id;
    }
    public function setTitulo(string $titulo): void{
        $this->titulo = $titulo;    
    }
    public function setDirector(string $director): void{
        $this->director = $director;
    }
    public function setAnio(int $anio): void{
        $this->anio = $anio;
    }
    public function setGenero(string $genero): void{
        $this->genero = $genero;
    }
    public function setImagen(string $imagen): void{
        $this->imagen = $imagen;
    }
    public function setVisto(bool $visto): void{
        $this->visto = $visto;
    }
    public function setActor(string $actor): void{
        $this->actores[] = $actor;
    }

    public function __toString(): string
    {
        return "la pelicula $this->titulo es del director $this->director del Año $this->anio"; 

    } 

}     
?>