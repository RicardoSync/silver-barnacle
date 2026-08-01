<?php
require_once __DIR__ . '/../includes/config.php';

class TopologiaDAO {
    private $db;

    public function __construct() {
        $this->db = (new Conexion())->conectar();
    }

    public function obtenerNodos() {
        $this->sincronizarConInventario();
        $stmt = $this->db->query("SELECT * FROM topologia_nodos ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function sincronizarConInventario() {
        try {
            // 1. Purga de equipos borrados/desactivados
            $stmtEq = $this->db->query("SELECT id, nombre, ip_address FROM equipos WHERE estado = 1");
            $activeEquipos = $stmtEq->fetchAll(PDO::FETCH_ASSOC);
            $activeEqIds = array_column($activeEquipos, 'id');
            $activeEqMap = [];
            foreach ($activeEquipos as $e) { $activeEqMap[$e['id']] = $e; }

            // 2. Purga de mikrotiks borrados/desactivados
            $stmtMk = $this->db->query("SELECT id, nombre, ip_address FROM mikrotiks WHERE estado_actual = 1");
            $activeMikrotiks = $stmtMk->fetchAll(PDO::FETCH_ASSOC);
            $activeMkIds = array_column($activeMikrotiks, 'id');
            $activeMkMap = [];
            foreach ($activeMikrotiks as $m) { $activeMkMap[$m['id']] = $m; }

            // 3. Obtener nodos actuales de topología
            $stmtNodes = $this->db->query("SELECT id, nombre, ip_address, equipo_ref_id, tipo_ref FROM topologia_nodos");
            $topoNodes = $stmtNodes->fetchAll(PDO::FETCH_ASSOC);

            foreach ($topoNodes as $node) {
                if ($node['tipo_ref'] === 'equipo' && $node['equipo_ref_id']) {
                    if (!in_array($node['equipo_ref_id'], $activeEqIds)) {
                        // El equipo ya no existe o fue eliminado de la tabla equipos -> eliminar de topologia
                        $this->eliminarNodo($node['id']);
                    } else {
                        // Actualizar nombre o IP si cambió en la tabla equipos
                        $eqInfo = $activeEqMap[$node['equipo_ref_id']];
                        if ($eqInfo['nombre'] !== $node['nombre'] || $eqInfo['ip_address'] !== $node['ip_address']) {
                            $stmtUp = $this->db->prepare("UPDATE topologia_nodos SET nombre = ?, ip_address = ? WHERE id = ?");
                            $stmtUp->execute([$eqInfo['nombre'], $eqInfo['ip_address'], $node['id']]);
                        }
                    }
                } elseif ($node['tipo_ref'] === 'mikrotik' && $node['equipo_ref_id']) {
                    if (!in_array($node['equipo_ref_id'], $activeMkIds)) {
                        // El mikrotik fue eliminado -> eliminar de topologia
                        $this->eliminarNodo($node['id']);
                    } else {
                        // Actualizar nombre o IP si cambió
                        $mkInfo = $activeMkMap[$node['equipo_ref_id']];
                        if ($mkInfo['nombre'] !== $node['nombre'] || $mkInfo['ip_address'] !== $node['ip_address']) {
                            $stmtUp = $this->db->prepare("UPDATE topologia_nodos SET nombre = ?, ip_address = ? WHERE id = ?");
                            $stmtUp->execute([$mkInfo['nombre'], $mkInfo['ip_address'], $node['id']]);
                        }
                    }
                }
            }

            // 4. Auto-importar dispositivos activos que aún no estén en el mapa
            $this->importarDispositivosExistentes();
        } catch (\Exception $ex) {
            error_log("Error en sincronizarConInventario: " . $ex->getMessage());
        }
    }

    public function obtenerEnlaces() {
        $stmt = $this->db->query("SELECT e.*, 
            n1.nombre as origen_nombre, n1.ip_address as origen_ip,
            n2.nombre as destino_nombre, n2.ip_address as destino_ip
            FROM topologia_enlaces e
            JOIN topologia_nodos n1 ON e.nodo_origen_id = n1.id
            JOIN topologia_nodos n2 ON e.nodo_destino_id = n2.id
            ORDER BY e.id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerNodoPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM topologia_nodos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertarNodo($nombre, $ip_address, $tipo = 'ap', $pos_x = 150, $pos_y = 150, $equipo_ref_id = null, $tipo_ref = 'custom') {
        $stmt = $this->db->prepare("INSERT INTO topologia_nodos (nombre, ip_address, tipo, pos_x, pos_y, equipo_ref_id, tipo_ref) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $res = $stmt->execute([$nombre, $ip_address, $tipo, $pos_x, $pos_y, $equipo_ref_id, $tipo_ref]);
        if ($res) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function actualizarNodo($id, $nombre, $ip_address, $tipo) {
        $stmt = $this->db->prepare("UPDATE topologia_nodos SET nombre = ?, ip_address = ?, tipo = ? WHERE id = ?");
        return $stmt->execute([$nombre, $ip_address, $tipo, $id]);
    }

    public function actualizarPosicion($id, $pos_x, $pos_y) {
        $stmt = $this->db->prepare("UPDATE topologia_nodos SET pos_x = ?, pos_y = ? WHERE id = ?");
        return $stmt->execute([$pos_x, $pos_y, $id]);
    }

    public function eliminarNodo($id) {
        $stmt = $this->db->prepare("DELETE FROM topologia_nodos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function insertarEnlace($nodo_origen_id, $nodo_destino_id, $tipo_enlace = 'inalambrico', $etiqueta = '') {
        // Verificar que no exista el mismo enlace en ese sentido
        $stmtCheck = $this->db->prepare("SELECT id FROM topologia_enlaces WHERE (nodo_origen_id = ? AND nodo_destino_id = ?) OR (nodo_origen_id = ? AND nodo_destino_id = ?)");
        $stmtCheck->execute([$nodo_origen_id, $nodo_destino_id, $nodo_destino_id, $nodo_origen_id]);
        if ($stmtCheck->fetch()) {
            return false; // Ya existe enlace entre estos nodos
        }

        $stmt = $this->db->prepare("INSERT INTO topologia_enlaces (nodo_origen_id, nodo_destino_id, tipo_enlace, etiqueta) VALUES (?, ?, ?, ?)");
        $res = $stmt->execute([$nodo_origen_id, $nodo_destino_id, $tipo_enlace, $etiqueta]);
        if ($res) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function eliminarEnlace($id) {
        $stmt = $this->db->prepare("DELETE FROM topologia_enlaces WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function importarDispositivosExistentes() {
        $nodosCreados = 0;
        $x_start = 120;
        $y_start = 100;
        $gap_x = 220;
        $gap_y = 160;
        $max_cols = 4;
        
        // Obtener nodos existentes directamente de la DB sin llamar a obtenerNodos() para evitar recursión
        $stmtExist = $this->db->query("SELECT * FROM topologia_nodos ORDER BY id ASC");
        $nodosExistentes = $stmtExist->fetchAll(PDO::FETCH_ASSOC);
        $refsMikrotik = [];
        $refsEquipo = [];
        $ipsExistentes = [];

        foreach ($nodosExistentes as $n) {
            $ipsExistentes[] = trim($n['ip_address']);
            if ($n['tipo_ref'] === 'mikrotik' && $n['equipo_ref_id']) {
                $refsMikrotik[] = $n['equipo_ref_id'];
            }
            if ($n['tipo_ref'] === 'equipo' && $n['equipo_ref_id']) {
                $refsEquipo[] = $n['equipo_ref_id'];
            }
        }

        $count = count($nodosExistentes);

        // 1. Importar MikroTiks de la tabla `mikrotiks`
        $stmtMk = $this->db->query("SELECT id, nombre, ip_address FROM mikrotiks WHERE estado_actual = 1");
        $mks = $stmtMk->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mks as $mk) {
            if (!in_array($mk['id'], $refsMikrotik) && !in_array(trim($mk['ip_address']), $ipsExistentes)) {
                $col = $count % $max_cols;
                $row = floor($count / $max_cols);
                $px = $x_start + ($col * $gap_x);
                $py = $y_start + ($row * $gap_y);

                $this->insertarNodo($mk['nombre'], $mk['ip_address'], 'router', $px, $py, $mk['id'], 'mikrotik');
                $nodosCreados++;
                $count++;
            }
        }

        // 2. Importar Equipos de la tabla `equipos`
        $stmtEq = $this->db->query("SELECT id, nombre, ip_address FROM equipos WHERE estado = 1");
        $eqs = $stmtEq->fetchAll(PDO::FETCH_ASSOC);

        foreach ($eqs as $eq) {
            if (!in_array($eq['id'], $refsEquipo) && !in_array(trim($eq['ip_address']), $ipsExistentes)) {
                $col = $count % $max_cols;
                $row = floor($count / $max_cols);
                $px = $x_start + ($col * $gap_x);
                $py = $y_start + ($row * $gap_y);

                $this->insertarNodo($eq['nombre'], $eq['ip_address'], 'ap', $px, $py, $eq['id'], 'equipo');
                $nodosCreados++;
                $count++;
            }
        }

        return $nodosCreados;
    }

    public function hacerPing($ip) {
        $ip_escaped = escapeshellarg(trim($ip));
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            $output = shell_exec("ping -n 1 -w 1500 {$ip_escaped} 2>&1");
        } else {
            $output = shell_exec("ping -c 1 -W 2 {$ip_escaped} 2>&1");
        }

        $ms = -1;
        $status = 'offline';

        if (!empty($output)) {
            // Regex para capturar milisegundos en español o inglés
            if (preg_match('/(?:time|tiempo)[=<]([\d\.]+)\s*ms/i', $output, $matches)) {
                $ms = intval(round(floatval($matches[1])));
                $status = 'online';
            } elseif (preg_match('/rtt min\/avg\/max\/mdev = [\d\.]+\/([\d\.]+)\//i', $output, $matches)) {
                $ms = intval(round(floatval($matches[1])));
                $status = 'online';
            } elseif (strpos($output, 'TTL=') !== false || strpos($output, 'ttl=') !== false || strpos($output, '1 received') !== false || strpos($output, '1 recibidos') !== false) {
                $ms = 1; // Si devolvió TTL pero sin formato de tiempo exacto
                $status = 'online';
            }
        }

        return [
            'status' => $status,
            'ms' => $ms
        ];
    }
}
