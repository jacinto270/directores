<?php

require_once(__DIR__ . "/peliculas.php");
class PeliculasDAO{
    //conexion con la base de datos

    private PDO $conexion;



    public function __construct()
    {
        //los datos de conexion se cargan aqui y no con global, asi funciona
        //aunque la clase se cargue por primera vez dentro de una funcion
        require __DIR__ . "/datos/config.php";

        $this->conexion = new PDO("mysql:host=$servidor;dbname=$nombreBase;charset=utf8mb4", $usuarioBD, $passwordBD);

        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    }
     //OBTENER TODAS LAS PELICULAS DE LA BASE DE DATOS
    public function getAll(): array {
      
        $sql = "SELECT * FROM pelicula";
        $stmt  = $this->conexion->query($sql);
       
            $peliculas = [];
            
            while($fila = $stmt->fetch(PDO::FETCH_ASSOC)){
                $pelicula = new Pelicula(
                    $fila["titulo"],
                    $fila["director"],
                    $fila["anio"],
                    $fila["genero"],
                    $fila["imagen"],
                    $fila["visto"] == 1 ? true : false,
                    $fila["id"],
                    json_decode($fila["actores"], true, 512, JSON_THROW_ON_ERROR),
                );
                $peliculas[] = $pelicula;
            }

            return $peliculas;
        }
        
        //OBTENER UNA PELICULA POR SU ID
        public function getById(int $id): ?Pelicula{
            $sql = "SELECT * FROM pelicula WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if($fila){
                return new Pelicula(
                    $fila["titulo"],
                    $fila["director"],
                    $fila["anio"],
                    $fila["genero"],
                    $fila["imagen"],
                    $fila["visto"] == 1 ? true : false,
                    $fila["id"],
                    json_decode($fila["actores"], true, 512, JSON_THROW_ON_ERROR),
                );
            }else{
                return null;
            }
        }

        //AGREGAR UNA PELICULA A LA BASE DE DATOS
        public function setPelicula(Pelicula $pelicula): bool{
            $sql ="INSERT INTO pelicula (titulo, director, anio, genero, imagen, visto, actores)
            VALUES (:titulo, :director, :anio, :genero, :imagen, :visto, :actores)";

           $stmt = $this->conexion->prepare($sql);

           return $stmt->execute([
            ":titulo" => $pelicula->getTitulo(),
            ":director" => $pelicula->getDirector(),    
            ":anio" => $pelicula->getAnio(),
            ":genero" => $pelicula->getGenero(),    
            ":imagen" => $pelicula->getImagen(),
            ":visto" => (int) $pelicula->isVisto(),
            ":actores" => json_encode($pelicula->getActores(), JSON_THROW_ON_ERROR)
           ]);
            
        }

        //actualizar una pelicula en la base de datos
        public function actualizar(Pelicula $pelicula): bool{

            $sql = "UPDATE pelicula
                    SET titulo = :titulo,
                        director = :director, 
                        anio = :anio, 
                        genero = :genero, 
                        imagen = :imagen, 
                        visto = :visto,
                        actores = :actores
                    WHERE id = :id";

            $stmt = $this->conexion->prepare($sql);
            
            return $stmt->execute([
                ":titulo" => $pelicula->getTitulo(),
                ":director" => $pelicula->getDirector(),
                ":anio" => $pelicula->getAnio(),
                ":genero" => $pelicula->getGenero(),
                ":imagen" => $pelicula->getImagen(),
                ":visto" => (int) $pelicula->isVisto(),
                ":actores" => json_encode($pelicula->getActores(), JSON_THROW_ON_ERROR),
                ":id" => $pelicula->getId()
            ]);
        }

        //eliminar una pelicula de la base de datos
        public function eliminar(int $id): bool{
            $sql = "DELETE FROM pelicula WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            return $stmt->execute([":id" => $id]);
        }
    }

    


?>