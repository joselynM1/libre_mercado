<?php
/**
 * Ejecuta una operacion de escritura en los 3 nodos de forma atomica.
 *
 * Se usa para las tablas "catalogo" que estan replicadas en los 3 nodos
 * (productos, usuarios, proveedores). Garantiza que el mismo cambio se
 * aplique en TODOS los nodos o en NINGUNO:
 *
 *  - Si no se puede conectar a alguno de los 3 nodos -> se aborta antes
 *    de empezar (ningun cambio).
 *  - Si la operacion falla en cualquier nodo durante la transaccion ->
 *    rollback en todos los nodos que llegaron a iniciarla.
 *  - Solo si los 3 nodos confirman -> commit en los 3.
 *
 * Esto es deliberadamente estricto (modelo CP): ante una particion de
 * red que deje un nodo inalcanzable, las operaciones de escritura sobre
 * el catalogo se rechazan por completo en vez de aplicarse parcialmente.
 */
class MultiNodo
{
    /**
     * @param callable $accion function(PDO $pdo, int $idNodo): mixed
     * @return array<int, mixed> resultados de $accion indexados por id de nodo
     * @throws RuntimeException si algun nodo no esta disponible o la accion falla
     */
    public static function ejecutar(callable $accion): array
    {
        $conexiones = [];
        $resultados = [];

        // 1) Conectar y abrir transaccion en TODOS los nodos primero.
        foreach (ConexionNodos::ids() as $idNodo) {
            try {
                $pdo = ConexionNodos::get($idNodo);
                $pdo->beginTransaction();
                $conexiones[$idNodo] = $pdo;
            } catch (Throwable $e) {
                self::rollbackTodos($conexiones);
                throw new RuntimeException(
                    ConexionNodos::nombre($idNodo) . ' no está disponible. Operación cancelada (sin cambios en ningún nodo).'
                );
            }
        }

        // 2) Ejecutar la accion en cada nodo (fase "prepare").
        try {
            foreach ($conexiones as $idNodo => $pdo) {
                $resultados[$idNodo] = $accion($pdo, $idNodo);
            }
        } catch (Throwable $e) {
            self::rollbackTodos($conexiones);
            throw new RuntimeException('Operación cancelada (rollback en los 3 nodos): ' . $e->getMessage());
        }

        // 3) Confirmar en todos (fase "commit").
        foreach ($conexiones as $pdo) {
            $pdo->commit();
        }

        return $resultados;
    }

    /**
     * Obtiene el siguiente ID disponible para una tabla, consultando el
     * MAX(columna) en el primer nodo accesible. Se usa para que el mismo
     * registro tenga el MISMO id en los 3 nodos.
     */
    public static function siguienteId(string $tabla, string $columna): int
    {
        foreach (ConexionNodos::ids() as $idNodo) {
            try {
                $pdo = ConexionNodos::get($idNodo);
                $stmt = $pdo->query("SELECT COALESCE(MAX($columna), 0) + 1 AS siguiente FROM $tabla");
                return (int)$stmt->fetchColumn();
            } catch (Throwable $e) {
                continue;
            }
        }
        throw new RuntimeException('No se pudo contactar ningún nodo para generar el ID.');
    }

    private static function rollbackTodos(array $conexiones): void
    {
        foreach ($conexiones as $pdo) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        }
    }
}
